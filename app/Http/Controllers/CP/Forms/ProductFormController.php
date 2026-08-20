<?php

namespace App\Http\Controllers\CP\Forms;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\SubscriberGroup;
use App\Services\Forms\FormTemplateRegistry;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ScopedProductFormProductSelector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductFormController
{
    public function index(Request $request, ScopedProductFormProductSelector $products): View
    {
        $scopedProducts = $products->productsFor($request->user());
        $productIds = $scopedProducts->modelKeys();
        $organisationIds = $scopedProducts
            ->pluck('organisation_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $forms = ProductForm::query()
            ->with(['organisation', 'product'])
            ->withCount('submissions')
            ->where(function ($query) use ($productIds, $organisationIds): void {
                if ($productIds === [] && $organisationIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('product_id', $productIds)
                    ->orWhere(function ($query) use ($organisationIds): void {
                        $query->where('form_scope', 'organisation')
                            ->whereIn('organisation_id', $organisationIds);
                    });
            })
            ->orderBy('name')
            ->get();

        return view('forms.cp.index', compact('forms'));
    }

    public function create(
        Request $request,
        ScopedProductFormProductSelector $products,
        FormTemplateRegistry $templates,
    ): View {
        $scopedProducts = $products->productsFor($request->user());

        return view('forms.cp.create', [
            'products' => $scopedProducts,
            'organisations' => $products->organisationsFor($request->user()),
            'templateFamilies' => $templates->all(),
            'groups' => $this->audienceGroups($scopedProducts->modelKeys()),
            'form' => null,
            'fieldDefinitionsJson' => $this->defaultFieldDefinitionsJson(),
            'allowedOriginsText' => '',
            'allowedProductIds' => [],
        ]);
    }

    public function store(
        Request $request,
        ScopedProductFormProductSelector $products,
        ProductFormService $forms,
    ): RedirectResponse {
        $data = $this->validatedPayload($request);

        if ($data['form_scope'] === 'organisation') {
            $organisation = $products->resolveOrganisation($request->user(), (int) $data['organisation_id']);

            abort_if(! $organisation instanceof Organisation, 403, 'Selected organisation is outside your active form scope.');

            $form = $forms->createForOrganisation($organisation, $data);
        } else {
            $product = $products->resolve($request->user(), (int) $data['product_id']);

            abort_if(! $product instanceof Product, 403, 'Selected product is outside your active form scope.');

            $form = $forms->create($product, $data);
        }

        return redirect(cp_route('product-forms.index'))
            ->with('success', "Hosted form {$form->name} created.");
    }

    public function edit(
        Request $request,
        ProductForm $productForm,
        ScopedProductFormProductSelector $products,
        FormTemplateRegistry $templates,
    ): View {
        abort_if(! $products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $scopedProducts = $products->productsFor($request->user());

        return view('forms.cp.edit', [
            'form' => $productForm,
            'products' => $scopedProducts,
            'organisations' => $products->organisationsFor($request->user()),
            'templateFamilies' => $templates->all(),
            'groups' => $this->audienceGroups($scopedProducts->modelKeys()),
            'fieldDefinitionsJson' => json_encode($productForm->field_definitions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'allowedOriginsText' => implode(PHP_EOL, $productForm->allowed_origins ?? []),
            'allowedProductIds' => $productForm->allowed_product_ids ?? [],
        ]);
    }

    public function update(
        Request $request,
        ProductForm $productForm,
        ScopedProductFormProductSelector $products,
        ProductFormService $forms,
    ): RedirectResponse {
        abort_if(! $products->canAccessForm($request->user(), $productForm), 403, 'Hosted form is outside your active form scope.');

        $data = $this->validatedPayload($request, $productForm);

        if ($data['form_scope'] === 'organisation') {
            $organisation = $products->resolveOrganisation($request->user(), (int) $data['organisation_id']);

            abort_if(! $organisation instanceof Organisation, 403, 'Selected organisation is outside your active form scope.');

            $forms->updateForOrganisation($productForm, $organisation, $data);
        } else {
            $product = $products->resolve($request->user(), (int) $data['product_id']);

            abort_if(! $product instanceof Product, 403, 'Selected product is outside your active form scope.');

            $forms->update($productForm, $product, $data);
        }

        return redirect(cp_route('product-forms.index'))
            ->with('success', "Hosted form {$productForm->name} updated.");
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
            'template_family' => ['required', Rule::in(['application_basic', 'data_collection_basic'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'success_message' => ['required', 'string', 'max:255'],
            'field_definitions_json' => ['required', 'string'],
            'allowed_origins_text' => ['nullable', 'string'],
            'requires_review' => ['nullable', 'boolean'],
            'audience_group_id' => ['nullable', 'integer'],
            'audience_sub_group_id' => ['nullable', 'integer'],
            'custom_extension_key' => ['nullable', 'string', 'max:255'],
            'product_selection_field' => ['nullable', 'string', 'max:255'],
            'allowed_product_ids' => ['nullable', 'array'],
            'allowed_product_ids.*' => ['integer'],
        ]);

        $data['field_definitions'] = $this->decodeFieldDefinitions((string) $data['field_definitions_json']);
        $data['allowed_origins'] = $this->lines((string) ($data['allowed_origins_text'] ?? ''));
        $data['audience_group_id'] = $this->nullableInteger(Arr::get($data, 'audience_group_id'));
        $data['audience_sub_group_id'] = $this->nullableInteger(Arr::get($data, 'audience_sub_group_id'));
        $data['requires_review'] = $request->boolean('requires_review');

        if ($data['form_scope'] === 'organisation') {
            $data['product_id'] = null;
            $data['audience_group_id'] = null;
            $data['audience_sub_group_id'] = null;
        }

        unset($data['field_definitions_json'], $data['allowed_origins_text']);

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
     * @return \Illuminate\Database\Eloquent\Collection<int, SubscriberGroup>
     */
    private function audienceGroups(array $productIds)
    {
        if ($productIds === []) {
            return new \Illuminate\Database\Eloquent\Collection;
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

    private function defaultFieldDefinitionsJson(): string
    {
        return json_encode([
            [
                'handle' => 'full_name',
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'handle' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
            ],
            [
                'handle' => 'message',
                'label' => 'Message',
                'type' => 'textarea',
                'required' => false,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
}
