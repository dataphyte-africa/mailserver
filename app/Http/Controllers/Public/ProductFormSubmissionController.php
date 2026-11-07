<?php

namespace App\Http\Controllers\Public;

use App\Models\ProductFormSubmission;
use App\Models\Subscriber;
use App\Services\Forms\ApplicationWindow;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $offerProfile = $subscription && collect($resolved->field_definitions ?? [])
            ->contains(fn (array $field): bool => $field['handle'] !== 'email'
                && $field['type'] !== 'checkbox' && ! $field['required']);
        if ($subscription) {
            $request->session()->forget('subscription_profile_completion');
        }
        if ($offerProfile) {
            $request->session()->put('subscription_profile_completion', [
                'form_id' => $resolved->getKey(),
                'submission_id' => $submission->getKey(),
                'subscriber_id' => $subscription['subscriber']->getKey(),
            ]);
        }

        return redirect()
            ->back()
            ->with($offerProfile ? 'subscription_profile_started' : 'product_form_success', $resolved->success_message);
    }

    public function completeProfile(string $form, Request $request): RedirectResponse
    {
        $resolved = $this->forms->resolvePublishedForm($form);
        abort_unless($resolved?->isPublished() && $resolved->mode === 'subscription', 404);

        $pending = $request->session()->get('subscription_profile_completion');
        abort_unless(is_array($pending) && (int) ($pending['form_id'] ?? 0) === (int) $resolved->getKey(), 403);

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
