<?php

namespace App\Http\Controllers\CP\Forms;

use App\Models\ProductForm;
use App\Models\ProductFormSubmission;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ScopedProductFormProductSelector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductFormSubmissionController
{
    public function __construct(
        private readonly ProductFormService $forms,
        private readonly ScopedProductFormProductSelector $products,
    ) {}

    public function index(Request $request, ProductForm $productForm): View
    {
        abort_if(! $this->products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $submissions = $this->forms->submissions($productForm);

        return view('forms.cp.submissions', ['form' => $productForm, 'submissions' => $submissions]);
    }

    public function export(Request $request, ProductForm $productForm): Response
    {
        abort_if(! $this->products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $content = $this->forms->csvContent($productForm);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s-submissions.csv"', $productForm->slug),
        ]);
    }

    public function transition(Request $request, ProductForm $productForm, ProductFormSubmission $submission): RedirectResponse
    {
        abort_if(! $this->products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:submitted,pending_review,under_review,approved,rejected'],
        ]);

        $this->forms->transitionSubmission($productForm, $submission, $validated['status']);

        return redirect()
            ->back()
            ->with('success', 'Submission status updated.');
    }
}
