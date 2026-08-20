<?php

namespace App\Http\Controllers\Public;

use App\Models\ProductForm;
use App\Services\Forms\ProductFormService;
use Illuminate\Contracts\View\View;

class ProductFormPageController
{
    public function __construct(
        private readonly ProductFormService $forms,
    ) {}

    public function show(string $form): View
    {
        $resolved = $this->forms->resolvePublishedForm($form);

        abort_unless($resolved?->isPublished(), 404);

        return view('forms.public.show', [
            'form' => $resolved->loadMissing(['product.organisation']),
            'hostedPageUrl' => $this->forms->hostedPageUrl($resolved),
            'submitUrl' => $this->forms->submitUrl($resolved),
        ]);
    }
}
