<?php

namespace App\Providers;

use App\Http\Controllers\CP\Forms\FocalPointApplicationController as CpFocalPointApplicationController;
use App\Http\Controllers\CP\Forms\LegacySubscriptionSettingsController;
use App\Http\Controllers\CP\Forms\ProductFormController;
use App\Http\Controllers\CP\Forms\ProductFormSubmissionController;
use App\Http\Controllers\CP\Platform\OperatorScopeController;
use App\Http\Controllers\CP\Platform\OrganisationDomainController;
use App\Http\Controllers\CP\Platform\OrganisationProfileController;
use App\Http\Controllers\CP\Platform\ProductBannerController;
use App\Http\Controllers\Public\ProductFormPageController;
use App\Http\Controllers\Public\ProductFormSubmissionController as PublicProductFormSubmissionController;
use App\Http\Middleware\EnforceCpOwnership;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\User;
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
            $nav->content('Subscription Forms')
                ->section('Newsletter')
                ->url(cp_route('subscription-forms.index'))
                ->icon('form');

            if (User::current()?->isSuper()) {
                $nav->content('Application Forms')
                    ->section('Forms')
                    ->url(cp_route('product-forms.index'))
                    ->icon('form');
            }

            $nav->content('Organisation Domains')
                ->section('Forms')
                ->url(cp_route('organisation-domains.index'))
                ->icon('earth');

            if (app(CpOperatorScope::class)->organisationIds(User::current()) !== []) {
                $nav->content('Organisation Profile')
                    ->section('Settings')
                    ->url(cp_route('organisation-profiles.index'))
                    ->icon('earth');
            }

            if (app(CpOperatorScope::class)->productIds(User::current()) !== []) {
                $nav->content('Product Banners')
                    ->section('Settings')
                    ->url(cp_route('product-banners.index'))
                    ->icon('assets');
            }

            if (User::current()?->isSuper()) {
                $nav->content('Operator Access')
                    ->section('Forms')
                    ->url(cp_route('operator-scopes.index'))
                    ->icon('users');
            }
        });
    }

    protected function registerRoutes(): void
    {
        Statamic::pushCpRoutes(function (): void {
            \Route::prefix('focal-point-applications')->name('focal-point-applications.')->group(function (): void {
                \Route::get('/', [CpFocalPointApplicationController::class, 'index'])->name('index');
                \Route::get('/export/csv', [CpFocalPointApplicationController::class, 'export'])->name('export');
                \Route::get('/{applicationId}', [CpFocalPointApplicationController::class, 'show'])->whereNumber('applicationId')->name('show');
                \Route::post('/{applicationId}/status', [CpFocalPointApplicationController::class, 'updateStatus'])->whereNumber('applicationId')->name('status');
            });

            \Route::prefix('subscription-forms')->name('subscription-forms.')->middleware(EnforceCpOwnership::class)->group(function (): void {
                \Route::get('/', [ProductFormController::class, 'index'])->name('index');
                \Route::get('/create', [ProductFormController::class, 'create'])->name('create');
                \Route::post('/', [ProductFormController::class, 'store'])->name('store');
                \Route::get('/legacy/{handle}/edit', [LegacySubscriptionSettingsController::class, 'edit'])->name('legacy.edit');
                \Route::put('/legacy/{handle}', [LegacySubscriptionSettingsController::class, 'update'])->name('legacy.update');
                \Route::get('/{productForm}/edit', [ProductFormController::class, 'edit'])->name('edit');
                \Route::put('/{productForm}', [ProductFormController::class, 'update'])->name('update');
                \Route::get('/{productForm}/submissions', [ProductFormSubmissionController::class, 'index'])->name('submissions.index');
                \Route::get('/{productForm}/submissions/export/csv', [ProductFormSubmissionController::class, 'export'])->name('submissions.export');
            });

            \Route::prefix('product-forms')->name('product-forms.')->middleware(EnforceCpOwnership::class)->group(function (): void {
                \Route::get('/', [ProductFormController::class, 'index'])->name('index');
                \Route::get('/create', [ProductFormController::class, 'create'])->name('create');
                \Route::post('/', [ProductFormController::class, 'store'])->name('store');
                \Route::get('/{productForm}/edit', [ProductFormController::class, 'edit'])->name('edit');
                \Route::put('/{productForm}', [ProductFormController::class, 'update'])->name('update');
                \Route::get('/{productForm}/submissions', [ProductFormSubmissionController::class, 'index'])->name('submissions.index');
                \Route::get('/{productForm}/submissions/export/csv', [ProductFormSubmissionController::class, 'export'])->name('submissions.export');
                \Route::post('/{productForm}/submissions/{submission}/status', [ProductFormSubmissionController::class, 'transition'])->name('submissions.status');
            });

            \Route::prefix('organisation-domains')->name('organisation-domains.')->middleware(EnforceCpOwnership::class)->group(function (): void {
                \Route::get('/', [OrganisationDomainController::class, 'index'])->name('index');
                \Route::post('/{organisation}', [OrganisationDomainController::class, 'update'])->name('update');
                \Route::post('/{organisation}/verify', [OrganisationDomainController::class, 'verify'])->name('verify');
            });

            \Route::prefix('organisation-profiles')->name('organisation-profiles.')->middleware(EnforceCpOwnership::class)->group(function (): void {
                \Route::get('/', [OrganisationProfileController::class, 'index'])->name('index');
                \Route::put('/{organisation}', [OrganisationProfileController::class, 'update'])->name('update');
            });

            \Route::prefix('product-banners')->name('product-banners.')->middleware(EnforceCpOwnership::class)->group(function (): void {
                \Route::get('/', [ProductBannerController::class, 'index'])->name('index');
                \Route::put('/{product}', [ProductBannerController::class, 'update'])->name('update');
                \Route::get('/default', [ProductBannerController::class, 'default'])->name('default');
                \Route::post('/one-time-upload', [ProductBannerController::class, 'uploadOverride'])->middleware('throttle:20,1')->name('upload-override');
            });

            \Route::prefix('operator-scopes')->name('operator-scopes.')->group(function (): void {
                \Route::get('/', [OperatorScopeController::class, 'index'])->name('index');
                \Route::put('/{userId}', [OperatorScopeController::class, 'update'])->name('update');
            });
        });

        \Route::prefix('forms')->name('product-forms.public.')->group(function (): void {
            \Route::get('/{form}', [ProductFormPageController::class, 'show'])->name('show');
            \Route::get('/{form}/schema', [ProductFormPageController::class, 'schema'])->name('schema');
            \Route::post('/{form}', [PublicProductFormSubmissionController::class, 'store'])
                ->withoutMiddleware([VerifyCsrfToken::class])
                ->middleware('throttle:10,1')
                ->name('submit');
        });
    }

    protected function registerCpScripts(): void
    {
        Statamic::inlineScript('window.productBannerDefaultEndpoint = '.json_encode(url('/'.trim(config('statamic.cp.route', 'cp'), '/').'/product-banners/default')).';');
        Statamic::inlineScript('window.productBannerUploadEndpoint = '.json_encode(url('/'.trim(config('statamic.cp.route', 'cp'), '/').'/product-banners/one-time-upload')).';');
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
            updatePreferenceGroups(root);

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
                    updatePreferenceGroups(root);
                });
            }
        });
    }

    function updatePreferenceGroups(root) {
        var organisation = root.querySelector('[data-organisation-select]');
        if (!organisation) return;

        var visibleCount = 0;
        root.querySelectorAll('[data-public-preference-group]').forEach(function (option) {
            var visible = option.dataset.organisationId === organisation.value;
            var input = option.querySelector('input[type="checkbox"]');
            option.hidden = !visible;
            if (input) {
                input.disabled = !visible;
                if (!visible) input.checked = false;
            }
            if (visible) visibleCount++;
        });
        var empty = root.querySelector('[data-public-preferences-empty]');
        if (empty) empty.hidden = visibleCount > 0;
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
            updatePreferenceGroups(root);
        }
    });
})();
JS);
        Statamic::inlineScript(<<<'JS'
(function () {
    var match = window.location.pathname.match(/\/collections\/([^/]+)\/entries\/create(?:\/|$)/);
    var blueprint = new URLSearchParams(window.location.search).get('blueprint');
    if (!match || !blueprint) return;

    var url = new URL(window.productBannerDefaultEndpoint, window.location.origin);
    url.searchParams.set('collection', match[1]);
    url.searchParams.set('blueprint', blueprint);

    fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (!data) return;
            function showPreview() {
                var field = document.querySelector('.assets-fieldtype label[for="field_hero_image"]')?.closest('.assets-fieldtype');
                if (!field || field.querySelector('[data-product-banner-preview]')) return;
                var preview = document.createElement('div');
                preview.className = 'newsletter-hero-default';
                preview.dataset.productBannerPreview = '1';
                var caption = document.createElement('p');
                caption.textContent = data.url
                    ? (data.source === 'product' ? 'Product' : 'Organisation') + ' default for ' + data.product + '.'
                    : 'No default banner is configured for ' + data.product + '.';
                var image = document.createElement('img');
                var override = document.getElementById('field_hero_image_override_url')?.value;
                image.src = override || data.url || '';
                image.alt = 'Hero image for ' + data.product;
                if (!image.src || (!override && !data.url)) image.hidden = true;
                var label = document.createElement('label');
                label.textContent = 'Upload a one-time replacement for this newsletter';
                var upload = document.createElement('input');
                upload.type = 'file';
                upload.accept = 'image/png,image/jpeg,image/webp';
                upload.dataset.heroOverrideUpload = '1';
                label.appendChild(upload);
                var status = document.createElement('small');
                status.setAttribute('role', 'status');
                preview.append(caption, image, label, status);
                field.querySelector('[data-ui-field-text]')?.after(preview);
                upload.addEventListener('change', function () {
                    if (!upload.files.length) return;
                    var file = upload.files[0];
                    if (file.size > 4 * 1024 * 1024) {
                        status.textContent = 'Choose an image under 4 MB.';
                        return;
                    }
                    var form = new FormData();
                    form.append('collection', match[1]);
                    form.append('blueprint', blueprint);
                    form.append('banner', file);
                    if (typeof StatamicConfig !== 'undefined') form.append('_token', StatamicConfig.csrfToken);
                    upload.disabled = true;
                    status.textContent = 'Uploading image...';
                    fetch(window.productBannerUploadEndpoint, { method: 'POST', body: form, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                        .then(function (response) { if (!response.ok) throw new Error('Upload failed. Check the image type and try again.'); return response.json(); })
                        .then(function (result) {
                            var input = document.getElementById('field_hero_image_override_url');
                            if (!input) throw new Error('Image uploaded, but the issue field is unavailable. Copy the image URL before leaving this page: ' + result.url);
                            var setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
                            setter.call(input, result.url);
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            image.src = result.url;
                            image.hidden = false;
                            caption.textContent = 'One-time image selected for this newsletter.';
                            status.textContent = 'Uploaded. Save the newsletter to keep this replacement.';
                        })
                        .catch(function (error) { status.textContent = error.message; })
                        .finally(function () { upload.disabled = false; });
                });
            }
            showPreview();
            var observer = new MutationObserver(showPreview);
            observer.observe(document.getElementById('content-card') || document.body, { childList: true, subtree: true });
        });
})();
JS);
    }
}
