# Form And Data Collection Platform Implementation Notes

## Purpose

Capture implementation constraints and intended structure for the form and data collection platform without redefining shared platform rules locally.

## Implementation Posture

This feature should be implemented as part of the modular monolith, not as a separate subsystem with its own competing rules for:

- domain resolution
- access control
- workflow states
- reporting truth

## Collection And Product Alignment

The form platform follows the corrected `version/2` ownership model:

- Statamic collection = organisation
- Statamic blueprint inside that collection = product
- relational `organisations` and `products` remain the operational source of truth
- hosted forms must use relational ownership records populated from the approved collection/blueprint mapping

Supported form scopes:

- `product`: the form belongs to one product and every submission is stored under that product
- `organisation`: the form belongs to one organisation and includes a required product-selection field that routes each submission to one allowed product inside the same organisation

Guardrails:

- organisation-level forms may not route submissions to products in another organisation
- organisation-level forms must declare `product_selection_field`
- organisation-level forms must declare `allowed_product_ids`
- the product-selection field must be a select field whose options match the allowed product IDs exactly
- form submissions are never stored without a concrete `product_id`

## Recommended Internal Boundaries

### Form Definition Layer

Responsibilities:

- bind a form to either exactly one product or one organisation with guarded product-choice routing
- declare form mode
- declare template family
- declare configurable fields and metadata

### Submission Intake Layer

Responsibilities:

- receive validated input
- normalize input
- persist stored submission data
- invoke the correct mode-specific workflow

### Mode-Specific Workflow Layer

Responsibilities:

- `subscription`
  - subscriber create or update
  - preference mapping
  - lifecycle trigger
- `application`
  - reviewable intake
  - eligibility or routing extensions when configured
- `data_collection`
  - structured storage
  - optional review or follow-up handling

### Review And Audit Layer

Responsibilities:

- operator actions
- workflow-state transitions
- export visibility
- audit history

## Guardrails

- do not hardcode domains in form controllers or templates
- do not bypass shared workflow state vocabulary
- do not assume every form submission creates or updates a subscriber
- do not let external rendering frontends become the source of truth
- do not mix reusable template logic and hardcoded product-specific logic without an explicit boundary

## Known Unresolved Inputs

The following must be treated as external dependencies until the coordinator settles them:

- exact persistence model for organisations and products
- exact group-to-scope implementation
- exact permission slug design
- exact authority for domain verification and configuration

## Implementation Readiness

This feature is documentation-ready for implementation planning.

It remains blocked for sensitive implementation decisions that require final answers to the unresolved shared-platform dependencies above.

## Session 14 Runtime Adoption Notes

The first controlled runtime adoption of the shared domain scaffolding is now in the hosted form layer only.

Implemented:

- subscription form schema payloads now expose domain-aware submit endpoints when the form maps to a product with an eligible forms domain
- hosted application pages now emit domain-aware schema, submit, and location-helper URLs using the shared form-domain chain

Guardrails preserved:

- no new external embed behaviour was introduced
- no inbound host canonicalisation or redirect policy was assumed
- unresolved domain-verification authority remains outside this feature implementation

## Session 40 Product-Owned Form Baseline Notes

This session implements the first product-owned form and data-collection baseline beyond the legacy newsletter form lifecycle.

Implemented:

- dedicated relational `product_forms` records bound to exactly one `product` and one `organisation`
- dedicated relational `product_form_submissions` storage for operational form truth
- supported template families limited to:
  - `application_basic`
  - `data_collection_basic`
- hosted public form rendering at `/forms/{slug}` with shared product-domain preference and platform fallback
- server-side `Origin` allow-list enforcement for public submit
- additive operational admin surface at `/cp/product-forms`, registered through Statamic CP routing
- CSV export and paginated submission listing through `ProductFormService`
- archive-aware optional audience assignment validation for newly created forms

Rule preserved:

- the new baseline does not infer ownership from historical unowned rows
- the new baseline does not create or activate subscribers by default
- `subscription` mode was not cut over into the new relational form path
- existing pending-to-active signup lifecycle behaviour remains untouched
- no historical cleanup, ownership backfill, remapping, or broad read cutover was introduced

Verification on Tuesday, August 18, 2026:

- focused feature coverage passed with `21 tests, 75 assertions` across:
  - `tests/Feature/ProductFormPlatformTest.php`
  - `tests/Feature/SubscriberArchiveAssignmentTest.php`
  - `tests/Feature/PendingSubscriberLifecycleTest.php`
- the new coverage proves:
  - product-owned form creation fails closed for archived audience targets
  - hosted URLs prefer verified product domains and fall back to the platform host
  - allowed-origin submit stores the operational submission
  - disallowed-origin submit is rejected server-side
  - admin form listing renders over HTTP and submission listing/export return stored values through the service boundary

Coordinator correction after browser review on Wednesday, August 19, 2026:

- local CP errors on `Hosted Forms` and `Subscribers` were caused first by pending v2 migrations not being applied to the local `mailserver` database
- `php artisan migrate --force` applied the pending subscriber lifecycle columns plus `product_forms` and `product_form_submissions`
- the form admin routes were moved from normal `web.php` auth routes into `Statamic::pushCpRoutes()` so `/cp/product-forms` uses the same CP route stack as newsletter admin pages
- `Hosted Forms` is now a direct item under the `Forms` CP section instead of a nested child under another `Forms` item
- the hosted-form CP list and submission views now extend `statamic::layout` so they render inside the normal Statamic sidebar/topbar shell
- unauthenticated local checks now redirect to CP login instead of returning a 500; authenticated CP access is covered by the focused feature test

Deferred:

- relational `subscription` template adoption
- broader schema-fetch embed flows
- reviewer workflow transitions beyond initial `submitted`
- explicit hardcoded custom flows beyond the documented extension key boundary

Coordinator follow-up implementation on Wednesday, August 19, 2026:

- added Statamic CP create and edit screens for product-owned hosted forms
- create/edit uses active product scope and active organisation checks through a form-specific product selector
- form fields are managed as JSON in this baseline so hardcoded/custom flows can still be represented before a full form-builder UI exists
- allowed embed origins are managed as one origin per line; the resolved hosted product/platform origin remains allowed automatically
- optional audience group/subgroup assignment remains archive-aware and product-owned through `ProductFormService`
- submission listing/export now enforces the same product form scope instead of relying only on authenticated CP access
- CP Blade screens in this module now push module-scoped CSS into the Statamic CP head stack and do not rely on Tailwind utility classes or Statamic button utility availability
- when no active product is available, the create page now renders an in-CP setup message instead of returning a raw 403
- Statamic super users can create forms for any active product under an active organisation; non-super operators still require active product scope

Verification:

- `php artisan route:list --path=cp/product-forms`
  - result: seven `statamic.cp.product-forms.*` routes are registered, including create, store, edit, and update
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `6 tests`, `54 assertions`
- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/NewsletterDashboardWidgetTest.php`
  - result: `PASS`, `6 tests`, `67 assertions`

Still deferred:

- visual drag-and-drop form builder
- relational `subscription` template adoption
- broader schema-fetch embed flows
- reviewer workflow transitions beyond initial `submitted`

## Coordinator Correction - Organisation Scope CP Product Filtering

Date: Wednesday, August 19, 2026

Decision:

- Statamic collections remain organisations.
- Blueprints inside those collections remain products.
- Product forms support two explicit scopes:
  - `product`: one form is locked to one product.
  - `organisation`: one form belongs to an organisation and requires the submitter to choose one allowed product under that organisation.

Implementation correction:

- selecting `organisation` scope in the CP hides and disables the `Single Product` field
- selecting an organisation filters the visible/assignable `Allowed Products For Organisation Form` options to that organisation only
- selecting an organisation also filters the hidden single-product options so switching back to `product` scope does not retain a cross-organisation choice
- server-side validation remains the authority and still rejects products outside the selected organisation
- the CP script is registered through Statamic's CP script hook instead of inline Blade sections, because Statamic CP page mounting can occur after a Blade partial has rendered
- the CP script rebuilds product dropdown options for the selected organisation instead of relying on hidden `<option>` elements
- a MutationObserver-based retry loop was removed because it could repeatedly rebuild select options and make the CP page feel unresponsive

Verification:

- browser verification on `/cp/product-forms/create` confirmed that selecting `Organisation with product choice` and `Dataphyte Insight` hides/disables `Single Product` and shows only:
  - `Dataphyte Insight / Data Dive`
  - `Dataphyte Insight / Marina and Maitama`
  - `Dataphyte Insight / Pocket Science`
  - `Dataphyte Insight / SenorRita`
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `8 tests`, `67 assertions`
