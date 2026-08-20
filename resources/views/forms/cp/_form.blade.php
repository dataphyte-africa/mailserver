@php
    $selectedScope = (string) old('form_scope', $form?->form_scope ?? 'product');
    $selectedOrganisationId = (string) old('organisation_id', $form?->organisation_id ?? $organisations->first()?->getKey());
    $selectedProductId = (string) old('product_id', $form?->product_id ?? $products->first()?->getKey());
    $selectedTemplate = (string) old('template_family', $form?->template_family ?? 'application_basic');
    $selectedStatus = (string) old('status', $form?->status ?? 'published');
    $selectedGroupId = (string) old('audience_group_id', $form?->audience_group_id ?? '');
    $selectedSubGroupId = (string) old('audience_sub_group_id', $form?->audience_sub_group_id ?? '');
    $selectedAllowedProductIds = collect(old('allowed_product_ids', $allowedProductIds ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

@if($errors->any())
    <div class="form-platform-error">
        <p class="form-platform-error-title">Please correct the highlighted fields.</p>
        <ul class="form-platform-error-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-platform-card form-platform-stack" data-product-form-scope-controls>
    <div class="form-platform-grid">
        <div class="form-platform-field">
            <label class="form-platform-label">Form Scope</label>
            <select name="form_scope" class="input-text form-platform-control" data-form-scope required>
                <option value="product" @selected($selectedScope === 'product')>Single product</option>
                <option value="organisation" @selected($selectedScope === 'organisation')>Organisation with product choice</option>
            </select>
            <p class="form-platform-help">
                Single product forms assign every submission to one product. Organisation forms require the user to select one allowed product.
            </p>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Organisation</label>
            <select name="organisation_id" class="input-text form-platform-control" data-organisation-select>
                @foreach($organisations as $organisation)
                    <option value="{{ $organisation->getKey() }}" @selected($selectedOrganisationId === (string) $organisation->getKey())>
                        {{ $organisation->name }}
                    </option>
                @endforeach
            </select>
            <p class="form-platform-help">Required for organisation-level product-choice forms.</p>
        </div>

        <div class="form-platform-field" data-product-scope-field @if($selectedScope === 'organisation') hidden @endif>
            <label class="form-platform-label">Single Product</label>
            <select name="product_id" class="input-text form-platform-control" data-product-select @disabled($selectedScope === 'organisation')>
                @foreach($products as $product)
                    <option value="{{ $product->getKey() }}"
                            data-organisation-id="{{ $product->organisation_id }}"
                            @selected($selectedProductId === (string) $product->getKey())>
                        {{ $product->name }} &middot; {{ $product->organisation?->name }}
                    </option>
                @endforeach
            </select>
            <p class="form-platform-help">Only active products in your operator scope are listed.</p>
        </div>
    </div>

    <div class="form-platform-grid" data-organisation-scope-fields @if($selectedScope !== 'organisation') hidden @endif>
        <div class="form-platform-field">
            <label class="form-platform-label">Product Selection Field</label>
            <input type="text" name="product_selection_field" value="{{ old('product_selection_field', $form?->product_selection_field ?? 'product_choice') }}" class="input-text form-platform-control">
            <p class="form-platform-help">
                For organisation-level forms, this must match a select field handle in the JSON below.
            </p>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Allowed Products For Organisation Form</label>
            <select name="allowed_product_ids[]" class="input-text form-platform-control" multiple data-allowed-products-select @disabled($selectedScope !== 'organisation')>
                @foreach($products as $product)
                    <option value="{{ $product->getKey() }}"
                            data-organisation-id="{{ $product->organisation_id }}"
                            @selected(in_array((string) $product->getKey(), $selectedAllowedProductIds, true))>
                        {{ $product->organisation?->name }} / {{ $product->name }}
                    </option>
                @endforeach
            </select>
            <p class="form-platform-help">
                Only products in the selected organisation are accepted server-side. Match these IDs in the product-choice select options.
            </p>
        </div>
    </div>

    <div class="form-platform-grid">
        <div class="form-platform-field">
            <label class="form-platform-label">Template</label>
            <select name="template_family" class="input-text form-platform-control" required>
                @foreach($templateFamilies as $handle => $definition)
                    <option value="{{ $handle }}" @selected($selectedTemplate === $handle)>
                        {{ str($handle)->replace('_', ' ')->title() }} &middot; {{ $definition['mode'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $form?->name) }}" class="input-text form-platform-control" required>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $form?->slug) }}" class="input-text form-platform-control" required>
            <p class="form-platform-help">Public URL: /forms/{slug}</p>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Status</label>
            <select name="status" class="input-text form-platform-control" required>
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Custom Extension Key</label>
            <input type="text" name="custom_extension_key" value="{{ old('custom_extension_key', $form?->custom_extension_key) }}" class="input-text form-platform-control">
            <p class="form-platform-help">Optional hook for hardcoded custom flows.</p>
        </div>
    </div>

    <div class="form-platform-grid">
        <div class="form-platform-field">
            <label class="form-platform-label">Headline</label>
            <input type="text" name="headline" value="{{ old('headline', $form?->headline) }}" class="input-text form-platform-control">
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Success Message</label>
            <input type="text" name="success_message" value="{{ old('success_message', $form?->success_message ?? 'Submission received.') }}" class="input-text form-platform-control" required>
        </div>
    </div>

    <div class="form-platform-field">
        <label class="form-platform-label">Description</label>
        <textarea name="description" rows="3" class="input-text form-platform-control">{{ old('description', $form?->description) }}</textarea>
    </div>

    <div class="form-platform-field">
        <label class="form-platform-label">Field Definitions JSON</label>
        <textarea name="field_definitions_json" rows="14" class="input-text form-platform-control form-platform-code" required>{{ old('field_definitions_json', $fieldDefinitionsJson) }}</textarea>
        <p class="form-platform-help">
            Supported field types in this baseline: text, email, textarea, select. Select fields require options.
        </p>
    </div>

    <div class="form-platform-field">
        <label class="form-platform-label">Allowed Embed Origins</label>
        <textarea name="allowed_origins_text" rows="4" class="input-text form-platform-control" placeholder="https://example.com">{{ old('allowed_origins_text', $allowedOriginsText) }}</textarea>
        <p class="form-platform-help">
            One origin per line. Organisation source/newsletter domains and the hosted form's own product/platform origin are allowed automatically.
        </p>
    </div>

    <div class="form-platform-grid">
        <div class="form-platform-field">
            <label class="form-platform-label">Audience Group</label>
            <select name="audience_group_id" class="input-text form-platform-control">
                <option value="">No audience assignment</option>
                @foreach($groups as $group)
                    <option value="{{ $group->getKey() }}" @selected($selectedGroupId === (string) $group->getKey())>
                        {{ $group->product?->name ?? 'Product' }} &middot; {{ $group->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-platform-field">
            <label class="form-platform-label">Audience Subgroup</label>
            <select name="audience_sub_group_id" class="input-text form-platform-control">
                <option value="">No subgroup assignment</option>
                @foreach($groups as $group)
                    @foreach($group->subGroups as $subGroup)
                        <option value="{{ $subGroup->getKey() }}" @selected($selectedSubGroupId === (string) $subGroup->getKey())>
                            {{ $group->name }} / {{ $subGroup->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
            <p class="form-platform-help">Archived groups and subgroups are excluded.</p>
        </div>
    </div>

    <label class="form-platform-checkbox">
        <input type="checkbox" name="requires_review" value="1" @checked(old('requires_review', $form?->requires_review ?? true))>
        Requires review before operational follow-up
    </label>
</div>
