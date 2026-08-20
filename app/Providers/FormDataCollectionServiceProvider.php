<?php

namespace App\Providers;

use App\Http\Controllers\CP\Forms\ProductFormController;
use App\Http\Controllers\CP\Forms\ProductFormSubmissionController;
use App\Http\Controllers\Public\ProductFormPageController;
use App\Http\Controllers\Public\ProductFormSubmissionController as PublicProductFormSubmissionController;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Statamic;

class FormDataCollectionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerCpNav();
        $this->registerRoutes();
        $this->registerCpScripts();
    }

    protected function registerCpNav(): void
    {
        Nav::extend(function ($nav): void {
            $nav->content('Hosted Forms')
                ->section('Forms')
                ->url(cp_route('product-forms.index'))
                ->icon('form');
        });
    }

    protected function registerRoutes(): void
    {
        Statamic::pushCpRoutes(function (): void {
            \Route::prefix('product-forms')->name('product-forms.')->group(function (): void {
                \Route::get('/', [ProductFormController::class, 'index'])->name('index');
                \Route::get('/create', [ProductFormController::class, 'create'])->name('create');
                \Route::post('/', [ProductFormController::class, 'store'])->name('store');
                \Route::get('/{productForm}/edit', [ProductFormController::class, 'edit'])->name('edit');
                \Route::put('/{productForm}', [ProductFormController::class, 'update'])->name('update');
                \Route::get('/{productForm}/submissions', [ProductFormSubmissionController::class, 'index'])->name('submissions.index');
                \Route::get('/{productForm}/submissions/export/csv', [ProductFormSubmissionController::class, 'export'])->name('submissions.export');
            });
        });

        \Route::prefix('forms')->name('product-forms.public.')->group(function (): void {
            \Route::get('/{form}', [ProductFormPageController::class, 'show'])->name('show');
            \Route::post('/{form}', [PublicProductFormSubmissionController::class, 'store'])
                ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
                ->middleware('throttle:10,1')
                ->name('submit');
        });
    }

    protected function registerCpScripts(): void
    {
        Statamic::inlineScript(<<<'JS'
(function () {
    function captureOptions(root, select, key) {
        if (!select || root[key]) {
            return;
        }

        root[key] = Array.prototype.map.call(select.options, function (option) {
            return {
                value: option.value,
                label: option.textContent,
                organisationId: option.dataset.organisationId,
                selected: option.selected,
            };
        });
    }

    function replaceOptions(root, select, key, organisationId, chooseFirstVisible) {
        if (!select) {
            return;
        }

        captureOptions(root, select, key);

        var currentValues = Array.prototype
            .filter.call(select.options, function (option) {
                return option.selected;
            })
            .map(function (option) {
                return option.value;
            });
        var selectedValues = currentValues.length
            ? currentValues
            : root[key].filter(function (option) {
                return option.selected;
            }).map(function (option) {
                return option.value;
            });
        var options = root[key].filter(function (option) {
            return option.organisationId === organisationId;
        });
        var selectedValueIsAvailable = options.some(function (option) {
            return selectedValues.indexOf(option.value) !== -1;
        });

        select.innerHTML = '';

        options.forEach(function (sourceOption, index) {
            var option = document.createElement('option');

            option.value = sourceOption.value;
            option.textContent = sourceOption.label;
            option.dataset.organisationId = sourceOption.organisationId;
            option.selected = selectedValues.indexOf(sourceOption.value) !== -1
                || (chooseFirstVisible && !selectedValueIsAvailable && index === 0);

            select.appendChild(option);
        });
    }

    function updateProductScopeControls(root) {
        var scope = root.querySelector('[data-form-scope]');
        var organisation = root.querySelector('[data-organisation-select]');
        var productField = root.querySelector('[data-product-scope-field]');
        var organisationFields = root.querySelector('[data-organisation-scope-fields]');
        var productSelect = root.querySelector('[data-product-select]');
        var allowedSelect = root.querySelector('[data-allowed-products-select]');

        if (!scope || !organisation || !productSelect || !allowedSelect) {
            return;
        }

        var isOrganisationScope = scope.value === 'organisation';
        var organisationId = organisation.value;

        if (productField) {
            productField.hidden = isOrganisationScope;
        }

        if (organisationFields) {
            organisationFields.hidden = !isOrganisationScope;
        }

        productSelect.disabled = isOrganisationScope;
        allowedSelect.disabled = !isOrganisationScope;

        replaceOptions(root, productSelect, '__productFormProductOptions', organisationId, true);
        replaceOptions(root, allowedSelect, '__productFormAllowedProductOptions', organisationId, false);
    }

    function bootProductScopeControls() {
        var forms = document.querySelectorAll('[data-product-form-scope-controls]');

        Array.prototype.forEach.call(forms, function (root) {
            var scope = root.querySelector('[data-form-scope]');
            var organisation = root.querySelector('[data-organisation-select]');

            updateProductScopeControls(root);

            if (scope && !scope.dataset.productScopeListenerBound) {
                scope.dataset.productScopeListenerBound = '1';
                scope.addEventListener('change', function () {
                    updateProductScopeControls(root);
                });
            }

            if (organisation && !organisation.dataset.productScopeListenerBound) {
                organisation.dataset.productScopeListenerBound = '1';
                organisation.addEventListener('change', function () {
                    updateProductScopeControls(root);
                });
            }
        });
    }

    function startProductScopeControls() {
        bootProductScopeControls();

        window.setTimeout(bootProductScopeControls, 250);
        window.setTimeout(bootProductScopeControls, 1000);
        window.setTimeout(bootProductScopeControls, 2000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startProductScopeControls);
    } else {
        startProductScopeControls();
    }

    document.addEventListener('statamic:navigated', startProductScopeControls);
    document.addEventListener('change', function (event) {
        if (!event.target.matches('[data-form-scope], [data-organisation-select]')) {
            return;
        }

        var root = event.target.closest('[data-product-form-scope-controls]');

        if (root) {
            updateProductScopeControls(root);
        }
    });
})();
JS);
    }
}
