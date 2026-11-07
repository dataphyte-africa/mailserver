# Platform and access

## Implemented structure

- `organisations` and `products` are relational records; Statamic collections/blueprints hold editorial content. `StatamicNewsletterProductSyncService` and explicit provisioning commands align selected editorial structures with products. Do not infer that a product or audience exists in a particular environment merely because a blueprint is checked in.
- `organisation_user_scope`, `product_user_scope`, and `statamic_group_scope_map` exist. `CpOperatorScope`, CP middleware, and scoped selectors filter custom CP listings and direct access. Statamic collection and entry policies cover editorial access.
- A non-super operator may choose among **delegated** organisations in Working Organisation. The active organisation narrows the workspace; changing it does not grant access. Super-admin scope is distinct. The Operator Access CP page manages delegation.
- Ownership is explicit on campaigns, forms, audiences, and other operational records. Legacy rows without deterministic ownership need an audited repair or backfill, not name-based access assumptions.

## Public identity and domains

- Organisation Profile stores public identity, social links, colour choices, addresses, and uploaded logo/banner. Product Banners provides product-level hero images with organisation fallback. The current upload contract is PNG/JPG/WebP/GIF up to 150 KB; runtime behavior should be verified on the target environment.
- Organisation Domains saves a source domain, newsletter subdomain, DNS type, and expected DNS value. DNS verification marks the derived domain verified. `NewsletterPublicUrlService` and domain resolution build form, preference, unsubscribe, browser-view, and archive links with fallback when a verified domain is unavailable.
- DNS verification does **not** configure TLS, web-server host routing, queue workers, or CORS by itself. Cross-origin website submissions also require the form's Allowed Embed Origins and server `CORS_ALLOWED_ORIGINS` to permit the website origin.

## Evidence and limits

Key implementation: `app/Services/Platform/CpOperatorScope.php`, `app/Http/Middleware/EnforceCpOwnership.php`, `app/Services/Platform/OrganisationNewsletterDomainService.php`, and `app/Services/Newsletter/NewsletterPublicUrlService.php`. Focused coverage includes `tests/Feature/CpOperatorIsolationTest.php`, `tests/Feature/ActiveOrganisationContextTest.php`, `tests/Feature/OrganisationProfileTest.php`, and `tests/Feature/ProductFormPlatformTest.php`.

The code establishes the access model, but this snapshot does not certify production delegation rows, DNS resolution, TLS, or public URL reachability. See [Operations](../operations/README.md).
