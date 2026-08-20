<?php

namespace App\Http\Controllers\Public;

use App\Models\ProductForm;
use App\Services\Forms\ProductFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductFormSubmissionController
{
    public function __construct(
        private readonly ProductFormService $forms,
    ) {}

    public function store(string $form, Request $request): JsonResponse|RedirectResponse
    {
        $resolved = $this->forms->resolvePublishedForm($form);

        abort_unless($resolved?->isPublished(), 404);

        $submission = $this->forms->storeSubmission($resolved, $request->all(), $request);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'submitted',
                'submission_id' => $submission->getKey(),
                'success_message' => $resolved->success_message,
            ], 201);
        }

        return redirect()
            ->back()
            ->with('product_form_success', $resolved->success_message);
    }
}
