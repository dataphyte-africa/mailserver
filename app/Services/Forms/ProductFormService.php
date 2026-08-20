<?php

namespace App\Services\Forms;

use App\Contracts\Domain\DomainResolverInterface;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\ProductFormSubmission;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductFormService
{
    public function __construct(
        private readonly DomainResolverInterface $domains,
        private readonly FormTemplateRegistry $templates,
    ) {}

    public function create(Product $product, array $attributes): ProductForm
    {
        return $this->createForProduct($product, $attributes);
    }

    public function createForProduct(Product $product, array $attributes): ProductForm
    {
        $definition = $this->templates->definition((string) ($attributes['template_family'] ?? ''));
        $organisation = $product->organisation;
        $name = trim((string) ($attributes['name'] ?? ''));
        $slug = Str::slug((string) ($attributes['slug'] ?? $attributes['name'] ?? ''));

        if (! $organisation) {
            throw ValidationException::withMessages([
                'product' => ['The selected product must belong to a persisted organisation.'],
            ]);
        }

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages([
                'name' => ['The form name and slug are required.'],
            ]);
        }

        $fieldDefinitions = $this->normalizeFieldDefinitions($attributes['field_definitions'] ?? []);
        $audience = $this->resolveAudienceAssignments(
            $product,
            Arr::get($attributes, 'audience_group_id'),
            Arr::get($attributes, 'audience_sub_group_id'),
        );

        return ProductForm::query()->create([
            'organisation_id' => $organisation->getKey(),
            'product_id' => $product->getKey(),
            'form_scope' => 'product',
            'product_selection_field' => null,
            'allowed_product_ids' => null,
            'name' => $name,
            'slug' => $slug,
            'mode' => $definition['mode'],
            'template_family' => (string) ($attributes['template_family'] ?? ''),
            'status' => (string) ($attributes['status'] ?? 'published'),
            'headline' => $this->nullableString($attributes['headline'] ?? null),
            'description' => $this->nullableString($attributes['description'] ?? null),
            'success_message' => (string) ($attributes['success_message'] ?? 'Submission received.'),
            'field_definitions' => $fieldDefinitions,
            'allowed_origins' => $this->normalizeAllowedOrigins($attributes['allowed_origins'] ?? []),
            'settings' => is_array($attributes['settings'] ?? null) ? $attributes['settings'] : [],
            'requires_review' => array_key_exists('requires_review', $attributes)
                ? (bool) $attributes['requires_review']
                : (bool) $definition['requires_review'],
            'audience_group_id' => $audience['group_id'],
            'audience_sub_group_id' => $audience['sub_group_id'],
            'custom_extension_key' => $this->nullableString($attributes['custom_extension_key'] ?? null),
        ]);
    }

    public function createForOrganisation(Organisation $organisation, array $attributes): ProductForm
    {
        $definition = $this->templates->definition((string) ($attributes['template_family'] ?? ''));
        $name = trim((string) ($attributes['name'] ?? ''));
        $slug = Str::slug((string) ($attributes['slug'] ?? $attributes['name'] ?? ''));

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages([
                'name' => ['The form name and slug are required.'],
            ]);
        }

        $fieldDefinitions = $this->normalizeFieldDefinitions($attributes['field_definitions'] ?? []);
        $routing = $this->resolveOrganisationProductRouting($organisation, $attributes, $fieldDefinitions);

        return ProductForm::query()->create([
            'organisation_id' => $organisation->getKey(),
            'product_id' => null,
            'form_scope' => 'organisation',
            'product_selection_field' => $routing['field'],
            'allowed_product_ids' => $routing['product_ids'],
            'name' => $name,
            'slug' => $slug,
            'mode' => $definition['mode'],
            'template_family' => (string) ($attributes['template_family'] ?? ''),
            'status' => (string) ($attributes['status'] ?? 'published'),
            'headline' => $this->nullableString($attributes['headline'] ?? null),
            'description' => $this->nullableString($attributes['description'] ?? null),
            'success_message' => (string) ($attributes['success_message'] ?? 'Submission received.'),
            'field_definitions' => $fieldDefinitions,
            'allowed_origins' => $this->normalizeAllowedOrigins($attributes['allowed_origins'] ?? []),
            'settings' => is_array($attributes['settings'] ?? null) ? $attributes['settings'] : [],
            'requires_review' => array_key_exists('requires_review', $attributes)
                ? (bool) $attributes['requires_review']
                : (bool) $definition['requires_review'],
            'audience_group_id' => null,
            'audience_sub_group_id' => null,
            'custom_extension_key' => $this->nullableString($attributes['custom_extension_key'] ?? null),
        ]);
    }

    public function update(ProductForm $form, Product $product, array $attributes): ProductForm
    {
        return $this->updateForProduct($form, $product, $attributes);
    }

    public function updateForProduct(ProductForm $form, Product $product, array $attributes): ProductForm
    {
        $definition = $this->templates->definition((string) ($attributes['template_family'] ?? $form->template_family));
        $organisation = $product->organisation;
        $name = trim((string) ($attributes['name'] ?? $form->name));
        $slug = Str::slug((string) ($attributes['slug'] ?? $form->slug));

        if (! $organisation) {
            throw ValidationException::withMessages([
                'product' => ['The selected product must belong to a persisted organisation.'],
            ]);
        }

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages([
                'name' => ['The form name and slug are required.'],
            ]);
        }

        $fieldDefinitions = $this->normalizeFieldDefinitions($attributes['field_definitions'] ?? $form->field_definitions ?? []);
        $audience = $this->resolveAudienceAssignments(
            $product,
            Arr::get($attributes, 'audience_group_id'),
            Arr::get($attributes, 'audience_sub_group_id'),
        );

        $form->forceFill([
            'organisation_id' => $organisation->getKey(),
            'product_id' => $product->getKey(),
            'form_scope' => 'product',
            'product_selection_field' => null,
            'allowed_product_ids' => null,
            'name' => $name,
            'slug' => $slug,
            'mode' => $definition['mode'],
            'template_family' => (string) ($attributes['template_family'] ?? $form->template_family),
            'status' => (string) ($attributes['status'] ?? $form->status),
            'headline' => $this->nullableString($attributes['headline'] ?? null),
            'description' => $this->nullableString($attributes['description'] ?? null),
            'success_message' => (string) ($attributes['success_message'] ?? $form->success_message),
            'field_definitions' => $fieldDefinitions,
            'allowed_origins' => $this->normalizeAllowedOrigins($attributes['allowed_origins'] ?? []),
            'settings' => is_array($attributes['settings'] ?? null) ? $attributes['settings'] : ($form->settings ?? []),
            'requires_review' => array_key_exists('requires_review', $attributes)
                ? (bool) $attributes['requires_review']
                : (bool) $definition['requires_review'],
            'audience_group_id' => $audience['group_id'],
            'audience_sub_group_id' => $audience['sub_group_id'],
            'custom_extension_key' => $this->nullableString($attributes['custom_extension_key'] ?? null),
        ])->save();

        return $form->refresh();
    }

    public function updateForOrganisation(ProductForm $form, Organisation $organisation, array $attributes): ProductForm
    {
        $definition = $this->templates->definition((string) ($attributes['template_family'] ?? $form->template_family));
        $name = trim((string) ($attributes['name'] ?? $form->name));
        $slug = Str::slug((string) ($attributes['slug'] ?? $form->slug));

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages([
                'name' => ['The form name and slug are required.'],
            ]);
        }

        $fieldDefinitions = $this->normalizeFieldDefinitions($attributes['field_definitions'] ?? $form->field_definitions ?? []);
        $routing = $this->resolveOrganisationProductRouting($organisation, $attributes, $fieldDefinitions);

        $form->forceFill([
            'organisation_id' => $organisation->getKey(),
            'product_id' => null,
            'form_scope' => 'organisation',
            'product_selection_field' => $routing['field'],
            'allowed_product_ids' => $routing['product_ids'],
            'name' => $name,
            'slug' => $slug,
            'mode' => $definition['mode'],
            'template_family' => (string) ($attributes['template_family'] ?? $form->template_family),
            'status' => (string) ($attributes['status'] ?? $form->status),
            'headline' => $this->nullableString($attributes['headline'] ?? null),
            'description' => $this->nullableString($attributes['description'] ?? null),
            'success_message' => (string) ($attributes['success_message'] ?? $form->success_message),
            'field_definitions' => $fieldDefinitions,
            'allowed_origins' => $this->normalizeAllowedOrigins($attributes['allowed_origins'] ?? []),
            'settings' => is_array($attributes['settings'] ?? null) ? $attributes['settings'] : ($form->settings ?? []),
            'requires_review' => array_key_exists('requires_review', $attributes)
                ? (bool) $attributes['requires_review']
                : (bool) $definition['requires_review'],
            'audience_group_id' => null,
            'audience_sub_group_id' => null,
            'custom_extension_key' => $this->nullableString($attributes['custom_extension_key'] ?? null),
        ])->save();

        return $form->refresh();
    }

    public function resolvePublishedForm(string $slug): ?ProductForm
    {
        return ProductForm::query()
            ->with(['product.organisation'])
            ->where('slug', trim($slug))
            ->where('status', 'published')
            ->first();
    }

    public function hostedPageUrl(ProductForm $form): string
    {
        return $this->publicRouteUrl($form, 'product-forms.public.show', 'form_page');
    }

    public function submitUrl(ProductForm $form): string
    {
        return $this->publicRouteUrl($form, 'product-forms.public.submit', 'form_submit_endpoint');
    }

    /**
     * @return array<int, string>
     */
    public function allowedOrigins(ProductForm $form): array
    {
        $origins = [];

        foreach ([$this->hostedPageUrl($form), $this->submitUrl($form)] as $url) {
            $origin = $this->originFromUrl($url);

            if ($origin !== null) {
                $origins[] = $origin;
            }
        }

        foreach ($form->allowed_origins ?? [] as $origin) {
            $normalized = $this->normalizeOrigin(is_string($origin) ? $origin : null);

            if ($normalized !== null) {
                $origins[] = $normalized;
            }
        }

        return array_values(array_unique($origins));
    }

    public function assertAllowedOrigin(ProductForm $form, Request $request): void
    {
        $origin = $this->normalizeOrigin($request->headers->get('Origin'));

        if ($origin === null) {
            return;
        }

        if (! in_array($origin, $this->allowedOrigins($form), true)) {
            throw new HttpException(403, 'This origin is not allowed for the selected form.');
        }
    }

    public function storeSubmission(ProductForm $form, array $payload, Request $request): ProductFormSubmission
    {
        $this->assertAllowedOrigin($form, $request);

        $validator = Validator::make($payload, $this->submissionRules($form));
        $validated = $validator->validate();

        return ProductFormSubmission::query()->create([
            'product_form_id' => $form->getKey(),
            'organisation_id' => $form->organisation_id,
            'product_id' => $this->resolveSubmissionProduct($form, $validated)->getKey(),
            'status' => 'submitted',
            'payload' => $this->submissionPayload($form, $validated),
            'submission_origin' => $this->normalizeOrigin($request->headers->get('Origin')),
            'submitted_at' => now(),
            'ip_address' => (string) ($request->ip() ?? ''),
            'user_agent' => Str::limit((string) ($request->userAgent() ?? ''), 255, ''),
            'metadata' => [
                'mode' => $form->mode,
                'form_scope' => $form->form_scope,
                'requires_review' => $form->requires_review,
            ],
        ]);
    }

    public function submissions(ProductForm $form, int $perPage = 25): LengthAwarePaginator
    {
        return $form->submissions()
            ->latest('submitted_at')
            ->paginate($perPage);
    }

    public function csvContent(ProductForm $form): string
    {
        $headers = array_merge(
            ['submission_id', 'status', 'submitted_at', 'submission_origin'],
            array_map(
                fn (array $field): string => (string) $field['handle'],
                $form->field_definitions ?? [],
            ),
        );

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headers);

        foreach ($form->submissions()->latest('submitted_at')->get() as $submission) {
            $row = [
                $submission->getKey(),
                $submission->status,
                optional($submission->submitted_at)->toIso8601String(),
                $submission->submission_origin,
            ];

            foreach ($form->field_definitions ?? [] as $field) {
                $row[] = Arr::get($submission->payload ?? [], $field['handle'], '');
            }

            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveAudienceAssignments(Product $product, mixed $groupId, mixed $subGroupId): array
    {
        if (! filled($groupId) && ! filled($subGroupId)) {
            return ['group_id' => null, 'sub_group_id' => null];
        }

        $group = null;

        if (filled($groupId)) {
            $group = SubscriberGroup::query()->find($groupId);

            if (! $group || $group->isArchived() || ! $group->product_id || (int) $group->product_id !== (int) $product->getKey()) {
            throw ValidationException::withMessages([
                'audience_group_id' => ['Forms may only assign active product-owned audience groups.'],
            ]);
        }
        }

        if (! filled($subGroupId)) {
            return ['group_id' => $group?->getKey(), 'sub_group_id' => null];
        }

        $subGroup = SubscriberSubGroup::query()->with('group')->find($subGroupId);

        if (! $subGroup || $subGroup->isArchived() || ! $subGroup->group || $subGroup->group->isArchived()) {
            throw ValidationException::withMessages([
                'audience_sub_group_id' => ['Forms may only assign active audience sub-groups with active parent groups.'],
            ]);
        }

        if (! $subGroup->group->product_id || (int) $subGroup->group->product_id !== (int) $product->getKey()) {
            throw ValidationException::withMessages([
                'audience_sub_group_id' => ['Forms may only assign sub-groups that belong to the selected product.'],
            ]);
        }

        if ($group && (int) $group->getKey() !== (int) $subGroup->subscriber_group_id) {
            throw ValidationException::withMessages([
                'audience_sub_group_id' => ['The selected audience sub-group must belong to the selected audience group.'],
            ]);
        }

        return [
            'group_id' => $subGroup->subscriber_group_id,
            'sub_group_id' => $subGroup->getKey(),
        ];
    }

    /**
     * @param  mixed  $fieldDefinitions
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeFieldDefinitions(mixed $fieldDefinitions): array
    {
        if (! is_array($fieldDefinitions) || $fieldDefinitions === []) {
                throw ValidationException::withMessages([
                    'field_definitions' => ['At least one field definition is required.'],
                ]);
            }

        $normalized = [];
        $seenHandles = [];

        foreach ($fieldDefinitions as $index => $field) {
            if (! is_array($field)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}" => ['Each field definition must be an object-like array.'],
                ]);
            }

            $handle = Str::snake((string) ($field['handle'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            $type = trim((string) ($field['type'] ?? 'text'));

            if ($handle === '' || $label === '') {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}" => ['Each field definition requires a handle and label.'],
                ]);
            }

            if (in_array($handle, $seenHandles, true)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.handle" => ['Field handles must be unique per form.'],
                ]);
            }

            if (! in_array($type, ['text', 'email', 'textarea', 'select'], true)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.type" => ['Unsupported field type for this baseline.'],
                ]);
            }

            $options = [];

            if ($type === 'select') {
                $options = collect($field['options'] ?? [])
                    ->map(function ($option): array {
                        if (is_string($option)) {
                            return [
                                'label' => $option,
                                'value' => Str::slug($option),
                            ];
                        }

                        return [
                            'label' => trim((string) ($option['label'] ?? '')),
                            'value' => trim((string) ($option['value'] ?? '')),
                        ];
                    })
                    ->filter(fn (array $option): bool => $option['label'] !== '' && $option['value'] !== '')
                    ->values()
                    ->all();

                if ($options === []) {
                    throw ValidationException::withMessages([
                        "field_definitions.{$index}.options" => ['Select fields require at least one option.'],
                    ]);
                }
            }

            $normalized[] = [
                'handle' => $handle,
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'help_text' => $this->nullableString($field['help_text'] ?? null),
                'options' => $options,
            ];

            $seenHandles[] = $handle;
        }

        return $normalized;
    }

    /**
     * @param  mixed  $origins
     * @return array<int, string>
     */
    protected function normalizeAllowedOrigins(mixed $origins): array
    {
        if (! is_array($origins)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($origin): ?string => $this->normalizeOrigin(is_string($origin) ? $origin : null),
            $origins,
        ))));
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function submissionRules(ProductForm $form): array
    {
        $rules = [];

        foreach ($form->field_definitions ?? [] as $field) {
            $rule = [$field['required'] ? 'required' : 'nullable'];

            switch ($field['type']) {
                case 'email':
                    $rule[] = 'email';
                    break;
                case 'select':
                    $rule[] = Rule::in(array_map(
                        fn (array $option): string => (string) $option['value'],
                        $field['options'] ?? [],
                    ));
                    break;
                default:
                    $rule[] = 'string';
                    break;
            }

            $rules[(string) $field['handle']] = $rule;
        }

        return $rules;
    }

    protected function resolveSubmissionProduct(ProductForm $form, array $validated): Product
    {
        if ($form->form_scope !== 'organisation') {
            $product = $form->product;

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'product' => ['This form is not linked to an active product.'],
                ]);
            }

            return $product;
        }

        $selectionField = (string) $form->product_selection_field;
        $selectedProductId = (int) ($validated[$selectionField] ?? 0);
        $allowedProductIds = array_map('intval', $form->allowed_product_ids ?? []);

        if ($selectionField === '' || $selectedProductId <= 0 || ! in_array($selectedProductId, $allowedProductIds, true)) {
            throw ValidationException::withMessages([
                $selectionField ?: 'product' => ['Select a valid product for this organisation form.'],
            ]);
        }

        $product = Product::query()
            ->whereKey($selectedProductId)
            ->where('organisation_id', $form->organisation_id)
            ->where('status', 'active')
            ->first();

        if (! $product instanceof Product) {
            throw ValidationException::withMessages([
                $selectionField => ['The selected product is not available for this organisation form.'],
            ]);
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function submissionPayload(ProductForm $form, array $validated): array
    {
        $payload = [];

        foreach ($form->field_definitions ?? [] as $field) {
            $handle = (string) $field['handle'];
            $payload[$handle] = $validated[$handle] ?? null;
        }

        return $payload;
    }

    protected function publicRouteUrl(ProductForm $form, string $routeName, string $surface): string
    {
        $url = route($routeName, ['form' => $form->slug]);
        $domain = $form->form_scope === 'organisation'
            ? $this->domains->resolveOrganisationDomain((string) $form->organisation_id, $surface)
            : $this->domains->resolveProductDomain((string) $form->product_id, $surface);

        if (! is_string($domain) || trim($domain) === '') {
            return $url;
        }

        return $this->replaceUrlHost($url, $domain);
    }

    protected function replaceUrlHost(string $url, string $domain): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $scheme = (string) config('platform.domain.platform_scheme', $parts['scheme'] ?? 'https');
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return sprintf('%s://%s%s%s', $scheme, trim($domain), $path, $query.$fragment);
    }

    protected function originFromUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $this->normalizeOrigin(sprintf(
            '%s://%s%s',
            $parts['scheme'],
            $parts['host'],
            isset($parts['port']) ? ':'.$parts['port'] : '',
        ));
    }

    protected function normalizeOrigin(?string $origin): ?string
    {
        if (! is_string($origin) || trim($origin) === '') {
            return null;
        }

        $parts = parse_url(trim($origin));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = Str::lower($parts['scheme']);
        $host = Str::lower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return sprintf('%s://%s%s', $scheme, $host, $port);
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fieldDefinitions
     * @return array{field: string, product_ids: array<int, int>}
     */
    protected function resolveOrganisationProductRouting(Organisation $organisation, array $attributes, array $fieldDefinitions): array
    {
        $field = Str::snake((string) ($attributes['product_selection_field'] ?? ''));
        $productIds = collect($attributes['allowed_product_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($field === '') {
            throw ValidationException::withMessages([
                'product_selection_field' => ['Organisation-level forms require a product selection field.'],
            ]);
        }

        if ($productIds === []) {
            throw ValidationException::withMessages([
                'allowed_product_ids' => ['Organisation-level forms require at least one allowed product.'],
            ]);
        }

        $validProductIds = Product::query()
            ->where('organisation_id', $organisation->getKey())
            ->where('status', 'active')
            ->whereIn('id', $productIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        sort($productIds);
        sort($validProductIds);

        if ($productIds !== $validProductIds) {
            throw ValidationException::withMessages([
                'allowed_product_ids' => ['Organisation-level forms may only route to active products inside the same organisation.'],
            ]);
        }

        $selectionDefinition = collect($fieldDefinitions)->firstWhere('handle', $field);

        if (! is_array($selectionDefinition) || ($selectionDefinition['type'] ?? null) !== 'select') {
            throw ValidationException::withMessages([
                'product_selection_field' => ['The product selection field must be a select field in the field definitions.'],
            ]);
        }

        $optionValues = collect($selectionDefinition['options'] ?? [])
            ->pluck('value')
            ->map(fn ($value): int => (int) $value)
            ->sort()
            ->values()
            ->all();

        if ($optionValues !== $productIds) {
            throw ValidationException::withMessages([
                'product_selection_field' => ['The product selection options must match the allowed product IDs exactly.'],
            ]);
        }

        return [
            'field' => $field,
            'product_ids' => $productIds,
        ];
    }
}
