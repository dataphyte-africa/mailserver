# Domain Resolution Architecture

## Objective

Define the exact domain-resolution architecture for Mailserver `version/2` so all public surfaces use consistent product, organisation, and platform domains without feature drift.

## Resolution Order

For each product-facing surface:

1. use the product domain if it exists, is verified, and is enabled
2. else use the generated organisation newsletter domain if it exists and is verified
3. else use the platform domain

This order applies to both:

- outbound URL generation
- inbound request resolution

unless a surface-specific policy says otherwise.

## Domain States

Each configured domain should have an explicit status.

Recommended states:

- `draft`
- `pending_verification`
- `verified`
- `disabled`
- `failed`

Only verified and enabled domains may be used as active product domains.

## Ownership Model

### Organisation Level

Recommended organisation fields:

- `source_domain`
- `newsletter_subdomain`
- `newsletter_domain`
- `newsletter_domain_status`
- `newsletter_domain_verified_at`
- `newsletter_dns_record_type`
- `newsletter_dns_expected_value`
- `default_domain`
- `default_mail_domain`
- `status`

Organisation defaults support:

- shared branded fallback for multiple products
- organisations that do not yet have per-product domains
- automatic allowed origins for hosted forms embedded on the source domain or generated newsletter domain

The canonical organisation newsletter host is generated from the source domain. Example:

- source domain: `dataphyte.org`
- newsletter subdomain: `nl`
- generated newsletter domain: `nl.dataphyte.org`

Operators should be shown the DNS record to create, usually an `A` record for `nl` pointing at the app server IP unless the platform is configured to require a `CNAME`.

### Product Level

Recommended product fields:

- `public_domain`
- `forms_domain`
- `mail_from_domain`
- `domain_status`
- `domain_verified_at`
- `domain_is_primary`
- `fallback_to_platform_domain`

Product-level domain settings override organisation defaults when the domain is verified and enabled.

## Core Services

### DomainResolver

Purpose:

- resolve the effective domain for a product and surface
- resolve inbound hostnames to organisation and product context

Recommended responsibilities:

- `resolveProductDomain(product, surfacePolicy)`
- `resolveOrganisationDomain(organisation, surfacePolicy)`
- `resolveRequestContext(host, path)`
- `isVerified(domainConfig)`
- `isEnabled(domainConfig)`

### ProductUrlGenerator

Purpose:

- generate canonical product-aware URLs for public surfaces

Recommended responsibilities:

- `landingPage(product)`
- `formPage(product, form)`
- `formSubmitEndpoint(product, form)`
- `preferencesPage(product, subscriber)`
- `unsubscribePage(product, subscriber)`
- `browserViewPage(product, campaign)`
- `campaignLink(product, pathOrUrl)`

### RequestContextResolver

Purpose:

- map inbound request host and path to the correct organisation, product, and surface context

This may remain part of `DomainResolver` initially if the codebase stays small, but the responsibility should remain explicit.

## Public Surface Policies

Each public surface should declare one of the following policies:

- `product_required`
- `product_preferred`
- `organisation_fallback`
- `platform_only`

## Surface Map

### Product Landing Pages

Policy:

- `product_preferred`

Expected behaviour:

- use verified product domain when available
- else verified organisation newsletter domain
- else platform domain
- canonical URL should point to the resolved preferred domain

### Hosted Public Form Pages

Policy:

- `product_preferred`

Expected behaviour:

- form pages should inherit the product-facing public domain chain
- application, subscription, and data collection pages should not invent their own unrelated domain logic
- if the form uses the organisation fallback, the public URL should be `https://nl.{source_domain}/forms/{form-slug}`

### Public Form Submit Endpoints

Policy:

- `product_preferred`

Expected behaviour:

- endpoints exposed to product-facing websites should resolve under the same product-preferred domain chain where feasible
- platform-internal submission handlers should still be coded against route names and resolver services, not literal domains

### Preferences And Unsubscribe Pages

Policy:

- `product_preferred`

Expected behaviour:

- links in outgoing mail should use the resolved product-preferred domain
- avoid unnecessary redirect hops in the email click path
- fallback remains verified organisation newsletter domain, then platform
- organisation fallback URLs should use `/preferences` and `/unsubscribe/{signed-token}` under `https://nl.{source_domain}`

### Browser-View Newsletter Pages

Policy:

- `product_preferred`

Expected behaviour:

- campaign browser-view pages should use the same product-facing domain chain as the newsletter product

### Campaign And Transactional Email Links

Policy:

- `product_preferred`

Expected behaviour:

- links pointing to product-facing destinations should use the resolved product domain chain
- generated URLs must come from `ProductUrlGenerator`, not direct string concatenation

### Tracking And Redirect Links

Policy:

- `product_preferred` by default

Expected behaviour:

- if branded tracking is supported, use the resolved domain chain
- if operational constraints require a central tracking host later, that should be documented as an explicit exception rather than mixed into normal URL generation rules

### Internal CP And Admin Routes

Policy:

- `platform_only`

Expected behaviour:

- do not route the Statamic CP or internal administration through product domains

### Webhook Endpoints

Policy:

- `platform_only`

Expected behaviour:

- providers should target the stable platform domain unless a future provider constraint requires a different rule

## Canonical URL Strategy

Every public resource should have a canonical URL.

Recommended behaviour:

- content and landing pages: redirect to canonical preferred domain
- browser-view pages: redirect to canonical preferred domain
- preferences and unsubscribe pages: allow direct resolved URL and avoid decorative redirects unless needed for correctness
- API-style form submit endpoints: avoid unnecessary redirects

## Implementation Guardrails

- never hardcode domains in controllers
- never hardcode domains in Blade or Antlers templates
- never hardcode domains in mailables
- route and link generation must go through shared domain services
- product-level and form-level features must reuse the same resolver logic
- organisation source/newsletter domains must be added to hosted-form allowed origins by the domain service, not `.env`

## Dependencies

This architecture depends on future clarification or implementation from adjacent sessions:

- Session 1:
  - exact organisation and product persistence model
- Session 2:
  - who can manage domains and verification state
- Session 5:
  - any newsletter-surface exceptions around branded tracking or browser-view routing
- Session 6:
  - any form or API exceptions for approved external platform usage

## Current Recommendation

This area is documentation-ready but not implementation-ready until the organisation/product model and role model are accepted by the coordinator and the adjacent feature sessions confirm their public-surface exceptions.
