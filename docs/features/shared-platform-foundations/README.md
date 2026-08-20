# Shared Platform Foundations

## Objective

Define and standardise the shared platform layer used by both newsletter operations and form/data collection workflows.

## Scope

- organisation model
- product model
- domain model and fallback rules
- reporting evolution path
- GA4 integration boundary
- analytics driver contract
- internal module/service boundaries
- search strategy for operator use
- eventing evolution path
- Statamic users
- roles and groups
- permissions
- workflow states
- audit trail
- sender and branding ownership rules
- environment, deployment, and provider integration boundaries

## Why This Feature Exists

Without a strong shared platform layer, the newsletter and form systems will continue to grow as separate custom flows instead of one coherent product.

## Target Outcomes

- each organisation can own multiple products
- each product has a clear collection and form ownership boundary
- each product can use its own verified domain, with deterministic fallback to organisation or platform domain
- reporting can scale without overloading transactional tables
- delivery, webhooks, analytics, and workflow logic are separated into clear internal modules
- operator search can be upgraded cleanly if needed
- roles and permissions can be assigned cleanly in Statamic
- editorial, operational, and review workflows have explicit states
- providers and infrastructure are documented as shared services, not feature-specific assumptions

## Dependencies

- [Project Source Of Truth](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [Session Roadmap](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [Domain Resolution Architecture](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/domain-resolution-architecture.md)
- [Analytics And Reporting Contract](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/analytics-reporting-contract.md)

## Foundation Decisions

### Organisation

An organisation is the top operational owner.

It owns:

- products
- default domain and sender settings
- organisation-level operator access boundaries
- shared compliance and support defaults

### Product

A product is the main operating unit for `version/2`.

It owns:

- one primary public publishing collection
- its public identity
- its forms
- its audience path
- its product-level domain and sender overrides when required

### Persistence Baseline

For `version/2`, the shared platform model should use dedicated application tables for:

- organisations
- products
- organisation user scope
- product user scope
- optional Statamic group to scope mapping

Do not treat organisation and product ownership as YAML-only, naming-convention-only, or group-handle-only state.

### Collection Ownership

For `version/2`, use this default:

- one product -> one primary public collection

If multiple public collections are later needed under one product, that must be reviewed by the coordinator as an explicit exception.

### Form Ownership

Each form belongs to exactly one product.

That keeps:

- domain routing
- access control
- submission workflow
- analytics attribution
- subscriber mapping

stable and reviewable.

### Audience Ownership

Each top-level audience group belongs to exactly one product.

Each preference subgroup belongs to exactly one top-level product audience group.

Do not assume cross-product audience sharing in `version/2`.

### Scope Model

Authorization scope must be relational.

Use this division:

- Statamic roles for capability
- relational scope records for organisation and product boundaries
- Statamic groups for operator-facing team management and optional scope mapping

Recommended group categories:

- `organisation_group`
- `product_group`
- `operating_team`

Recommended relational scope records:

- `organisation_user_scope`
- `product_user_scope`

Optional group mapping record:

- `statamic_group_scope_map`

### Campaign And Template Ownership

Persisted campaign records should be product-owned.

Persisted operator-managed template records should also be product-owned by default.

Recommended architecture-level ownership keys:

- `product_id`
- `organisation_id`
- `created_by`

## Domain Ownership Boundaries

Use this ownership split:

- platform owns the final fallback domain
- organisation owns default brand-level domain and mail defaults
- product owns public-surface overrides

The same split should govern:

- public pages
- hosted forms
- subscribe flows
- unsubscribe and preferences
- browser-view newsletter pages
- product-branded email URLs

## Domain Architecture

The detailed platform design for this area lives in:

- [Domain Resolution Architecture](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/domain-resolution-architecture.md)

## Internal Module Boundaries

The modular-monolith boundary should be:

- Platform Foundations
- Identity And Access
- Audience
- Newsletter
- Forms And Submissions
- Delivery And Tracking
- Analytics And Reporting
- Operations

These are code and responsibility boundaries first, not independent deployable services.

## Near-Term Documentation Tasks

- write the organisation and product model
- define domain ownership and resolution rules
- define reporting-table and warehouse escalation rules
- define internal service/module boundaries
- record search and eventing strategy
- define user roles and permissions
- define workflow state names and transitions
- define audit and logging expectations

## Access And Workflow Recommendations

### Statamic Foundation

Use:

- `roles` for capabilities
- `groups` for organisation, product, and operating-team scope

Do not rely on roles alone to express ownership boundaries.

### Recommended Baseline Roles

- `super_admin`
- `organisation_admin`
- `product_manager`
- `editor`
- `reviewer`
- `approver`
- `sender`
- `analyst`

### Permission Slug Baseline

Recommended exact permission slugs:

- `platform_admin`
- `organisation_manage`
- `product_manage`
- `access_scope_manage`
- `newsletter_view`
- `newsletter_create`
- `newsletter_edit`
- `newsletter_review`
- `newsletter_approve`
- `newsletter_schedule`
- `newsletter_send`
- `newsletter_retry`
- `newsletter_archive`
- `form_view`
- `form_create`
- `form_edit`
- `form_publish`
- `submission_view`
- `submission_review`
- `submission_approve`
- `submission_export`
- `submission_close`
- `subscriber_view`
- `subscriber_manage`
- `audience_manage`
- `preference_manage`
- `analytics_view`
- `analytics_export`
- `domain_manage`
- `integration_manage`

### Recommended Workflow Baselines

Campaigns should use:

- `draft`
- `in_review`
- `changes_requested`
- `approved`
- `scheduled`
- `sending`
- `sent`
- `partial`
- `failed`
- `cancelled`
- `archived`

Submissions should use:

- `submitted`
- `under_review`
- `changes_requested`
- `shortlisted`
- `approved`
- `rejected`
- `withdrawn`
- `closed`
- `archived`

### Enforcement Rule

Sensitive actions should require:

1. the correct role
2. the correct organisation or product scope
3. the correct workflow state

This must ultimately be enforced in application policies or service-layer authorization, not just in CP visibility.

## Coordinator-Approved Resolution For Prior Foundation Blockers

These items are now resolved for implementation planning:

1. `hybrid` products may exist, but hybrid behaviour is optional by configuration rather than mandatory by type alone.
2. Product-level sender profiles should begin as structured overrides on organisation defaults in `version/2`, not as a separate fully independent persistence model.
3. Organisations and products should be stored in dedicated database tables.
4. Compliance settings should live primarily at organisation level with optional product override where explicitly required.
5. Access control must be enforced by permission plus relational scope, and workflow state where applicable.

## Platform Recommendations

### Reporting

Use read-optimised reporting tables first.

Do not introduce a warehouse until reporting demands clearly exceed what reporting tables can support safely.

### Analytics Driver Contract

Define the analytics contract now, but keep the database implementation as the only production read backend until analytics behaviour is stable.

Recommended direction:

- `ANALYTICS_DRIVER=database|clickhouse`
- database is the default and only production read path for now
- ClickHouse can be added later as a secondary implementation

Recommended contracts:

- `AnalyticsReaderInterface`
- `AnalyticsWriterInterface`
- optional `AnalyticsEventStoreInterface`

Reader responsibilities should cover:

- campaign summary metrics
- delivery/open/click/bounce aggregates
- subscriber growth and lifecycle reporting
- form and submission reporting
- dashboard query methods

Writer responsibilities should cover:

- canonical analytics event projection
- reporting-table updates
- future compatibility with an alternative backend without changing feature semantics

The contract must remain subordinate to unresolved shared-foundation decisions on:

- domain-management authority
- workflow truth
- complained-subscriber recovery policy

Recommended rollout:

1. stabilise metrics and reporting using the database backend
2. only begin a ClickHouse implementation when explicitly requested as a separate future track
3. compare outputs for parity
4. only then allow production read switching

## Analytics And Reporting Contract

The detailed contract for this area lives in:

- [Analytics And Reporting Contract](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/analytics-reporting-contract.md)

### GA4

Use `GA4` only for behavioural analytics on public-facing web surfaces.

Recommended uses:

- landing-page traffic
- conversion funnel monitoring
- source and campaign attribution
- content click-through analysis

Do not use GA4 as operational truth for subscribers, campaigns, submissions, workflows, or platform health.

### Search

If dedicated search is needed later, prefer `Meilisearch` over `OpenSearch` for this project.

Reason:

- both are open source
- Meilisearch is simpler and lighter for internal operator search
- OpenSearch should be reserved for a significantly larger search or observability footprint

### Eventing

Stay with Laravel queues and events first.

Introduce a separate event bus only when multiple bounded contexts or external consumers truly need durable event distribution.
