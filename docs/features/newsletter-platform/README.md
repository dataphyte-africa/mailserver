# Newsletter Platform

## Objective

Standardise Mailserver as an internal multi-product newsletter platform.

## Scope

- newsletter product collections
- product-domain routing with platform fallback
- signup and preference routing into subscriber groups and subgroups
- campaign drafting, preview, approval, scheduling, and sending
- send retry and finalisation handling
- sender identity and product branding
- audience targeting and segmentation
- analytics, deliverability, and suppression handling
- browser-view and product-branded public newsletter surfaces

## Target Product Behaviour

- each organisation can operate one or more newsletter products
- each product maps to its own collection and product listing
- each product uses its own public domain when available, otherwise the organisation or platform fallback domain
- forms feed subscribers into the correct product audience path
- subscribers become active after signup email delivery, per the desired operational rule
- operators can manage campaigns through a clear workflow instead of direct-send admin actions only

## Platform Position

The newsletter platform is the managed publishing and delivery capability for `version/2`.

It should behave like a controlled product workflow with:

- explicit product ownership
- explicit audience ownership
- explicit workflow states
- domain-aware public surfaces
- auditable delivery and analytics reporting

## Core Operating Objects

The newsletter platform relies on these cross-feature objects:

- `Organisation`
- `Product`
- `Collection`
- `Campaign`
- `Subscriber`
- `Subscriber Group`
- `Subscriber Subgroup`
- `Template`
- `User`
- `Workflow Status`

Within this feature, the primary operating objects are:

- `Product`
  - owns the newsletter identity and public surface
- `Collection`
  - holds the editorial entries for that product
- `Campaign`
  - operational send record linked to content and audience
- `Template`
  - reusable rendering layer for product mail output
- `Audience`
  - the product-owned subscriber group and subgroups used for targeting

## Subscriber Lifecycle Boundary

The newsletter platform must treat subscriber lifecycle and audience membership as shared product rules, not as ad hoc campaign logic.

Stable lifecycle states:

- `pending`
- `active`
- `unsubscribed`
- `bounced`
- `complained`

Core rules:

- new signups become `pending` first
- activation happens only after signup email delivery
- only `active` subscribers are send-eligible
- unsubscribe, bounce, and complaint states suppress sending
- resubscribe flows return the subscriber to `pending` until delivery-confirmed reactivation completes

Audience membership rules:

- each parent subscriber group belongs to exactly one product
- each subgroup belongs to exactly one parent group
- preferences map to subgroups only inside the owning product audience tree
- one product form must not assign subscribers into another product's audience structure

Audience structure lifecycle rules:

- unused and empty groups or subgroups may be hard-deleted
- unused groups or subgroups with subscribers require reassignment or explicit membership removal before delete
- previously targeted groups or subgroups are archived instead of hard-deleted
- subscriber identity records are never deleted as a side effect of audience-structure deletion
- archived groups or subgroups are excluded from new targeting and new signup/preference assignment, but remain available for historical reporting and audit

This session treats the lifecycle above as accepted input from Session 4 and does not redefine it locally.

## Product To Collection Behaviour

### Default Rule

For `version/2`, one organisation should own one primary public newsletter collection by default, and each product maps to a blueprint inside that organisation collection.

This keeps:

- editorial ownership
- domain routing
- audience attribution
- campaign reporting
- approval workflow

stable and reviewable.

### Organisation Collection Responsibilities

The organisation-owned newsletter collection is responsible for:

- editorial entries used for newsletter campaigns
- blueprint-level product structure
- product-specific template defaults
- product-specific sender and branding defaults where allowed
- product-specific browser-view and archive behaviour

### Exception Rule

If a product later needs more than one public newsletter collection or one collection must span multiple organisations, that must be reviewed by the coordinator as an explicit exception.

Do not assume this as a default implementation path.

## Campaign Model

A campaign is the operational unit that turns a product-owned content item into a controlled send.

The campaign must carry or resolve:

- product
- collection
- content entry or content source reference
- audience target definition
- sender identity
- workflow state
- schedule intent
- delivery and analytics status

The editorial entry is the content source. The campaign is the delivery operation.

## Template Ownership And Reuse

### Ownership Rule

Templates should be owned at the product level by default.

That means:

- each product can define its preferred newsletter presentation pattern
- shared platform layout components may still exist
- product teams should not need to fork unrelated products to change their own presentation

### Reuse Rule

Template reuse should happen in layers:

1. shared platform layout primitives
2. optional organisation-level branding partials where later approved
3. product-level templates and variants

### Non-Goal

Do not assume a global template manager implementation is already settled.

This session defines the ownership model and reuse direction only. Exact persistence and template-management UI details remain implementation decisions for later sessions.

## Audience Targeting Rules

### Ownership Rule

Each campaign targets subscribers only within the owning product audience path unless the coordinator later approves a cross-product exception.

### Targeting Levels

Allowed targeting levels should be:

- whole product audience
- selected product subgroups or preference segments
- future dynamic segments once the segmentation model is explicitly documented

### Current Stable Rule

For the stable baseline, assume:

- one product audience group per product
- one or more subgroups under that product
- no implicit cross-product audience mixing
- no hard delete for historically used audience structures

### Future Direction

Later versions may add dynamic segmentation using engagement, source, behaviour, or inactivity rules, but that is not yet a settled implementation fact in this session.

## Domain Expectations

The newsletter platform should resolve domains centrally for:

- campaign browser-view pages
- unsubscribe links
- preference links
- public archive or landing pages
- product-branded newsletter links
- sender and reply-to identity where domain policy allows

The shared foundation design for this lives in:

- [Domain Resolution Architecture](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/domain-resolution-architecture.md)

## Newsletter Public Surfaces

The newsletter platform should define and support these public surfaces:

- product newsletter landing page
- product browser-view newsletter page
- unsubscribe page
- preference-management page
- newsletter archive or canonical view surface when enabled
- campaign and content links that preserve product identity

### Public Surface Rule

These surfaces must use shared domain-resolution services rather than feature-local domain logic.

## Analytics Boundary

Use `GA4` here only for:

- traffic to newsletter landing pages
- signup funnel performance
- campaign traffic to website content
- attribution reporting

Keep campaign delivery, subscriber lifecycle, bounce/complaint handling, and send reconciliation inside the platform reporting layer.

## Current-State Reference

Useful current-state implementation docs:

- [Campaign Engine](/Users/dataphytefoundation/Herd/mailserver/docs/architecture/campaign-engine.md)
- [Subscriber Management](/Users/dataphytefoundation/Herd/mailserver/docs/operations/subscriber-management.md)
- [Subscriber Rating](/Users/dataphytefoundation/Herd/mailserver/docs/operations/subscriber-rating.md)
- [Elastic Email Integration](/Users/dataphytefoundation/Herd/mailserver/docs/integrations/elastic-email-integration.md)

## Feature Dependencies

This feature depends on:

- shared organisation and product ownership rules
- accepted role and workflow model
- accepted domain resolution architecture
- subscriber lifecycle and preference architecture
- analytics contract and reporting model

## Open Dependencies And Non-Settled Areas

This session does not settle:

- exact persistence schema for products, campaigns, templates, or domain verification records
- final permission slug naming
- final domain-management UI or verification operations
- final dynamic segmentation implementation
- final browser-view storage model

These areas must be resolved by their owning sessions or later implementation planning.

## Implementation Readiness

This feature is documentation-shaped but not fully implementation-ready until:

- subscriber lifecycle rules are finalised and accepted through checkpointing
- analytics contract is accepted
- coordinator confirms no contradiction with shared role and domain governance

## Target-State Topics To Document Next

- campaign workflow states
- approval rules
- template ownership model
- domain resolution for newsletter public surfaces
- product-level analytics expectations
- segmentation beyond static subgroup selection
