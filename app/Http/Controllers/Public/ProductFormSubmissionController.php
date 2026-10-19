<?php

namespace App\Http\Controllers\Public;

use App\Services\Forms\ApplicationWindow;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return redirect()
            ->back()
            ->with('product_form_success', $resolved->success_message);
    }
}
