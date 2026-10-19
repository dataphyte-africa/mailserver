<?php

namespace App\Http\Controllers\CP\Forms;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\SubscriberGroup;
use App\Services\Forms\FormTemplateRegistry;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use App\Services\Forms\ScopedProductFormProductSelector;
use App\Services\Foundation\FocalPointApplicationContent;
use App\Services\Newsletter\SubscriptionFormService;
use App\Services\Platform\CpOperatorScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Form as StatamicForm;

class ProductFormController
{
    public function index(Request $request, ScopedProductFormProductSelector $products, ProductFormService $formService, SubscriptionFormService $legacyService, FocalPointApplicationContent $focalContent): View|RedirectResponse
    {
        $subscriptionWorkspace = $this->isSubscriptionWorkspace($request);
        $isSuper = app(CpOperatorScope::class)->isSuper($request->user());
        if (! $subscriptionWorkspace && ! $isSuper) {
            return redirect(cp_route('subscription-forms.index'));
        }

        $organisationHandles = $products->organisationsFor($request->user())->pluck('primary_collection_handle')->all();
        $legacyForms = StatamicForm::all()
            ->filter(fn ($form) => $legacyService->isNewsletterForm($form)
                && ($legacyService->submissionMode($form) === 'subscription') === $subscriptionWorkspace
                && ($isSuper || in_array($legacyService->collectionHandle($form), $organisationHandles, true)))
            ->map(fn ($form): array => [
                'name' => Organisation::query()
                    ->where('primary_collection_handle', $legacyService->collectionHandle($form))
                    ->value('name') ?: Str::headline($form->title()),
                'handle' => $form->handle(),
                'collection' => $legacyService->collectionHandle($form),
                'url' => $legacyService->publicFormPageUrl($form),
            ])->values();

        $bespokeForms = $subscriptionWorkspace ? collect() : collect([
            [
                'name' => '2027 State Focal Points',
                'description' => 'Foundation recruitment application with state, LGA and ward lookup, private CV and certificate uploads.',
                'url' => route('focal-points.applications.show'),
                'submissions_url' => cp_route('focal-point-applications.index'),
                'manage_url' => cp_route('globals.variables.edit', 'focal_point_application'),
                'terms_approved' => $focalContent->torApproved(),
                'can_approve_terms' => $focalContent->canApprove(),
            ],
            [
                'name' => 'Observer Violations',
                'description' => 'Osun election incident reports with polling-unit lookup and private evidence uploads.',
                'url' => route('observer-violations.index'),
            ],
        ]);

        if ($isSuper) {
            $forms = ProductForm::query()->whereIn('mode', $subscriptionWorkspace ? ['subscription'] : ['application', 'data_collection'])
                ->with(['organisation', 'product'])->withCount('submissions')->orderBy('name')->get();

            return view('forms.cp.index', compact('forms', 'legacyForms', 'bespokeForms', 'formService', 'subscriptionWorkspace'));
        }

        $scopedProducts = $products->productsFor($request->user());
        $productIds = $scopedProducts->modelKeys();
        $organisationIds = $products->organisationsFor($request->user())->modelKeys();

        $forms = ProductForm::query()
            ->where('mode', 'subscription')
            ->with(['organisation', 'product'])
            ->withCount('submissions')
            ->where(function ($query) use ($productIds, $organisationIds): void {
                if ($productIds === [] && $organisationIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($query) use ($productIds): void {
                    $query->where('form_scope', 'product')->whereIn('product_id', $productIds)
                        ->whereHas('product', fn ($product) => $product->whereColumn('products.organisation_id', 'product_forms.organisation_id'));
                })
                    ->orWhere(function ($query) use ($organisationIds): void {
                        $query->where('form_scope', 'organisation')
                            ->whereIn('organisation_id', $organisationIds);
                    });
            })
            ->orderBy('name')
            ->get();

        return view('forms.cp.index', compact('forms', 'legacyForms', 'bespokeForms', 'formService', 'subscriptionWorkspace'));
    }

    public function create(
        Request $request,
        ScopedProductFormProductSelector $products,
        FormTemplateRegistry $templates,
        ProductFormSubscriptionService $subscriptions,
    ): View|RedirectResponse {
        $subscriptionWorkspace = $this->isSubscriptionWorkspace($request);
        if (! $subscriptionWorkspace && ! app(CpOperatorScope::class)->isSuper($request->user())) {
            return redirect(cp_route('subscription-forms.create'));
        }
        $this->assertWorkspaceAccess($request);
        $scopedProducts = $products->productsFor($request->user());

        $organisations = $products->organisationsFor($request->user());

        return view('forms.cp.create', [
            'products' => $scopedProducts,
            'organisations' => $organisations,
            'templateFamilies' => $this->workspaceTemplates($templates, $subscriptionWorkspace),
            'groups' => $this->audienceGroups($scopedProducts->modelKeys()),
            'publicPreferenceGroups' => $subscriptionWorkspace
                ? $subscriptions->availablePreferencesForOrganisations($organisations->modelKeys()) : collect(),
            'form' => null,
            'fieldDefinitionsJson' => $this->defaultFieldDefinitionsJson($subscriptionWorkspace),
            'allowedOriginsText' => '',
            'allowedProductIds' => [],
            'subscriptionWorkspace' => $subscriptionWorkspace,
        ]);
    }

    public function store(
        Request $request,
        ScopedProductFormProductSelector $products,
        ProductFormService $forms,
    ): RedirectResponse {
        $this->assertWorkspaceAccess($request);
        $data = $this->validatedPayload($request);
        $this->assertTemplateWorkspace($request, $data['template_family']);
        abort_if($this->isSubscriptionWorkspace($request) && $data['form_scope'] !== 'organisation', 422);

        if ($data['form_scope'] === 'organisation') {
            $organisation = $products->resolveOrganisation($request->user(), (int) $data['organisation_id']);

            abort_if(! $organisation instanceof Organisation, 403, 'Selected organisation is outside your active form scope.');

            $form = $forms->createForOrganisation($organisation, $data);
        } else {
            $product = $products->resolve($request->user(), (int) $data['product_id']);

            abort_if(! $product instanceof Product, 403, 'Selected product is outside your active form scope.');

            $form = $forms->create($product, $data);
        }

        return redirect(cp_route($this->workspaceRoute($request, 'index')))
            ->with('success', "Form {$form->name} created.");
    }

    public function edit(
        Request $request,
        ProductForm $productForm,
        ScopedProductFormProductSelector $products,
        FormTemplateRegistry $templates,
        ProductFormSubscriptionService $subscriptions,
    ): View {
        $subscriptionWorkspace = $this->isSubscriptionWorkspace($request);
        $this->assertWorkspaceAccess($request, $productForm);
        abort_if(! $products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $scopedProducts = $products->productsFor($request->user());

        $organisations = $products->organisationsFor($request->user());

        return view('forms.cp.edit', [
            'form' => $productForm,
            'products' => $scopedProducts,
            'organisations' => $organisations,
            'templateFamilies' => $this->workspaceTemplates($templates, $subscriptionWorkspace),
            'groups' => $this->audienceGroups($scopedProducts->modelKeys()),
            'publicPreferenceGroups' => $subscriptionWorkspace
                ? $subscriptions->availablePreferencesForOrganisations($organisations->modelKeys()) : collect(),
            'fieldDefinitionsJson' => json_encode($productForm->field_definitions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'allowedOriginsText' => implode(PHP_EOL, $productForm->allowed_origins ?? []),
            'allowedProductIds' => $productForm->allowed_product_ids ?? [],
            'subscriptionWorkspace' => $subscriptionWorkspace,
        ]);
    }

    public function update(
        Request $request,
        ProductForm $productForm,
        ScopedProductFormProductSelector $products,
        ProductFormService $forms,
    ): RedirectResponse {
        $this->assertWorkspaceAccess($request, $productForm);
        abort_if(! $products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $data = $this->validatedPayload($request, $productForm);
        $this->assertTemplateWorkspace($request, $data['template_family']);
        abort_if($this->isSubscriptionWorkspace($request)
            && $data['form_scope'] !== ($productForm->form_scope === 'product' ? 'product' : 'organisation'), 422);

        if ($data['form_scope'] === 'organisation') {
            $organisation = $products->resolveOrganisation($request->user(), (int) $data['organisation_id']);

            abort_if(! $organisation instanceof Organisation, 403, 'Selected organisation is outside your active form scope.');

            $forms->updateForOrganisation($productForm, $organisation, $data);
        } else {
            $product = $products->resolve($request->user(), (int) $data['product_id']);

            abort_if(! $product instanceof Product, 403, 'Selected product is outside your active form scope.');

            $forms->update($productForm, $product, $data);
        }

        return redirect(cp_route($this->workspaceRoute($request, 'index')))
            ->with('success', "Form {$productForm->name} updated.");
    }

    private function isSubscriptionWorkspace(Request $request): bool
    {
        return str_contains((string) $request->route()?->getName(), '.subscription-forms.');
    }

    private function workspaceRoute(Request $request, string $action): string
    {
        return ($this->isSubscriptionWorkspace($request) ? 'subscription-forms.' : 'product-forms.').$action;
    }

    private function assertWorkspaceAccess(Request $request, ?ProductForm $form = null): void
    {
        $subscriptionWorkspace = $this->isSubscriptionWorkspace($request);
        abort_if(! $subscriptionWorkspace && ! app(CpOperatorScope::class)->isSuper($request->user()), 403);
        abort_if($form && ($form->mode === 'subscription') !== $subscriptionWorkspace, 403);
    }

    private function assertTemplateWorkspace(Request $request, string $template): void
    {
        abort_if(($template === 'subscription_basic') !== $this->isSubscriptionWorkspace($request), 422);
    }

    private function workspaceTemplates(FormTemplateRegistry $templates, bool $subscriptionWorkspace): array
    {
        return array_filter($templates->all(), fn ($definition) => ($definition['mode'] === 'subscription') === $subscriptionWorkspace);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?ProductForm $form = null): array
    {
        $data = $request->validate([
            'form_scope' => ['required', Rule::in(['product', 'organisation'])],
            'organisation_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'required_if:form_scope,product', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_forms', 'slug')->ignore($form?->getKey()),
            ],
            'template_family' => ['required', Rule::in(['application_basic', 'data_collection_basic', 'subscription_basic'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'accepting_submissions' => ['nullable', 'boolean'],
            'closes_at' => ['nullable', 'date'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'success_message' => ['required', 'string', 'max:255'],
            'welcome_subject' => ['nullable', 'string', 'max:255'],
            'welcome_body' => ['nullable', 'string', 'max:5000'],
            'resubscribe_subject' => ['nullable', 'string', 'max:255'],
            'resubscribe_body' => ['nullable', 'string', 'max:5000'],
            'send_update_email' => ['nullable', 'boolean'],
            'update_subject' => ['nullable', 'string', 'max:255'],
            'update_body' => ['nullable', 'string', 'max:5000'],
            'show_banner' => ['nullable', 'boolean'],
            'include_country' => ['nullable', 'boolean'],
            'include_profession' => ['nullable', 'boolean'],
            'include_gender' => ['nullable', 'boolean'],
            'field_definitions_json' => ['required', 'string'],
            'allowed_origins_text' => ['nullable', 'string'],
            'requires_review' => ['nullable', 'boolean'],
            'audience_group_id' => ['nullable', 'integer'],
            'audience_sub_group_id' => ['nullable', 'integer'],
            'custom_extension_key' => ['nullable', 'string', 'max:255'],
            'product_selection_field' => ['nullable', 'string', 'max:255'],
            'allowed_product_ids' => ['nullable', 'array'],
            'allowed_product_ids.*' => ['integer'],
            'public_preference_group_ids' => ['nullable', 'array'],
            'public_preference_group_ids.*' => ['integer', 'distinct'],
        ]);

        $data['field_definitions'] = $this->decodeFieldDefinitions((string) $data['field_definitions_json']);
        if ($data['template_family'] === 'subscription_basic') {
            $data['custom_extension_key'] = null;
            $optionalFields = [
                'country' => ['label' => 'Country', 'type' => 'country'],
                'profession' => ['label' => 'Profession', 'type' => 'text'],
                'gender' => ['label' => 'Gender', 'type' => 'select', 'options' => [
                    ['label' => 'Male', 'value' => 'male'],
                    ['label' => 'Female', 'value' => 'female'],
                    ['label' => 'Other', 'value' => 'other'],
                ]],
            ];
            $data['field_definitions'] = array_values(array_filter(
                $data['field_definitions'],
                fn (array $field): bool => ! array_key_exists((string) ($field['handle'] ?? ''), $optionalFields),
            ));
            foreach ($optionalFields as $handle => $definition) {
                if ($request->boolean('include_'.$handle)) {
                    $data['field_definitions'][] = ['handle' => $handle, 'required' => false, ...$definition];
                }
            }
        }
        $data['settings'] = array_merge($form?->settings ?? [], [
            'show_banner' => $request->boolean('show_banner'),
            'welcome_subject' => trim((string) ($data['welcome_subject'] ?? '')),
            'welcome_body' => trim((string) ($data['welcome_body'] ?? '')),
            'resubscribe_subject' => trim((string) ($data['resubscribe_subject'] ?? '')),
            'resubscribe_body' => trim((string) ($data['resubscribe_body'] ?? '')),
            'send_update_email' => $request->boolean('send_update_email'),
            'update_subject' => trim((string) ($data['update_subject'] ?? '')),
            'update_body' => trim((string) ($data['update_body'] ?? '')),
        ]);
        if (! $this->isSubscriptionWorkspace($request)) {
            $data['settings']['accepting_submissions'] = $request->boolean('accepting_submissions');
            $data['settings']['closes_at'] = filled($data['closes_at'] ?? null)
                ? CarbonImmutable::parse($data['closes_at'], 'Africa/Lagos')->format('Y-m-d H:i:s') : null;
        }
        $data['allowed_origins'] = $this->lines((string) ($data['allowed_origins_text'] ?? ''));
        $data['audience_group_id'] = $this->nullableInteger(Arr::get($data, 'audience_group_id'));
        $data['audience_sub_group_id'] = $this->nullableInteger(Arr::get($data, 'audience_sub_group_id'));
        $data['requires_review'] = $request->boolean('requires_review');

        if ($data['form_scope'] === 'organisation') {
            $data['product_id'] = null;
            $data['audience_group_id'] = null;
            $data['audience_sub_group_id'] = null;
        }

        unset($data['field_definitions_json'], $data['allowed_origins_text'], $data['welcome_subject'], $data['welcome_body'], $data['resubscribe_subject'], $data['resubscribe_body'], $data['send_update_email'], $data['update_subject'], $data['update_body'], $data['show_banner'], $data['include_country'], $data['include_profession'], $data['include_gender'], $data['accepting_submissions'], $data['closes_at']);

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeFieldDefinitions(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'field_definitions_json' => ['Field definitions must be valid JSON.'],
            ]);
        }

        return $decoded;
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $value): array
    {
        return collect(preg_split('/\R/', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function nullableInteger(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    /**
     * @param  array<int, int>  $productIds
     * @return Collection<int, SubscriberGroup>
     */
    private function audienceGroups(array $productIds)
    {
        if ($productIds === []) {
            return new Collection;
        }

        return SubscriberGroup::query()
            ->with([
                'product',
                'subGroups' => fn ($query) => $query->whereNull('archived_at')->orderBy('name'),
            ])
            ->whereIn('product_id', $productIds)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
    }

    private function defaultFieldDefinitionsJson(bool $subscriptionWorkspace = false): string
    {
        $fields = [
            [
                'handle' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
            ],
        ];

        if (! $subscriptionWorkspace) {
            array_unshift($fields, [
                'handle' => 'full_name',
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
            ]);
            $fields[] = [
                'handle' => 'message',
                'label' => 'Message',
                'type' => 'textarea',
                'required' => false,
            ];
        }

        return json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
}
