<?php

namespace App\Services\Forms;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\ProductForm;
use App\Models\Organisation;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\CollectionRegistry;
use App\Services\Newsletter\SubscriberEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductFormSubscriptionService
{
    public function __construct(
        private readonly SubscriberEnrollmentService $enrollment,
        private readonly CollectionRegistry $collections,
    ) {}

    public function subscribe(ProductForm $form, array $payload, Request $request): array
    {
        $this->assertSubscriptionForm($form);
        $selectedIds = $payload['preferences'] ?? [];
        $managedIds = $this->approvedPreferences($form)->flatMap(
            fn (SubscriberGroup $group) => $group->subGroups->modelKeys()
        )->map(fn ($id) => (int) $id)->all();

        $managedIds = array_values(array_unique(array_merge($managedIds, $selectedIds)));

        $result = $this->enrollment->enrollPreferences(
            $managedIds,
            $selectedIds,
            [
                'email' => $payload['email'],
                'first_name' => $payload['first_name'] ?? $payload['firstname'] ?? null,
                'last_name' => $payload['last_name'] ?? $payload['lastname'] ?? null,
            ],
            ['product_form' => [
                'id' => $form->getKey(),
                'slug' => $form->slug,
                'product_id' => $form->product_id,
                'subscribed_via' => 'hosted_form',
            ]],
            $request,
        );

        $sendInitial = in_array($result['status'], ['subscribed', 'resubscribed'], true);
        $sendUpdate = $result['status'] === 'subscription_updated'
            && $result['subscriber']->status === 'active'
            && (bool) ($form->settings['send_update_email'] ?? false);
        $result['email_sent'] = ($sendInitial || $sendUpdate)
            ? $this->sendConfirmation($form, $result['subscriber'], $result['status'])
            : false;

        return $result;
    }

    public function resendConfirmation(ProductForm $form, Subscriber $subscriber, string $status): void
    {
        $this->assertSubscriptionForm($form);
        $this->sendConfirmation($form, $subscriber, $status);
    }

    public function approvedPreferences(ProductForm $form)
    {
        $this->assertSubscriptionForm($form);

        if ($form->form_scope === 'organisation') {
            $groups = $this->availablePreferencesForOrganisations([(int) $form->organisation_id]);
            $settings = $form->settings ?? [];

            return array_key_exists('public_preference_group_ids', $settings)
                ? $groups->whereIn('id', $settings['public_preference_group_ids'] ?? [])->values()
                : $groups;
        }

        return SubscriberGroup::query()
            ->where('organisation_id', $form->organisation_id)
            ->where('product_id', $form->product_id)
            ->where('collection_handle', $form->product->primary_collection_handle)
            ->whereHas('product', fn ($query) => $query
                ->where('status', 'active')
                ->where('organisation_id', $form->organisation_id))
            ->where('is_public_preference', true)
            ->whereNull('archived_at')
            ->whereHas('subGroups', fn ($query) => $query->whereNull('archived_at'))
            ->with(['product', 'subGroups' => fn ($query) => $query->whereNull('archived_at')->orderBy('name')])
            ->orderBy('name')->get();
    }

    public function availablePreferencesForOrganisations(array $organisationIds)
    {
        return SubscriberGroup::query()
            ->whereIn('organisation_id', $organisationIds)
            ->where('is_public_preference', true)
            ->whereNull('archived_at')
            ->whereHas('product', fn ($query) => $query
                ->where('status', 'active')
                ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle'))
            ->whereHas('subGroups', fn ($query) => $query->whereNull('archived_at'))
            ->with(['product', 'subGroups' => fn ($query) => $query->whereNull('archived_at')->orderBy('name')])
            ->orderBy('name')->get();
    }

    public function selectedPreferenceGroupIds(Organisation $organisation, mixed $selected): array
    {
        if (! is_array($selected) || $selected === []) {
            throw ValidationException::withMessages([
                'public_preference_group_ids' => ['Select at least one approved public preference for this form.'],
            ]);
        }

        $ids = [];
        foreach ($selected as $id) {
            if (! is_scalar($id) || ! ctype_digit((string) $id) || (int) $id <= 0) {
                throw ValidationException::withMessages([
                    'public_preference_group_ids' => ['One or more selected preferences are invalid.'],
                ]);
            }
            $ids[] = (int) $id;
        }

        if (count($ids) !== count(array_unique($ids))
            || array_diff($ids, $this->availablePreferencesForOrganisations([(int) $organisation->getKey()])->modelKeys()) !== []) {
            throw ValidationException::withMessages([
                'public_preference_group_ids' => ['Select only approved public preferences in this organisation.'],
            ]);
        }

        return $ids;
    }

    public function validateSelection(ProductForm $form, array $payload): array
    {
        $this->assertSubscriptionForm($form);
        $selected = $payload['preferences'] ?? null;
        if ($form->form_scope === 'product' && $selected === null && ! array_key_exists('preferences_present', $payload) && $form->audience_sub_group_id) {
            $group = SubscriberGroup::query()->find($form->audience_group_id);
            $subGroup = SubscriberSubGroup::query()->find($form->audience_sub_group_id);
            if ($group && ! $group->isArchived() && $subGroup && ! $subGroup->isArchived()
                && (int) $group->product_id === (int) $form->product_id
                && (int) $group->organisation_id === (int) $form->organisation_id
                && $group->collection_handle === $form->product->primary_collection_handle
                && (int) $subGroup->subscriber_group_id === (int) $group->getKey()) {
                return [(int) $subGroup->getKey()];
            }
        }

        if (! is_array($selected) || $selected === []) {
            throw ValidationException::withMessages(['preferences' => ['Select at least one available preference.']]);
        }

        $ids = [];
        foreach ($selected as $id) {
            if (! is_scalar($id) || ! ctype_digit((string) $id) || (int) $id <= 0) {
                throw ValidationException::withMessages(['preferences' => ['One or more selected preferences are invalid.']]);
            }
            $ids[] = (int) $id;
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['preferences' => ['Selected preferences must be distinct.']]);
        }

        $approvedIds = $this->approvedPreferences($form)->flatMap(
            fn (SubscriberGroup $group) => $group->subGroups->modelKeys()
        )->map(fn ($id) => (int) $id)->all();
        if (array_diff($ids, $approvedIds) !== []) {
            throw ValidationException::withMessages(['preferences' => ['One or more selected preferences are unavailable for this organisation.']]);
        }

        return $ids;
    }

    private function assertSubscriptionForm(ProductForm $form): void
    {
        $product = $form->product;
        if ($form->mode !== 'subscription'
            || ! in_array($form->form_scope, ['product', 'organisation'], true)
            || ! $form->organisation || $form->organisation->status !== 'active'
            || ($form->form_scope === 'product' && (! $product || $product->status !== 'active'
                || (int) $product->organisation_id !== (int) $form->organisation_id))) {
            throw ValidationException::withMessages([
                'audience' => ['This subscription form no longer has an active organisation or product.'],
            ]);
        }
    }

    private function sendConfirmation(ProductForm $form, Subscriber $subscriber, string $status): bool
    {
        $product = $form->product;
        $organisation = $form->organisation;
        $collection = $form->form_scope === 'organisation'
            ? $organisation->primary_collection_handle : $product->primary_collection_handle;
        $sender = $this->collections->sender($collection);
        $sender['from_name'] = $organisation->default_from_name ?: $sender['from_name'];
        $sender['reply_to'] = $organisation->default_reply_to ?: $sender['reply_to'];

        Mail::to($subscriber->email, $subscriber->full_name)->queue((new SubscriptionConfirmationMail(
            subscriber: $subscriber,
            status: $status,
            mailConfig: [
                'collection_handle' => $collection,
                'product_key' => $form->form_scope === 'organisation' ? $organisation : $collection,
                'collection_label' => $organisation->name,
                'sender' => $sender,
                'logo_url' => $organisation->logo_path
                    ? url(Storage::disk('public')->url($organisation->logo_path)) : null,
                'brand_color' => $organisation->brand_primary_color ?: $sender['brand_color'],
                'subject' => match ($status) {
                    'resubscribed' => ($form->settings['resubscribe_subject'] ?? null) ?: 'Welcome back to '.$organisation->name,
                    'subscription_updated' => ($form->settings['update_subject'] ?? null) ?: 'Your '.$organisation->name.' preferences were updated',
                    default => ($form->settings['welcome_subject'] ?? null) ?: 'Welcome to '.$organisation->name,
                },
                'body' => match ($status) {
                    'resubscribed' => ($form->settings['resubscribe_body'] ?? null) ?: 'You are subscribed to '.($product?->name ?? $organisation->name).' updates again.',
                    'subscription_updated' => ($form->settings['update_body'] ?? null) ?: 'Your '.($product?->name ?? $organisation->name).' preferences were updated.',
                    default => ($form->settings['welcome_body'] ?? null) ?: 'Thank you for subscribing to '.($product?->name ?? $organisation->name).' updates.',
                },
                'form_handle' => $form->slug,
                'submission_mode' => 'subscription',
            ],
        ))->afterCommit());

        return true;
    }
}
