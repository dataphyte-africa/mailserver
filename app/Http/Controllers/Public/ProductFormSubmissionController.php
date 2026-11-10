<?php

namespace App\Http\Controllers\Public;

use App\Mail\SubscriptionManagementLinkMail;
use App\Models\ProductFormSubmission;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Forms\ApplicationWindow;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductFormSubmissionController
{
    public function __construct(
        private readonly ProductFormService $forms,
    ) {}

    public function store(string $form, Request $request, ProductFormSubscriptionService $subscriptions, ApplicationWindow $window): JsonResponse|RedirectResponse
    {
        $resolved = $this->forms->resolvePublishedForm($form);

        abort_unless($resolved?->isPublished(), 404);
        abort_if($resolved->mode !== 'subscription' && ! $window->isOpen(
            (bool) ($resolved->settings['accepting_submissions'] ?? true),
            $resolved->settings['closes_at'] ?? null,
        ), 410, 'This application is closed.');

        if ($resolved->mode === 'subscription') {
            $this->forms->assertAllowedOrigin($resolved, $request);
            $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];
            $subscriber = Subscriber::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
            if ($request->input('action') === 'manage_existing') {
                $request->session()->forget('subscription_profile_completion');
                if ($subscriber && ! in_array($subscriber->status, ['erased'], true)) {
                    Mail::to($subscriber->email)->queue((new SubscriptionManagementLinkMail(
                        $subscriber,
                        $resolved->organisation,
                    ))->afterCommit());
                }

                $message = 'If this email is registered, a link to manage its preferences and details is on the way.';
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json(['status' => 'management_link_requested', 'success_message' => $message]);
                }

                return redirect()->back()->with('product_form_success', $message);
            }
            if ($subscriber) {
                $request->session()->forget('subscription_profile_completion');
                $subscriptions->validateSelection($resolved, $request->all());
                if (in_array($subscriber->status, ['erased', 'bounced', 'complained'], true)) {
                    throw ValidationException::withMessages([
                        'email' => ['This address cannot be subscribed through a public form.'],
                    ]);
                }
                Mail::to($subscriber->email)->queue((new SubscriptionManagementLinkMail(
                    $subscriber,
                    $resolved->organisation,
                ))->afterCommit());

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'management_link_sent',
                        'success_message' => 'This email is already registered. Check your inbox for a link to view or update your preferences and details.',
                    ]);
                }

                return redirect()->back()->with('product_form_success',
                    'This email is already registered. Check your inbox for a link to view or update your preferences and details.');
            }
        }

        [$submission, $subscription] = DB::transaction(function () use ($resolved, $request, $subscriptions): array {
            $submission = $this->forms->storeSubmission($resolved, $request->all(), $request);
            $subscription = $resolved->mode === 'subscription'
                ? $subscriptions->subscribe($resolved, $submission->payload ?? [], $request)
                : null;

            return [$submission, $subscription];
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => $subscription ? $subscription['subscriber']->status : 'submitted',
                'submission_id' => $submission->getKey(),
                'subscription_status' => $subscription['status'] ?? null,
                'email_sent' => $subscription['email_sent'] ?? null,
                'success_message' => $resolved->success_message,
            ], 201);
        }

        $offerProfile = $subscription && (collect($resolved->field_definitions ?? [])
            ->contains(fn (array $field): bool => $field['handle'] !== 'email'
                && $field['type'] !== 'checkbox' && ! $field['required'])
            || $subscriptions->approvedPreferences($resolved)->count() > 1);
        if ($subscription) {
            $request->session()->forget('subscription_profile_completion');
        }
        if ($offerProfile) {
            $request->session()->put('subscription_profile_completion', [
                'form_id' => $resolved->getKey(),
                'submission_id' => $submission->getKey(),
                'subscriber_id' => $subscription['subscriber']->getKey(),
                'selected_preference_ids' => $submission->payload['preferences'] ?? [],
            ]);
        }

        return redirect()
            ->back()
            ->with($offerProfile ? 'subscription_profile_started' : 'product_form_success', $resolved->success_message);
    }

    public function completeProfile(string $form, Request $request, ProductFormSubscriptionService $subscriptions, GroupEngagementService $engagement): RedirectResponse
    {
        $resolved = $this->forms->resolvePublishedForm($form);
        abort_unless($resolved?->isPublished() && $resolved->mode === 'subscription', 404);

        $pending = $request->session()->get('subscription_profile_completion');
        abort_unless(is_array($pending) && (int) ($pending['form_id'] ?? 0) === (int) $resolved->getKey(), 403);

        $recommendedIds = array_map('intval', $request->validate([
            'recommended_preferences' => ['sometimes', 'array'],
            'recommended_preferences.*' => ['integer', 'distinct'],
        ])['recommended_preferences'] ?? []);
        $available = $subscriptions->approvedPreferences($resolved)->flatMap(
            fn ($group) => $group->subGroups->modelKeys()
        )->map(fn ($id) => (int) $id)->all();
        $selected = array_map('intval', $pending['selected_preference_ids'] ?? []);
        abort_if(array_diff($recommendedIds, array_diff($available, $selected)) !== [], 422);
        $groupIds = SubscriberSubGroup::query()->whereKey(array_merge($selected, $recommendedIds))
            ->pluck('subscriber_group_id');
        abort_if($groupIds->unique()->count() !== $groupIds->count(), 422);

        if ($recommendedIds !== []) {
            $subscriber = Subscriber::query()->findOrFail($pending['subscriber_id']);
            DB::transaction(function () use ($subscriber, $recommendedIds): void {
                foreach ($recommendedIds as $id) {
                    $subscriber->allSubGroups()->syncWithoutDetaching([$id => [
                        'subscribed_at' => now(), 'unsubscribed_at' => null,
                    ]]);
                }
            });
            $engagement->syncForSubscriber($subscriber->fresh(), $groupIds->all());
        }

        if ($request->input('action') === 'skip') {
            $request->session()->forget('subscription_profile_completion');

            return redirect()->to($this->forms->hostedPageUrl($resolved));
        }

        $rules = [];
        foreach ($resolved->field_definitions ?? [] as $field) {
            $handle = (string) $field['handle'];
            if ($handle === 'email' || $field['type'] === 'checkbox' || $field['required']) {
                continue;
            }

            $rules[$handle] = match ($field['type']) {
                'country' => ['nullable', Rule::exists('countries', 'code')],
                'select' => ['nullable', Rule::in(array_column($field['options'] ?? [], 'value'))],
                'textarea' => ['nullable', 'string', 'max:10000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }
        $details = array_filter($request->validate($rules), fn ($value) => $value !== null && $value !== '');

        DB::transaction(function () use ($resolved, $pending, $details): void {
            $submission = ProductFormSubmission::query()
                ->whereKey($pending['submission_id'])
                ->where('product_form_id', $resolved->getKey())
                ->firstOrFail();
            $subscriber = Subscriber::query()
                ->whereKey($pending['subscriber_id'])
                ->where('email', $submission->payload['email'] ?? null)
                ->firstOrFail();

            $submission->update(['payload' => array_merge($submission->payload ?? [], $details)]);

            $metadata = $subscriber->metadata ?? [];
            $profile = $metadata['reader_profiles'][$resolved->organisation_id] ?? [];
            foreach ($details as $handle => $value) {
                if (in_array($handle, ['first_name', 'firstname'], true)) {
                    $subscriber->first_name = $value;
                } elseif (in_array($handle, ['last_name', 'lastname'], true)) {
                    $subscriber->last_name = $value;
                } else {
                    $profile[$handle] = $value;
                }
            }
            $metadata['reader_profiles'][$resolved->organisation_id] = $profile;
            $subscriber->metadata = $metadata;
            $subscriber->save();
        });

        $request->session()->forget('subscription_profile_completion');

        return redirect()->to($this->forms->hostedPageUrl($resolved))
            ->with('subscription_profile_saved', 'Your details have been saved.');
    }
}
