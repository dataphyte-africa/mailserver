@php
    $legacyProductSubscription = $subscriptionWorkspace && $form?->form_scope === 'product';
    $selectedScope = $subscriptionWorkspace ? ($legacyProductSubscription ? 'product' : 'organisation') : (string) old('form_scope', $form?->form_scope ?? 'product');
    $selectedOrganisationId = (string) old('organisation_id', $form?->organisation_id ?? $organisations->first()?->getKey());
    $selectedProductId = (string) old('product_id', $form?->product_id ?? $products->first()?->getKey());
    $selectedTemplate = (string) old('template_family', $form?->template_family ?? ($subscriptionWorkspace ? 'subscription_basic' : 'application_basic'));
    $selectedStatus = (string) old('status', $form?->status ?? 'published');
    $selectedGroupId = (string) old('audience_group_id', $form?->audience_group_id ?? '');
    $selectedSubGroupId = (string) old('audience_sub_group_id', $form?->audience_sub_group_id ?? '');
    $selectedAllowedProductIds = collect(old('allowed_product_ids', $allowedProductIds ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $savedPreferenceGroupIds = $form?->settings['public_preference_group_ids'] ?? null;
    $defaultPreferenceGroupIds = $savedPreferenceGroupIds ?? ($form && $form->form_scope === 'organisation'
        ? $publicPreferenceGroups->where('organisation_id', (int) $selectedOrganisationId)->pluck('id')->all()
        : []);
    $selectedPreferenceGroupIds = collect(old('public_preference_group_ids', $defaultPreferenceGroupIds))
        ->map(fn ($id) => (string) $id)->all();
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
        @if(!$subscriptionWorkspace)
        <div class="form-platform-field">
            <label class="form-platform-label">Form Scope</label>
            <select name="form_scope" class="input-text form-platform-control" data-form-scope required>
                <option value="product" @selected($selectedScope === 'product')>Single product</option>
                <option value="organisation" @selected($selectedScope === 'organisation')>Organisation with product choice</option>
            </select>
            <p class="form-platform-help">
                Single product forms assign submissions to one product. Organisation forms require the applicant to choose a product.
            </p>
        </div>
        @else
            <input type="hidden" name="form_scope" value="{{ $selectedScope }}">
        @endif

        <div class="form-platform-field">
            <label class="form-platform-label">Organisation</label>
            <select name="organisation_id" class="input-text form-platform-control" data-organisation-select>
                @foreach($organisations as $organisation)
                    <option value="{{ $organisation->getKey() }}" @selected($selectedOrganisationId === (string) $organisation->getKey())>
                        {{ $organisation->name }}
                    </option>
                @endforeach
            </select>
            <p class="form-platform-help">{{ $subscriptionWorkspace ? 'Choose the organisation, then select which of its approved public preferences this form will offer.' : 'Required for organisation-level product-choice forms.' }}</p>
        </div>

        @if(!$subscriptionWorkspace || $legacyProductSubscription)
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
        @endif
    </div>

    @if($subscriptionWorkspace && !$legacyProductSubscription)
        <fieldset class="form-platform-field form-platform-preferences">
            <legend class="form-platform-label">Public preferences on this form <span aria-hidden="true">*</span></legend>
            <p class="form-platform-help">Only the selected preferences appear on this form. Subscribers choose which ones to join.</p>
            <div class="form-platform-preference-list">
                @foreach($publicPreferenceGroups as $group)
                    @php $belongsToSelectedOrganisation = (string) $group->organisation_id === $selectedOrganisationId; @endphp
                    <label class="form-platform-preference-option" data-public-preference-group data-organisation-id="{{ $group->organisation_id }}" @if(!$belongsToSelectedOrganisation) hidden @endif>
                        <input type="checkbox" name="public_preference_group_ids[]" value="{{ $group->getKey() }}"
                            @checked(in_array((string) $group->getKey(), $selectedPreferenceGroupIds, true))
                            @disabled(!$belongsToSelectedOrganisation)>
                        <span>
                            <strong>{{ $group->name }}</strong>
                            <small>{{ $group->product?->name }} &middot; {{ $group->subGroups->count() }} {{ \Illuminate\Support\Str::plural('choice', $group->subGroups->count()) }}</small>
                        </span>
                    </label>
                @endforeach
                <p class="form-platform-help" data-public-preferences-empty @if($publicPreferenceGroups->contains('organisation_id', (int) $selectedOrganisationId)) hidden @endif>
                    No approved public preferences are available for this organisation. Publish an audience group in Organisation Profile first.
                </p>
            </div>
        </fieldset>
    @endif

    @unless($subscriptionWorkspace)
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
    @endunless

    <div class="form-platform-grid">
        @if($subscriptionWorkspace)
            <input type="hidden" name="template_family" value="subscription_basic">
        @else
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
        @endif

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

        @unless($subscriptionWorkspace)
        <div class="form-platform-field">
            <label class="form-platform-label">Closes at (Africa/Lagos)</label>
            <input type="datetime-local" name="closes_at" value="{{ old('closes_at', filled($form?->settings['closes_at'] ?? null) ? \Carbon\CarbonImmutable::parse($form->settings['closes_at'])->format('Y-m-d\TH:i') : '') }}" class="input-text form-platform-control">
            <p class="form-platform-help">Leave blank for no scheduled deadline. Extend to a future date to reopen.</p>
        </div>
        <label class="form-platform-checkbox">
            <input type="checkbox" name="accepting_submissions" value="1" @checked(old('accepting_submissions', $form?->settings['accepting_submissions'] ?? true))>
            Accepting submissions (turn off to close immediately)
        </label>
        <div class="form-platform-field">
            <label class="form-platform-label">Custom Extension Key</label>
            <input type="text" name="custom_extension_key" value="{{ old('custom_extension_key', $form?->custom_extension_key) }}" class="input-text form-platform-control">
            <p class="form-platform-help">Optional hook for hardcoded custom flows.</p>
        </div>
        @endunless
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

    @if($subscriptionWorkspace)
    <div class="form-platform-card form-platform-stack">
        <strong>Subscription form options</strong>
        <p class="form-platform-help">Used by subscription templates. These fields are optional for subscribers and are stored with their form submission.</p>
        @foreach(['country' => 'Country', 'profession' => 'Profession', 'gender' => 'Gender'] as $handle => $label)
            <label class="form-platform-checkbox">
                <input type="checkbox" name="include_{{ $handle }}" value="1" @checked(old('include_'.$handle, collect($form?->field_definitions ?? [])->contains(fn ($field) => ($field['handle'] ?? '') === $handle)))>
                Ask for {{ $label }}
            </label>
        @endforeach
        <label class="form-platform-checkbox">
            <input type="checkbox" name="show_banner" value="1" @checked(old('show_banner', $form?->settings['show_banner'] ?? true))>
            Show the organisation's uploaded banner
        </label>
        <div class="form-platform-field">
            <label class="form-platform-label">Welcome email subject</label>
            <input type="text" name="welcome_subject" value="{{ old('welcome_subject', $form?->settings['welcome_subject'] ?? '') }}" class="input-text form-platform-control" placeholder="Welcome to our updates">
        </div>
        <div class="form-platform-field">
            <label class="form-platform-label">Welcome email message</label>
            <textarea name="welcome_body" rows="5" class="input-text form-platform-control" placeholder="Thank you for subscribing.">{{ old('welcome_body', $form?->settings['welcome_body'] ?? '') }}</textarea>
            <p class="form-platform-help">The email is queued after signup. A pending subscriber becomes active only after confirmed delivery.</p>
        </div>
        <div class="form-platform-field">
            <label class="form-platform-label">Resubscription email subject</label>
            <input type="text" name="resubscribe_subject" value="{{ old('resubscribe_subject', $form?->settings['resubscribe_subject'] ?? '') }}" class="input-text form-platform-control" placeholder="Welcome back">
        </div>
        <div class="form-platform-field">
            <label class="form-platform-label">Resubscription email message</label>
            <textarea name="resubscribe_body" rows="5" class="input-text form-platform-control" placeholder="You are subscribed again.">{{ old('resubscribe_body', $form?->settings['resubscribe_body'] ?? '') }}</textarea>
        </div>
        <label class="form-platform-checkbox">
            <input type="checkbox" name="send_update_email" value="1" @checked(old('send_update_email', $form?->settings['send_update_email'] ?? false))>
            Email subscribers when they update their details or preferences
        </label>
        <div class="form-platform-field">
            <label class="form-platform-label">Preference update email subject</label>
            <input type="text" name="update_subject" value="{{ old('update_subject', $form?->settings['update_subject'] ?? '') }}" class="input-text form-platform-control" placeholder="Your preferences were updated">
        </div>
        <div class="form-platform-field">
            <label class="form-platform-label">Preference update email message</label>
            <textarea name="update_body" rows="5" class="input-text form-platform-control" placeholder="Your preferences have been updated.">{{ old('update_body', $form?->settings['update_body'] ?? '') }}</textarea>
        </div>
    </div>
    @endif

    <div class="form-platform-field">
        <label class="form-platform-label">Field Definitions JSON</label>
        <textarea name="field_definitions_json" rows="14" class="input-text form-platform-control form-platform-code" required>{{ old('field_definitions_json', $fieldDefinitionsJson) }}</textarea>
        <p class="form-platform-help">
            Supported field types: text, email, textarea, select, country. Country options come from the shared database catalogue.
            @if($subscriptionWorkspace)
                Subscription forms require a required <code>email</code> field. The public form lists only the approved preferences selected above; each subscriber choice enrolls them in that audience.
            @endif
        </p>
    </div>

    <div class="form-platform-field">
        <label class="form-platform-label">Allowed Embed Origins</label>
        <textarea name="allowed_origins_text" rows="4" class="input-text form-platform-control" placeholder="https://example.com">{{ old('allowed_origins_text', $allowedOriginsText) }}</textarea>
        <p class="form-platform-help">
            One origin per line. Organisation source/newsletter domains and the hosted form's platform origin are allowed automatically.
        </p>
    </div>

    @if($legacyProductSubscription)
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
            <p class="form-platform-help">Optional fixed audience assignment for legacy API clients. Hosted subscription forms use the subscriber's selected public preferences instead; membership remains pending until confirmation.</p>
        </div>
    </div>
    @endif

    @unless($subscriptionWorkspace)
        <label class="form-platform-checkbox">
            <input type="checkbox" name="requires_review" value="1" @checked(old('requires_review', $form?->requires_review ?? true))>
            Requires review before operational follow-up
        </label>
    @endunless
</div>
