# Project Source Of Truth

## Project Name

Mailserver

## Product Direction

Mailserver should be developed as an internal multi-organisation platform with two first-class capabilities:

1. newsletter publishing, audience management, and campaign delivery
2. configurable form and data collection workflows

It should no longer be treated as only a custom newsletter tool.

## Overall Objective

Build a standardised internal platform that allows multiple organisations or brands to:

- manage products and audiences
- create and manage signup and application forms
- collect structured data
- route subscribers into the right group and preference path
- send newsletters from product-specific collections
- operate safely through role-based workflows
- review analytics, delivery status, and submission history from one system

## Build Branch

The new version of this platform is to be built on:

- `version/2`

All coordinator and feature-session prompts should treat `version/2` as the target build branch for this revamp unless explicitly changed later.

## Custom CP Styling Rule

Custom control-panel pages must render inside the Statamic CP shell and use feature-scoped pure CSS classes. Do not rely on Tailwind utility-only layouts for custom CP surfaces because the Statamic CP runtime may not compile or preserve those utilities consistently.

## Core Goals

### 1. Standardise The Newsletter Platform

The system should support:

- organisation-level configuration
- product-level collections and publishing flows
- reusable newsletter templates
- audience and subgroup targeting
- campaign drafting, approval, scheduling, sending, and analytics

### 2. Standardise The Form And Data Collection Platform

The system should support:

- reusable form templates
- organisation-specific form configuration
- custom business-rule handling where needed
- data intake, storage, review, and workflow progression
- optional linkage between submissions and subscribers

### 3. Use Statamic As The Operational CMS Layer

Statamic should remain the operational admin layer for:

- users
- roles
- groups
- collection editing
- editorial workflows
- controlled content and form management

## Product Model

The target platform model is:

- `Organisation`
- `Product`
- `Collection`
- `Form`
- `Subscriber`
- `Subscriber Group`
- `Subscriber Subgroup / Preference Segment`
- `Campaign`
- `Submission`
- `Template`
- `User`
- `Role`
- `Workflow Status`

## Access And Ownership Model

The admin foundation for `version/2` should use Statamic users, roles, and groups.

### Access Model Rule

- `roles` define capabilities
- `groups` define operational context
- application policies or service-layer authorization must combine both before sensitive actions are allowed

### Grouping Rule

Groups should be used for:

- organisation membership
- product-team membership
- cross-functional operating teams where needed

Roles should not be used to model organisation ownership directly. Roles define what a user may do; groups and later policy rules define where they may do it.

### Default Administrative Roles

The platform should support these baseline roles:

- `super_admin`
- `organisation_admin`
- `product_manager`
- `editor`
- `reviewer`
- `approver`
- `sender`
- `analyst`

### Role Intent

- `super_admin`
  - platform-wide configuration and override authority
  - can manage organisations, products, permissions, integrations, and audits
- `organisation_admin`
  - full administrative control within one organisation
  - can manage products, team membership, forms, campaigns, and reporting for that organisation
- `product_manager`
  - operational owner for one or more products
  - can coordinate content, audiences, forms, schedules, and readiness for send or publication
- `editor`
  - can create and edit content, forms, and draft assets within allowed products
- `reviewer`
  - can review submissions or campaign content and return items for revision
- `approver`
  - can approve campaigns or structured submission outcomes where approval is required
- `sender`
  - can execute final send or publish actions only for already-approved items
- `analyst`
  - can access reporting, exports, and performance dashboards without operational edit permissions

### Separation Of Duties Rule

Where possible, the platform should separate:

- editing
- reviewing
- approving
- sending

The same person may hold multiple roles in small teams, but the permission model should not assume that by default.

## Workflow State Model

Workflow states must be explicit and shared across the platform instead of being hidden in controller logic.

### Campaign Workflow States

Recommended campaign states:

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

### Campaign Workflow Meanings

- `draft`
  - editable working state
- `in_review`
  - submitted for editorial or operational review
- `changes_requested`
  - reviewed and returned for edits
- `approved`
  - content and targeting accepted; may move to schedule or send
- `scheduled`
  - approved and queued for a future send time
- `sending`
  - actively dispatching or awaiting dispatch completion
- `sent`
  - completed without unresolved failures
- `partial`
  - completed with unresolved send failures or mixed outcome
- `failed`
  - unable to proceed or complete safely
- `cancelled`
  - intentionally stopped before send completion
- `archived`
  - closed historical record, not active operational work

### Campaign Permission Boundaries

- editors may create and edit drafts
- reviewers may move drafts into review outcomes
- approvers may move reviewed work to `approved`
- senders may move approved work to `scheduled` or `sending`
- only organisation admins or super admins may force-reset failed or exceptional states unless a future policy says otherwise

### Submission Workflow States

Recommended submission states:

- `draft` when internal-only creation exists
- `submitted`
- `under_review`
- `changes_requested`
- `shortlisted`
- `approved`
- `rejected`
- `withdrawn`
- `closed`
- `archived`

### Submission Workflow Meanings

- `submitted`
  - received and stored but not yet processed by a reviewer
- `under_review`
  - currently being assessed
- `changes_requested`
  - requires more information, correction, or follow-up
- `shortlisted`
  - passed an initial evaluation stage but not yet fully approved
- `approved`
  - accepted outcome
- `rejected`
  - declined outcome
- `withdrawn`
  - withdrawn by applicant or operator with audit note
- `closed`
  - operationally complete and no longer active
- `archived`
  - long-term retained historical record

### Submission Permission Boundaries

- editors or intake operators may accept and prepare submissions
- reviewers may assess and classify submissions
- approvers may confirm final approved or rejected outcomes when approval is required
- analysts may report on submission trends but not change statuses

### Ownership Boundary Rule

No user should act outside their assigned organisation or product context unless they hold `super_admin` authority or an explicitly documented cross-organisation role.

This boundary must eventually be enforced in application policies and service-level authorization, not only in CP visibility.

### Organisation Model

An `Organisation` is the highest operational owner in the platform.

It is responsible for:

- brand identity
- default public-domain ownership
- default mail-domain ownership
- product ownership
- organisation-level users and operator access boundaries
- compliance and contact defaults reused by its products

Recommended organisation-level responsibilities:

- owns one or more products
- provides the default domain fallback for its products
- provides shared sender defaults unless a product overrides them
- defines who may administer products under it

Suggested organisation-level fields:

- `name`
- `slug`
- `status`
- `default_domain`
- `default_mail_domain`
- `default_from_name`
- `default_reply_to`
- `compliance_profile`
- `support_contact`

### Organisation Persistence Rule

For `version/2`, organisations must be stored in dedicated application database tables.

Do not treat organisations as YAML-only or ad hoc Statamic configuration once implementation moves beyond documentation.

Reason:

- organisation ownership affects products, scope enforcement, analytics attribution, and domain fallback
- those concerns need stable relational identifiers

Recommended minimum persistence fields:

- `id`
- `name`
- `slug`
- `status`
- `default_domain`
- `default_mail_domain`
- `default_from_name`
- `default_reply_to`
- `compliance_profile`
- `support_contact`
- `created_at`
- `updated_at`

### Product Model

A `Product` is the main operating unit for newsletter publishing and form/data collection flows.

It is responsible for:

- owning a public-facing product identity
- mapping to a product blueprint inside its organisation publishing collection
- owning its subscription and public form surfaces
- owning its audience path
- owning product-specific sender and domain overrides when needed

Recommended product-level responsibilities:

- belongs to exactly one organisation
- maps to one product blueprint inside the owning organisation collection in `version/2`
- may own one or more forms
- may own one or more subscriber groups or preference paths
- may override organisation defaults for domain, sender, and branding

Suggested product-level fields:

- `organisation_id`
- `name`
- `slug`
- `status`
- `product_type`
- `public_domain`
- `mail_from_domain`
- `forms_domain`
- `domain_status`
- `domain_verified_at`
- `domain_is_primary`
- `primary_collection_handle`
- `blueprint_handle`
- `default_sender_profile`
- `default_template_family`
- `fallback_to_platform_domain`

### Product Persistence Rule

For `version/2`, products must be stored in dedicated application database tables.

Recommended minimum persistence fields:

- `id`
- `organisation_id`
- `name`
- `slug`
- `status`
- `product_type`
- `public_domain`
- `mail_from_domain`
- `forms_domain`
- `domain_status`
- `domain_verified_at`
- `domain_is_primary`
- `primary_collection_handle`
- `blueprint_handle`
- `default_sender_profile`
- `default_template_family`
- `fallback_to_platform_domain`
- `created_at`
- `updated_at`

### Statamic Editorial Ownership Rule

For the current `version/2` newsletter platform, Statamic editorial structure is interpreted as:

- collection = organisation workspace
- blueprint inside that collection = product

Examples:

- `insight_newsletters` is the Insight organisation workspace
- `insight_newsletters/data_dive.yaml` is the Data Dive product
- `insight_newsletters/marina_maitama.yaml` is the Marina and Maitama product
- `foundation_newsletters` is the Foundation organisation workspace
- each blueprint under `foundation_newsletters` is a Foundation product

The relational ownership layer remains the operational source of truth for permissions, domains, forms, subscribers, campaigns, and analytics. It must be populated from the approved Statamic collection/blueprint mapping instead of treating a collection alone as the product.

### Custom CP Styling Rule

Custom Statamic CP Blade surfaces must use plain CSS, not Tailwind utility classes, unless Tailwind is explicitly loaded and verified inside the Statamic CP bundle.

Rules:

- use Statamic CP-native classes first where they are stable and verified
- use feature-scoped plain CSS classes for custom layout and visual presentation
- dashboard widgets and other Statamic-rendered injected surfaces must load custom CSS through the Statamic CP stylesheet registry or another CP-head stylesheet path
- do not rely on body-local `<style>` tags for dashboard widget styling
- keep custom CP CSS namespaced by feature, for example `newsletter-widget-*`, `form-platform-*`, or `subscriber-cp-*`
- wide custom CP data tables must keep the Statamic page shell fixed and place horizontal or vertical scrolling inside a feature-scoped table wrapper; use sticky headers and sticky context columns where row orientation would otherwise be lost

### Product Type Rule

For `version/2`, treat the platform as supporting product types through behaviour, not through unrelated ad hoc configuration.

Recommended product types:

- `newsletter`
- `form_program`
- `hybrid`

Definitions:

- `newsletter`: newsletter-first product with optional supporting forms
- `form_program`: form/data-collection-first product with optional subscriber linkage
- `hybrid`: product that intentionally owns both newsletter and structured intake workflows

### Collection Ownership Rule

To avoid drift in `version/2`, use this default rule:

- each product owns exactly one primary public collection

That primary collection is the canonical publishing collection for the product.

Additional rule:

- if a team later wants multiple public collections under one product, that must be treated as a coordinator-reviewed exception, not an assumed default

Reason:

- it keeps product identity, domain routing, template ownership, audience mapping, and analytics attribution stable

### Form Ownership Rule

Forms do not float independently at platform level.

Each form must belong to:

- exactly one product
- and therefore exactly one organisation through that product

This keeps ownership clear for:

- domain resolution
- preference routing
- submission workflow
- audit history
- access control

### Scope Persistence Rule

Scope must not be inferred only from group names or role names.

For `version/2`, scope should be represented through dedicated relational records.

Recommended persistence model:

- `organisations`
- `products`
- `organisation_user_scope`
- `product_user_scope`
- optional `statamic_group_scope_map`

### Group-To-Scope Implementation Model

Use Statamic groups as operator-facing grouping and team-management containers, but do not rely on them as the canonical persistence layer for authorization scope.

Recommended rule:

- roles answer `what can this user do`
- relational scope records answer `where can this user do it`
- Statamic groups support operator management, CP visibility, and team membership workflows
- application policies and services must resolve authorization from both capability and persisted scope

### Statamic Operator Identity Link Rule

Statamic remains the CP authentication source. Relational scope evaluation must resolve the authenticated Statamic operator through an explicit identity link instead of replacing the Statamic auth provider.

Required rule:

- `users.statamic_user_id` stores the stable Statamic user identifier and is the authoritative runtime link
- runtime identity resolution uses only `statamic_user_id` and fails closed when the link is missing or ambiguous
- normalized email may be used only during an explicit provisioning or synchronization operation to locate an existing relational user
- provisioning a relational identity must not grant organisation or product scope
- ordinary CP requests must never lazily create or synchronize relational users

Recommended group categories:

- `organisation_group`
- `product_group`
- `operating_team`

### Audience Ownership Rule

Subscriber groups and preference subgroups must resolve through product ownership.

Recommended rule:

- each top-level subscriber group belongs to exactly one product
- each subgroup or preference segment belongs to exactly one top-level group
- cross-product audience reuse is not a default pattern in `version/2`

### Audience Persistence Rule

Audience ownership should be stored relationally through product ownership records, not only by convention.

Recommended minimum direction:

- subscriber groups should carry `product_id`
- subscriber groups may also carry `organisation_id` for query convenience and audit clarity
- subgroups should inherit product ownership through their parent group, even if `product_id` is denormalised later

## Campaign And Template Ownership Persistence

### Campaign Ownership Rule

Campaign records should be product-owned.

Recommended architecture-level fields:

- `product_id`
- `organisation_id`
- `created_by`

### Template Ownership Rule

Newsletter template ownership should also be product-owned by default.

Recommended architecture-level fields for any persisted template records:

- `product_id`
- `organisation_id`
- `template_key`
- `template_family`
- `status`
- `created_by`

## Permission Slug Model

Permission slugs must be explicit and stable before permission-sensitive implementation begins.

Recommended naming rule:

- use lowercase snake_case
- prefix by bounded area
- separate `view`, `manage`, `review`, `approve`, `send`, `export`, and `admin` capabilities

### Baseline Permission Slugs

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

### Authorization Rule

Permission slug grants are necessary but not sufficient.

Every sensitive action must require:

1. the permission slug
2. the correct organisation or product scope
3. the correct workflow state, where applicable

## Domain Resolution Architecture Rule

The platform should resolve domains through two explicit concerns:

- inbound request resolution
- outbound URL generation

Inbound resolution identifies the organisation, product, and public surface from the request host and path.

Outbound resolution generates the correct product, organisation, or platform URL for links and public surfaces.

The same fallback order must apply in both directions unless a surface-specific policy says otherwise.

## Domain Policy Rule

Each public surface should declare one of these policies:

- `product_required`
- `product_preferred`
- `organisation_fallback`
- `platform_only`

Default expectation for `version/2`:

- public landing pages: `product_preferred`
- hosted public forms: `product_preferred`
- public form submit endpoints: `product_preferred`
- unsubscribe and preferences pages: `product_preferred`
- browser-view newsletter pages: `product_preferred`
- internal CP and admin routes: `platform_only`
- webhook endpoints: `platform_only`

## Canonical URL Rule

Every public resource should resolve to one canonical URL.

If a request arrives on a non-canonical but valid fallback domain, the platform should have an explicit policy to either:

- serve it as-is
- redirect to canonical

Default recommendation:

- content and landing pages: redirect to canonical
- browser-view pages: redirect to canonical
- unsubscribe and preferences pages: avoid unnecessary redirect hops in the email click path
- machine-facing API endpoints: avoid decorative redirects unless required for correctness

## Domain Service Rule

Domain selection should not be hardcoded inside controllers, templates, or mailables.

It should be resolved through shared platform services such as:

- `DomainResolver`
- `ProductUrlGenerator`
- `RequestContextResolver`

If cross-product audience reuse is required later, it must be explicitly designed as a shared audience contract.

## Domain Model

Domain ownership should be a first-class platform capability.

Each product collection should be able to use its own domain when available, and fall back to the platform domain when it is not.

### Domain Resolution Rule

For each product:

1. use the product domain if it exists, is verified, and is enabled
2. else use the organisation default domain if defined
3. else use the platform domain

This fallback must be deterministic and centrally configured.

### Domain Surfaces

The resolved domain should be used consistently across:

- product landing pages
- public form pages
- remote form endpoints when surfaced publicly
- subscription confirmation pages
- unsubscribe and preferences pages
- browser-view newsletter pages
- campaign and transactional email links
- tracking and redirect links where product branding matters

### Domain Ownership Fields

The target platform should support domain configuration at the organisation and product levels.

Suggested fields:

- Organisation:
  - `default_domain`
  - `default_mail_domain`
- Product:
  - `public_domain`
  - `mail_from_domain`
  - `forms_domain`
  - `fallback_to_platform_domain`

### Domain Ownership Boundary

Ownership should be split like this:

- platform owns the final fallback domain
- organisation owns default brand-level domain and mail defaults
- product owns public-surface overrides when needed

Recommended surface ownership:

- `public_domain`
  - owner: product
  - fallback: organisation, then platform
- `forms_domain`
  - owner: product
  - fallback: organisation, then platform
- `mail_from_domain`
  - owner: product
  - fallback: organisation, then platform-approved mail domain policy
- unsubscribe/preferences/browser-view URL base
  - resolved from product public-surface domain policy, not per-template hardcoding

### Technical Rule

Domain selection should not be hardcoded inside controllers, templates, or mailables.

It should be resolved through a central platform service such as:

- `DomainResolver`
- `ProductUrlGenerator`

## Recommended Behavioural Rules

### Newsletter Signup Lifecycle

Recommended subscription intake lifecycle:

1. form submitted
2. subscriber record enters `pending`
3. signup email is queued
4. signup email is delivered
5. subscriber becomes `active`
6. subscriber is attached to the correct group and preference segment

This reflects the desired operational rule that activation should happen after delivery of the signup email.

### Subscriber Lifecycle States

The newsletter audience lifecycle should use these stable states:

- `pending`
- `active`
- `unsubscribed`
- `bounced`
- `complained`

### Subscriber Lifecycle Meanings

- `pending`
  - subscriber record exists
  - intended product and selected preferences are known
  - subscriber is not yet send-eligible
  - activation is waiting for successful signup email delivery
- `active`
  - subscriber may receive sends for the allowed product audience path
  - at least one active membership path exists
- `unsubscribed`
  - subscriber opted out intentionally
  - subscriber must not receive sends until explicitly resubscribed
- `bounced`
  - subscriber is suppressed because delivery failed in a way that should stop sending
  - reactivation must be explicit and auditable
- `complained`
  - subscriber is suppressed because of spam complaint or abuse feedback
  - this is treated as a highest-severity suppression state

### Transition Rules

- `pending -> active`
  - only after signup email delivery is confirmed through the platform delivery-truth path
- `active -> unsubscribed`
  - when the subscriber uses unsubscribe flow or an equivalent operator-approved action
- `active -> bounced`
  - when delivery reconciliation determines the address should be suppressed for bounce reasons
- `active -> complained`
  - when complaint or abuse feedback is received
- `unsubscribed -> pending`
  - when the same person deliberately signs up again
  - the record should not jump directly to `active`
  - it should go through the same delivery-confirmed reactivation path
- `bounced -> pending`
  - only through explicit recovery or operator-approved reactivation flow
  - never silently through ordinary campaign activity
- `complained`
  - should not be silently reactivated
  - any reactivation policy must be explicit, auditable, and separately approved by the coordinator or future compliance rule

### Reactivation Rule

Resubscribe behaviour should be explicit:

- a resubscribe action creates a `pending` reactivation state
- actual reactivation occurs only when the reactivation/signup email is delivered
- prior suppression reason must remain visible in history even after reactivation

### Product Scope Rule

Subscriber activation is product-scoped through audience membership, not globally through one flat list.

This means:

- a subscriber may exist once as a person/contact identity
- audience memberships determine which product audience paths are active
- operational logic must prevent one product form from silently assigning the subscriber into another product's audience path

### Group And Subgroup Rules

Subscriber membership should follow these rules:

- each subscriber group belongs to exactly one product
- each subgroup belongs to exactly one parent subscriber group
- subgroups represent preference segments or routing segments within that product audience
- a subscriber may belong to multiple active subgroups only when the form and product rules explicitly allow it
- subgroup membership must never bypass parent group ownership

### Group And Subgroup Lifecycle Rules

Audience structure lifecycle must be handled separately from subscriber identity lifecycle.

Stable audience-structure states:

- `active`
- `archived`
- `deleted`

Core audience-structure rules:

- a group or subgroup that has ever been used in campaign targeting is not hard-deletable
- a previously used group or subgroup must be archived instead
- archiving removes the structure from new targeting and new form-assignment flows
- archiving must preserve historical campaign, analytics, and audit readability

Deletion eligibility rules:

- `unused + empty` -> deletable
- `unused + has subscribers` -> reassign subscribers or remove their membership first, then delete
- `used before` -> archive only

Subscriber safety rules:

- deleting a group or subgroup must not delete subscriber identity records as a side effect
- subscriber records are separate from audience memberships
- subscriber membership must not be silently dropped without an explicit operator action
- where a group or subgroup still has subscribers, the preferred action is reassignment into another valid audience path before deletion completes

Operator workflow rule:

1. check whether the group or subgroup has ever been used in campaign targeting
2. if yes, archive it
3. if no, check whether active subscribers still belong to it
4. if subscribers remain, require reassignment or explicit membership removal
5. allow hard delete only after the structure is unused and empty

### Membership Behaviour

The platform should distinguish clearly between subscriber identity and membership state.

Subscriber profile:

- who the subscriber is
- current lifecycle state
- default contact details
- last lifecycle change timestamps

Membership state:

- which product group the subscriber belongs to
- which subgroups or preference segments are active
- when membership was created, changed, suppressed, or reactivated

### Preference Mapping Rule

Preferences selected from a form should map into subgroups only within the owning product audience tree.

Allowed behaviour:

- form option -> subgroup slug -> subgroup membership under the product's parent group

Disallowed behaviour:

- one form assigning preferences across multiple unrelated product audience trees
- free-form preference values becoming unmanaged audience structures without coordinator-approved rules
- deleting a preference-backed subgroup while subscribers still rely on it without reassignment or explicit membership removal

### Suppression Rule

Suppression must be enforced at send eligibility time, not only at presentation time.

Subscribers in these states are not send-eligible:

- `pending`
- `unsubscribed`
- `bounced`
- `complained`

Only `active` subscribers with valid audience membership are send-eligible.

### Profile, Membership, And Event History Boundaries

The platform should separate these concerns:

- profile
  - stable subscriber identity and current lifecycle state
- membership
  - product group and subgroup membership records
- event history
  - delivery events
  - unsubscribe events
  - bounce or complaint events
  - reactivation attempts
  - operator lifecycle actions

The exact persistence shape for event history should remain aligned with the analytics and reporting contract, not invented independently in the subscriber module.

### Form Modes

The platform should formally support at least these modes:

- `subscription`
- `application`
- `data_collection`

`subscription` writes into the newsletter audience model.

`application` and `data_collection` store structured submissions and may optionally create or link subscribers.

## Major Feature Areas

### Shared Platform Foundations

- organisation model
- product model
- domain model and resolution rules
- Statamic users, roles, and permissions
- audit trail
- workflow states
- integrations and delivery services
- environment and deployment runbooks

### Internal Module Boundaries

`version/2` should be implemented as a modular monolith with clear internal boundaries.

Recommended bounded modules:

- `Platform Foundations`
  - organisations
  - products
  - domains
  - shared settings
- `Identity And Access`
  - Statamic users
  - roles
  - groups
  - permissions
- `Audience`
  - subscribers
  - subscriber groups
  - preference segments
  - lifecycle state
- `Newsletter`
  - templates
  - campaigns
  - send workflow
  - browser-view/public newsletter surfaces
- `Forms And Submissions`
  - form definitions
  - public schema and submit APIs
  - submission review workflow
  - application/data collection logic
- `Delivery And Tracking`
  - provider delivery adapters
  - outbound send queueing
  - webhook ingestion
  - send-event reconciliation
- `Analytics And Reporting`
  - analytics contracts
  - reporting tables
  - dashboards
  - exports
- `Operations`
  - deployment runbooks
  - audit logs
  - support and rollback procedures

### Cross-Module Rule

Cross-cutting behaviour must flow through stable platform contracts.

Do not allow:

- forms to invent their own audience model
- newsletter flows to invent their own domain logic
- analytics to define business truth independently from lifecycle modules
- provider-specific behaviour to leak into templates or controllers

## Delivery Governance

The platform revamp must be managed through a coordinator-led session model.

### Governance Rule

Do not treat feature sessions as isolated work packets that will be reconciled only at the end.

Use:

- one coordinator session
- multiple managed feature sessions
- recurring integration checkpoints
- shared project documentation as the control plane

### Coordinator Responsibilities

The coordinator session owns:

- project-wide sequencing
- dependency management
- blocker tracking
- documentation alignment
- integration checkpoints
- final go or no-go decisions on cross-feature assumptions

### Feature Session Responsibilities

Each feature session must:

- read the mandatory project docs before work
- stay within its bounded scope
- report dependencies and blockers back to the coordinator
- update the project tracker and relevant feature docs
- avoid inventing cross-cutting behaviour without coordinator approval

### Integration Rule

No feature is considered ready for implementation merely because its own session finished.

A feature becomes implementation-ready only after:

1. its own docs are updated
2. its dependencies are resolved
3. the coordinator accepts the handoff
4. integration drift has been checked against adjacent features

## Scalability And Evolution Approach

The project should be built as a robust modular monolith first, with clear internal boundaries and a controlled path for scaling specific workloads later.

### Reporting Strategy

Heavy reporting should not continue to depend only on transactional tables once reporting load grows.

Recommended evolution:

1. start with read-optimised reporting tables inside the primary application database
2. move to warehouse sync only when reporting volume, retention, or query complexity justifies it

Recommended warehouse direction:

- first choice: managed PostgreSQL reporting replica or dedicated reporting database if needs are still moderate
- next choice: BigQuery if the team wants cloud-scale analytics and flexible SQL over large event history
- alternative: ClickHouse if self-hosted or lower-cost high-volume event analytics becomes important

Current recommendation for this project:

- do not introduce a warehouse immediately
- start with reporting tables or materialised reporting models fed from campaign sends, webhook events, and submissions
- promote to a warehouse only after clear reporting bottlenecks appear

### Analytics Driver Strategy

The platform should define the analytics driver contract now, even though only the database-backed implementation should be used for production reads initially.

#### Current Rule

- production read path: `database` only
- ClickHouse: not yet an active production read backend

#### Target Configuration Direction

Suggested config shape:

- `ANALYTICS_DRIVER=database|clickhouse`

That env value must select a real implementation behind a stable contract, not bypass application logic directly.

#### Driver Contract

The analytics layer should be implemented behind stable interfaces such as:

- `AnalyticsReaderInterface`
- `AnalyticsWriterInterface`
- `AnalyticsEventStoreInterface`

Suggested responsibilities:

- `AnalyticsReaderInterface`
  - campaign summary metrics
  - delivery/open/click/bounce aggregates
  - subscriber growth and lifecycle reporting
  - form and submission reporting
  - dashboard query methods
- `AnalyticsWriterInterface`
  - write or project canonical analytics events
  - update read-optimised reporting tables
  - support future secondary projection to ClickHouse
- `AnalyticsEventStoreInterface`
  - persist canonical analytics events before they are transformed into reporting views when that becomes necessary

#### Contract Rule

These interfaces define reporting and projection behaviour only.

They must not become the source of truth for:

- subscriber permission decisions
- workflow authorization
- domain-management authority
- unresolved organisation or product persistence details

#### Canonical Reporting Domains

The analytics contract must cover three reporting domains:

- newsletter analytics
- subscriber and audience analytics
- form and submission analytics

#### Reporting Model Rule

Use canonical reporting tables or read models for metrics and dashboards.

Do not make dashboard reads depend directly on raw operational joins once the reporting shape is stable.

#### Canonical Rule

The database-backed analytics implementation is the canonical implementation until internal analytics definitions, reporting tables, and business metrics are stable.

#### Current Reporting Source Rule

For `version/2`, the analytics system must treat the application database as the only production reporting read path until parity, correctness, and operational behaviour are proven elsewhere.

#### ClickHouse Adoption Rule

ClickHouse should only be considered when explicitly initiated as a separate future track.

If that future track is approved, adopt it in phases:

1. define the contract and config now
2. stabilise the database analytics model first
3. start a dedicated ClickHouse implementation track
4. validate metric parity between both backends
5. only then allow `clickhouse` as a production read driver

#### Non-Negotiable Rule

Do not treat a future env switch as safe unless backend parity has been explicitly validated.

### Google Analytics 4 Boundary

`GA4` can be integrated as a complementary analytics source, but it must not be treated as the source of truth for internal platform operations.

Recommended GA4 usage:

- public website traffic analytics
- product landing-page performance
- signup funnel behaviour
- campaign click-through to website content
- source, medium, and acquisition attribution

Do not use GA4 as the primary system for:

- subscriber state and lifecycle truth
- campaign delivery reconciliation
- webhook processing truth
- submission workflow reporting
- reviewer or operator audit reporting
- queue and operational health analysis

Platform rule:

- GA4 is for behavioural and acquisition analytics
- internal reporting tables are for operational and workflow analytics

### Canonical Analytics Metrics

The internal analytics contract should support, at minimum, these metric families.

#### Newsletter Metrics

- campaigns created
- campaigns approved
- campaigns scheduled
- campaigns sent
- campaigns partial
- campaigns failed
- recipients queued
- recipients sent
- recipients delivered
- recipients opened
- recipients clicked
- recipients bounced
- recipients complained
- recipients unsubscribed after send
- delivery rate
- open rate
- click rate
- click-to-delivery rate

#### Subscriber And Audience Metrics

- subscribers pending
- subscribers active
- subscribers unsubscribed
- subscribers bounced
- subscribers complained
- subscribers reactivated
- net subscriber growth
- activation conversion from pending to active
- suppression counts by reason
- audience size by product
- subgroup size by product

#### Form And Submission Metrics

- submissions received
- submissions by mode
- submissions under review
- submissions approved
- submissions rejected
- submissions withdrawn
- submissions closed
- subscription-form conversions
- application completion rate where defined
- data collection volume by product and form

### Reporting Table Direction

The internal reporting model should eventually include read-optimised reporting tables or equivalent read models for:

- campaign performance summaries
- campaign recipient status aggregates
- subscriber lifecycle summaries
- audience membership summaries
- form submission summaries
- submission workflow summaries
- daily product analytics rollups

Exact physical schema remains implementation work and must respect unresolved persistence and scope decisions already recorded by the coordinator.

### Service Boundary Strategy

The codebase should remain one application, but these concerns should be split into clearer internal services or modules:

- delivery service
- webhook ingestion service
- analytics and reporting service
- subscriber lifecycle service
- submission workflow service

This is a code-organisation and responsibility split first, not a microservice split.

### Search Strategy

Add dedicated operator search only if admin search becomes a real bottleneck.

Recommended default choice:

- `Meilisearch`

Reason:

- open source
- simpler to operate
- fast enough for typical internal operator search
- lighter integration and maintenance burden than OpenSearch

OpenSearch is also open source, but it is heavier and is better reserved for cases where the project truly needs:

- very large-scale indexing
- more advanced relevance tuning
- broader log/search platform use beyond app operator search

Current recommendation for this project:

- prefer `Meilisearch` if search scaling becomes necessary
- use `OpenSearch` only if the search problem becomes much larger than app-side operator lookup

### Event Bus Strategy

Do not introduce a separate event bus early.

Use Laravel events, jobs, queues, and internal service boundaries first.

Add a proper event bus only when workflow complexity genuinely requires:

- multiple independently evolving consumers
- replayable domain events
- cross-service integration beyond one application boundary
- strict decoupling between operational workflows and analytical/event consumers

Candidate future event-bus options can be documented later if that threshold is reached.

### Newsletter Platform

- product collections
- editorial templates
- product-domain newsletter routing
- branded unsubscribe/preferences/browser-view links
- audience targeting
- campaign operations
- analytics and deliverability
- preference management

### Form And Data Collection Platform

- form builder strategy
- reusable templates
- embed/API contract
- allowed-platform governance
- structured submission storage
- review and review-status workflows
- custom hardcoded extensions for specialised forms

## Feature List

### Must-Have Features

- multi-organisation support
- product-to-collection mapping
- product domain with platform fallback
- form-to-group and preference-to-subgroup mapping
- role-based user access
- campaign workflow
- subscriber lifecycle tracking
- submission lifecycle tracking
- analytics and delivery visibility
- webhook and provider reconciliation
- project-wide update tracking in docs

### Strongly Recommended Features

- approval workflow before send
- dynamic audience segmentation
- per-form allowed platform rules
- API and embed governance
- reusable form templates with extension points
- operator audit logs
- review queues for application/data submissions
- read-optimised reporting path for analytics growth
- GA4 integration only for external behavioural analytics
- analytics driver contract with database-first production reads

## Documentation Rule

All future project-level direction should be anchored here first.

This file is the highest-level planning and alignment document in the repository.
