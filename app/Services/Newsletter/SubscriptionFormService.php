<?php

namespace App\Services\Newsletter;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Statamic\Contracts\Forms\Form as StatamicForm;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;
use Statamic\Facades\Form;
use Statamic\Fields\Field;

class SubscriptionFormService
{
    public function __construct(
        private readonly CollectionRegistry $collections,
    ) {}

    public function isNewsletterForm(StatamicForm $form): bool
    {
        return $this->group($form) !== null;
    }

    public function collectionHandle(StatamicForm $form): ?string
    {
        return $this->group($form)?->collection_handle;
    }

    public function group(StatamicForm $form): ?SubscriberGroup
    {
        $id = $form->get('newsletter_group');

        if (! filled($id)) {
            return null;
        }

        return SubscriberGroup::query()
            ->whereKey($id)
            ->whereNotNull('collection_handle')
            ->first();
    }

    public function endpointSlug(StatamicForm $form): string
    {
        return $form->get('newsletter_endpoint')
            ?: Str::of($form->handle())->replace('_', '-')->toString();
    }

    public function privacyUrl(StatamicForm $form): ?string
    {
        return $form->get('newsletter_privacy_url') ?: null;
    }

    public function logoUrl(StatamicForm $form): ?string
    {
        return $form->get('newsletter_logo_url') ?: null;
    }

    public function brandColor(StatamicForm $form): ?string
    {
        return $form->get('newsletter_brand_color') ?: null;
    }

    public function successMessage(StatamicForm $form): string
    {
        return $form->get('newsletter_success_message') ?: 'Subscription successful.';
    }

    public function sendConfirmationEmail(StatamicForm $form): bool
    {
        return (bool) $form->get('newsletter_send_confirmation_email');
    }

    public function sendUpdateEmail(StatamicForm $form): bool
    {
        return (bool) $form->get('newsletter_send_update_email');
    }

    public function preferenceFieldHandle(StatamicForm $form): ?string
    {
        $handle = $form->get('newsletter_preference_field');

        return is_string($handle) && $handle !== '' ? $handle : null;
    }

    public function resolveForm(string $identifier): ?StatamicForm
    {
        if ($form = Form::find($identifier)) {
            return $this->isNewsletterForm($form) ? $form : null;
        }

        return Form::all()
            ->first(fn (StatamicForm $form) =>
                $this->isNewsletterForm($form) && $this->endpointSlug($form) === $identifier
            );
    }

    public function schema(StatamicForm $form): array
    {
        return [
            'handle' => $form->handle(),
            'title' => $form->title(),
            'collection' => $this->collectionHandle($form),
            'group' => $this->group($form)?->only(['id', 'name', 'slug']),
            'endpoint' => $this->collections->formEndpoint($this->endpointSlug($form)),
            'privacy_url' => $this->privacyUrl($form),
            'logo_url' => $this->logoUrl($form),
            'brand_color' => $this->brandColor($form),
            'success_message' => $this->successMessage($form),
            'preference_field' => $this->preferenceFieldHandle($form),
            'fields' => $form->fields()
                ->map(fn (Field $field) => $this->serializeField($field))
                ->values()
                ->all(),
        ];
    }

    public function syncManagedSubGroups(StatamicForm $form): Collection
    {
        $collectionHandle = $this->collectionHandle($form);

        $group = $this->group($form);

        if (! $collectionHandle || ! $group) {
            return collect();
        }

        return collect($this->preferenceOptions($form))
            ->map(function (array $option) use ($group) {
                return SubscriberSubGroup::firstOrCreate(
                    [
                        'subscriber_group_id' => $group->id,
                        'slug' => $option['slug'],
                    ],
                    [
                        'name' => $option['label'],
                        'description' => 'Auto-managed from subscribe form preferences.',
                    ]
                );
            });
    }

    public function storeSubmission(StatamicForm $form, array $payload): ?StatamicSubmission
    {
        if (! $form->store()) {
            return null;
        }

        $submission = $form->makeSubmission()
            ->data($payload);

        $submission->save();

        return $submission;
    }

    public function annotateSubmission(StatamicSubmission $submission, array $attributes): void
    {
        $submission
            ->data(array_merge($submission->data()->all(), $attributes))
            ->save();
    }

    public function subscribe(StatamicForm $form, array $payload, Request $request): array
    {
        $collectionHandle = $this->collectionHandle($form);
        $group = $this->group($form);
        abort_if(! $group || ! $collectionHandle, 422, 'Newsletter form is not linked to a valid subscriber group.');
        $groupSlug = $group->slug;
        $managedSubGroups = $this->syncManagedSubGroups($form)->keyBy('slug');
        $groupId = $group->id;

        $subscriber = Subscriber::firstOrNew([
            'email' => strtolower(trim((string) Arr::get($payload, 'email'))),
        ]);
        $wasExisting = $subscriber->exists;
        $previousStatus = $subscriber->status;
        $previousFirstName = $subscriber->first_name;
        $previousLastName = $subscriber->last_name;
        $previousManagedIds = $wasExisting
            ? $subscriber->allSubGroups()
                ->where('subscriber_group_id', $groupId)
                ->whereIn('subscriber_sub_groups.id', $managedSubGroups->pluck('id')->all())
                ->wherePivotNull('subscriber_sub_group.unsubscribed_at')
                ->pluck('subscriber_sub_groups.id')
                ->sort()
                ->values()
                ->all()
            : [];

        $metadata = array_merge($subscriber->metadata ?? [], [
            'newsletter_form' => [
                'handle' => $form->handle(),
                'collection' => $collectionHandle,
                'endpoint' => $this->endpointSlug($form),
                'selected_preferences' => $this->selectedPreferenceSlugs($form, $payload),
                'subscribed_via' => 'statamic_form',
            ],
        ]);

        $subscriber->fill([
            'first_name' => $this->resolveNameField($payload, ['first_name', 'firstname', 'first']),
            'last_name' => $this->resolveNameField($payload, ['last_name', 'lastname', 'last']),
            'status' => 'active',
            'confirmed_at' => $subscriber->confirmed_at ?? now(),
            'unsubscribed_at' => null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
            'metadata' => $metadata,
        ]);

        $subscriber->ensureConfirmationToken();
        $subscriber->save();

        $managedIds = $managedSubGroups->pluck('id')->all();
        $selectedIds = collect($this->selectedPreferenceSlugs($form, $payload))
            ->map(fn (string $slug) => $managedSubGroups->get($slug)?->id)
            ->filter()
            ->values()
            ->all();

        $existingManaged = $subscriber->allSubGroups()
            ->where('subscriber_group_id', $groupId)
            ->whereIn('subscriber_sub_groups.id', $managedIds)
            ->pluck('subscriber_sub_groups.id')
            ->all();

        $toAdd = array_diff($selectedIds, $existingManaged);
        $toReactivate = array_intersect($selectedIds, $existingManaged);
        $toRemove = array_diff($existingManaged, $selectedIds);

        foreach ($toReactivate as $subGroupId) {
            $subscriber->allSubGroups()->updateExistingPivot($subGroupId, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        if ($toAdd) {
            $subscriber->subGroups()->attach($toAdd, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        if ($toRemove) {
            $subscriber->allSubGroups()->updateExistingPivot($toRemove, [
                'unsubscribed_at' => now(),
            ]);
        }

        $subscriber = $subscriber->fresh(['subGroups.group']);
        $currentManagedIds = $subscriber->subGroups
            ->where('group.slug', $groupSlug)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $namesChanged = $this->normalize($previousFirstName) !== $this->normalize($subscriber->first_name)
            || $this->normalize($previousLastName) !== $this->normalize($subscriber->last_name);
        $preferencesChanged = $previousManagedIds !== $currentManagedIds;

        $status = match (true) {
            ! $wasExisting => 'subscribed',
            $previousStatus === 'unsubscribed' => 'resubscribed',
            $namesChanged || $preferencesChanged => 'subscription_updated',
            default => 'already_subscribed',
        };

        return [
            'subscriber' => $subscriber,
            'subscriber_group_id' => $group->id,
            'status' => $status,
            'message' => $this->messageForStatus($status, $form),
            'email_sent' => $this->dispatchLifecycleEmail($form, $subscriber, $status),
        ];
    }

    public function preferenceOptions(StatamicForm $form): array
    {
        $fieldHandle = $this->preferenceFieldHandle($form);

        if (! $fieldHandle) {
            return [];
        }

        $field = $form->fields()->get($fieldHandle);

        if (! $field) {
            return [];
        }

        return collect($field->get('options', []))
            ->map(function ($label, $value) {
                if (is_array($label)) {
                    $optionValue = $label['key'] ?? $label['value'] ?? $value;
                    $optionLabel = $label['label'] ?? $label['text'] ?? $label['value'] ?? $optionValue;

                    return [
                        'slug' => Str::slug((string) $optionValue),
                        'label' => (string) $optionLabel,
                    ];
                }

                return [
                    'slug' => Str::slug((string) $value),
                    'label' => (string) $label,
                ];
            })
            ->filter(fn (array $option) => filled($option['slug']) && filled($option['label']))
            ->values()
            ->all();
    }

    public function selectedPreferenceSlugs(StatamicForm $form, array $payload): array
    {
        $fieldHandle = $this->preferenceFieldHandle($form);

        if (! $fieldHandle) {
            return [];
        }

        return collect(Arr::wrap(Arr::get($payload, $fieldHandle)))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => Str::slug((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function serializeField(Field $field): array
    {
        return [
            'handle' => $field->handle(),
            'type' => $field->type(),
            'display' => $field->display(),
            'instructions' => $field->instructions(),
            'required' => $field->isRequired(),
            'options' => collect($field->get('options', []))
                ->map(function ($label, $value) {
                    if (is_array($label)) {
                        return [
                            'value' => (string) ($label['key'] ?? $label['value'] ?? $value),
                            'label' => (string) ($label['label'] ?? $label['text'] ?? $label['value'] ?? $label['key'] ?? $value),
                        ];
                    }

                    return [
                        'value' => (string) $value,
                        'label' => (string) $label,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function messageForStatus(string $status, StatamicForm $form): string
    {
        return match ($status) {
            'already_subscribed' => 'You are already subscribed.',
            'subscription_updated' => 'You are already subscribed. Your details or preferences were updated.',
            'resubscribed' => 'Your subscription has been restored.',
            default => $this->successMessage($form),
        };
    }

    private function dispatchLifecycleEmail(StatamicForm $form, Subscriber $subscriber, string $status): bool
    {
        if (! $this->shouldSendLifecycleEmail($form, $status)) {
            return false;
        }

        $collectionHandle = $this->collectionHandle($form);

        Mail::to($subscriber->email, $subscriber->full_name)->queue(
            new SubscriptionConfirmationMail(
                subscriber: $subscriber,
                status: $status,
                mailConfig: [
                    'collection_handle' => $collectionHandle,
                    'collection_label' => $this->collections->label($collectionHandle),
                    'sender' => $this->collections->sender($collectionHandle),
                    'privacy_url' => $this->privacyUrl($form),
                    'logo_url' => $this->logoUrl($form),
                    'brand_color' => $this->brandColor($form),
                    'subject' => $this->confirmationSubject($form, $status),
                    'body' => $this->confirmationBody($form, $status),
                ],
            )
        );

        return true;
    }

    private function shouldSendLifecycleEmail(StatamicForm $form, string $status): bool
    {
        if (! $this->sendConfirmationEmail($form)) {
            return false;
        }

        return match ($status) {
            'subscribed', 'resubscribed' => true,
            'subscription_updated' => $this->sendUpdateEmail($form),
            default => false,
        };
    }

    private function confirmationSubject(StatamicForm $form, string $status): string
    {
        $collectionLabel = $this->collections->label($this->collectionHandle($form));
        $configured = match ($status) {
            'resubscribed' => $form->get('newsletter_resubscribe_subject') ?: $form->get('newsletter_confirmation_subject'),
            default => $form->get('newsletter_confirmation_subject'),
        };

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($status) {
            'resubscribed' => "Welcome back to {$collectionLabel}",
            'subscription_updated' => "Your {$collectionLabel} preferences were updated",
            default => "Welcome to {$collectionLabel}",
        };
    }

    private function confirmationBody(StatamicForm $form, string $status): string
    {
        $collectionLabel = $this->collections->label($this->collectionHandle($form));
        $configured = match ($status) {
            'resubscribed' => $form->get('newsletter_resubscribe_body') ?: $form->get('newsletter_confirmation_body'),
            default => $form->get('newsletter_confirmation_body'),
        };

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($status) {
            'resubscribed' => "You are subscribed to {$collectionLabel} again. We will continue sending updates to this email address.",
            'subscription_updated' => "Your {$collectionLabel} subscriber details have been updated.",
            default => "Thank you for subscribing to {$collectionLabel}. We will send future updates to this email address.",
        };
    }

    private function normalize(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }

    private function resolveNameField(array $payload, array $handles): ?string
    {
        foreach ($handles as $handle) {
            $value = Arr::get($payload, $handle);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
