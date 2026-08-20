# Shared Platform Foundations Implementation Notes

## Session 2 Notes

### Intended Statamic Foundation

- use Statamic `roles` for capabilities
- use Statamic `groups` for operational scope
- keep authorization enforcement in Laravel policies or service-layer authorization, not only in CP navigation or form visibility

### Expected Future Permission Areas

Recommended permission groupings:

- organisation management
- product management
- campaign operations
- form management
- submission review
- audience and subscriber management
- analytics and exports
- platform administration

### Non-Breaking Implementation Rule

Do not hardwire role checks directly into controllers or views as the long-term authorization model.

Prefer:

- policies
- gate checks
- service-layer authorization helpers

This will reduce cross-feature drift when newsletter, form, and analytics modules start implementing their own actions.

### Deferred Implementation Detail

The exact mapping between:

- Statamic group structure
- organisation records
- product records

is now resolved conceptually:

- organisations and products are persisted in dedicated database tables
- relational scope records are the canonical authorization scope layer
- Statamic groups remain operator-facing team containers and optional mapped scope helpers

## Session 9 Scaffolding Notes

The first controlled code session added shared platform scaffolding only.

Implemented:

- analytics contracts:
  - `AnalyticsReaderInterface`
  - `AnalyticsWriterInterface`
  - `AnalyticsEventStoreInterface`
- domain contracts:
  - `DomainResolverInterface`
  - `ProductUrlGeneratorInterface`
  - `RequestContextResolverInterface`
- config-driven platform bindings in `config/platform.php`
- provider bindings in `AppServiceProvider`
- placeholder database analytics classes
- explicit unavailable analytics placeholders for the `clickhouse` driver
- placeholder domain service implementations

Rule preserved:

- no newsletter, form, permission-enforcement, lifecycle, or domain-management feature logic was implemented in this session
- persistence, permission, and scope questions were intentionally left for a later foundation-definition session

Session 10 resolves those blockers at architecture level for later implementation planning. Implementation still remains additive and migration-safe, but later sessions should no longer treat the persistence and scope model as undefined.

## Session 11 Persistence Scaffolding Notes

This session implements only additive shared persistence foundations.

Implemented:

- relational foundation models:
  - `Organisation`
  - `Product`
  - `OrganisationUserScope`
  - `ProductUserScope`
  - `StatamicGroupScopeMap`
- additive database tables:
  - `organisations`
  - `products`
  - `organisation_user_scope`
  - `product_user_scope`
  - `statamic_group_scope_map`
- additive `User` relationships for organisation and product scope links
- pure unit coverage for model-level scaffolding without requiring a live database

Rule preserved:

- no newsletter sending, form handling, lifecycle enforcement, permission enforcement, or domain-management workflow logic was added
- existing runtime flows were not cut over to the new persistence
- campaign, template, subscriber, and audience tables were not rewritten in this session

## Session 12 Authorization Scaffolding Notes

This session implements only additive ownership-aware authorization scaffolding on top of the Session 11 persistence layer.

Implemented:

- permission and scope contracts:
  - `PermissionRegistryInterface`
  - `ScopeResolverInterface`
- shared authorization scaffolding:
  - `PermissionSlugs`
  - `PlatformPermissionRegistry`
  - `ScopeResolver`
  - `ResolvesAuthorizationScope`
- config-driven authorization registration in `config/platform.php`
- container bindings for authorization contracts in `AppServiceProvider`
- additive `User` helpers for active organisation and product scope queries
- pure unit coverage for permission-slug registration and active scope resolution

Rule preserved:

- relational organisation and product scope records remain the canonical scope layer
- Statamic roles remain capability inputs only in this phase
- no feature runtime was cut over to enforced policy checks in this session
- no newsletter, form, subscriber lifecycle, domain-management, or analytics runtime authorization behaviour was rewritten in this session
- unresolved domain-verification authority and complained-subscriber recovery policy remain deferred

## Session 13 Domain Scaffolding Notes

This session implements only additive shared domain scaffolding on top of the Session 11 persistence layer and the Session 21 approved architecture.

Implemented:

- additive domain config structure for:
  - surface policies
  - surface-to-domain-field mapping
  - scaffold URL path templates
  - platform scheme and platform domain fallback
- shared domain resolver scaffolding that can:
  - resolve product-facing domains with fallback order `product -> organisation -> platform`
  - resolve organisation fallback domains
  - evaluate request host context against product, organisation, and platform scope
- shared request-context resolver scaffolding as a thin wrapper over the domain resolver
- shared product URL generator scaffolding for:
  - landing pages
  - hosted form pages
  - hosted form submit endpoints
  - preferences pages
  - unsubscribe pages
  - browser-view placeholders
  - relative campaign link expansion
- pure unit coverage for fallback order, request-context detection, and scaffold URL generation

Rule preserved:

- no runtime route definitions, controllers, middleware, or existing link-generation flows were cut over in this session
- no newsletter, form, preferences, unsubscribe, browser-view, CP, or webhook runtime surface changed behaviour yet
- product domain verification authority remains unresolved and was not encoded beyond documented verified-status checks on product records
- database remains the only production read path; no analytics or ClickHouse work was introduced here

Deferred:

- final canonical redirect behaviour across public surfaces
- final browser-view route/storage implementation
- final domain verification and CP/operator authority workflow
- actual runtime adoption of these helpers by newsletter, forms, preferences, unsubscribe, and browser-view features

## Session 14 Form URL Consumer Notes

This session introduces the first runtime consumer of the shared domain helpers, limited to hosted form URLs and closely related public form links.

Implemented:

- `SubscriptionFormService` now resolves form submit URLs through the shared product URL generator when a matching product can be inferred from the form collection handle
- hosted application pages now generate schema, submit, and auxiliary location links against the shared form-domain resolution path
- route names remain the runtime source for path structure while the shared domain resolver supplies the preferred host

Rule preserved:

- no broad host-based routing or middleware cutover was introduced
- newsletter browser-view, tracking, preferences, unsubscribe, and mail-link generation remain untouched in this session
- existing runtime behaviour remains unchanged when no matching product or verified domain context exists

Deferred:

- canonical redirects between platform and branded form hosts
- domain-aware mail links for preferences and unsubscribe
- broader newsletter-surface adoption of the shared URL generator

## Session 15 Ownership Column Scaffolding Notes

This session implements only additive ownership-column scaffolding on existing product-owned relational records.

Implemented:

- additive nullable ownership columns on:
  - `subscriber_groups`
  - `campaigns`
  - `email_templates`
- model-level ownership relations from those records back to:
  - `Organisation`
  - `Product`
- additive inverse relationships on `Organisation` and `Product` for:
  - subscriber groups
  - campaigns
  - email templates
- pure unit coverage for the ownership scaffolding and subgroup inheritance boundary

Rule preserved:

- no existing runtime flow now requires `organisation_id` or `product_id` on these records
- no speculative ownership backfill was introduced
- `subscriber_sub_groups` were not given direct ownership keys because the approved baseline keeps subgroup ownership inherited from the parent group
- no authorization, query-scoping, routing, delivery, or analytics cutover was introduced in this session

Deferred:

- any safe backfill or mapping strategy for historical product-owned records
- ownership-aware read/query boundaries in CP and runtime flows
- form-owned relational record ownership columns where future sessions explicitly approve them

## Session 16 Ownership-Aware Read Boundary Notes

This session implements reusable ownership-aware Eloquent query scopes for the existing product-owned relational records.

Implemented:

- shared `HasOwnershipReadScopes` support for:
  - one explicit product
  - an explicit set of allowed products
  - one explicit organisation
  - a combined organisation and product boundary
- the shared scopes are applied only to:
  - `subscriber_groups`
  - `campaigns`
  - `email_templates`
- an empty allowed-product set fails closed and returns no records
- nullable legacy rows are excluded whenever an ownership scope is explicitly applied
- focused unit coverage proves the same boundary behaviour across all three product-owned models

Rule preserved:

- no existing controller, command, job, public route, CP screen, or service query was cut over in this session
- no ownership value is assigned or backfilled by these read helpers
- the scopes accept explicit ownership identifiers or persisted ownership models; they do not infer ownership from Statamic roles or groups
- callers may compose `ScopeResolverInterface::productIds()` with `ownedByProducts()` after the relevant consumer's visibility rules are approved
- database queries remain the only production read path

Deferred:

- user-derived visibility helpers until organisation-wide cross-product visibility is explicitly decided
- consumer adoption until the selected query path can rely on populated ownership values without hiding valid legacy records
- write-path ownership assignment, historical backfill, policy enforcement, and broad feature cutover

## Session 17 Subscriber Group Ownership Write Notes

This session implements ownership assignment on one controlled subscriber-group write path only.

Chosen path:

- the manual Insight provisioning flow resolves the product mapped to `insight_newsletters` before making provisioning changes
- its `SubscriberGroup` create/update step now assigns both product and organisation ownership
- this path was selected because the collection handle is fixed in code, `products.primary_collection_handle` is the accepted relational mapping, and the command is isolated from public and CP workflows

Implemented:

- shared `SubscriberGroupOwnershipWriter` support for:
  - resolving exactly one product from its primary collection handle
  - creating or updating a subscriber group with ownership assigned through the existing `product()` and `organisation()` relationships
  - assigning ownership to an existing unowned group when that same controlled write path runs
  - rejecting silent reassignment when existing ownership conflicts
- the Insight provisioner resolves ownership before any collection, blueprint, group, or form provisioning mutation
- focused unit coverage for successful assignment and missing, ambiguous, or conflicting ownership context

Rule preserved:

- no historical or bulk backfill was introduced
- no other subscriber-group, campaign, or email-template write path was changed
- no ownership-aware read consumer, policy, public workflow, CP workflow, domain, lifecycle, form-submission, or analytics behaviour was changed
- organisation-wide cross-product visibility was not assumed
- database records remain the ownership source; Statamic roles and groups are not used to infer ownership

Deferred:

- adoption by CP group creation, Foundation observer provisioning, campaign creation, template creation, or any other write path
- a coordinator decision on whether `products.primary_collection_handle` should receive a database uniqueness constraint
- historical ownership backfill and broad read-path cutover

## Session 18 Campaign Ownership Write Notes

This session implements ownership assignment on one controlled campaign write path only.

Chosen path:

- the operator-only `newsletter:seed-demo-campaign` command resolves the product mapped to its explicit `--collection` value before opening its database transaction
- its `Campaign` create/update step now assigns both product and organisation ownership
- this path was selected because persisted email templates have no operational write path and CP campaign creation would introduce user-facing failure behaviour beyond this session's metadata scope

Implemented:

- shared `ProductOwnershipResolver` support extracted from the Session 17 subscriber-group writer for:
  - resolving exactly one product from its primary collection handle
  - resolving and validating the product's persisted organisation
- shared `CampaignOwnershipWriter` support for:
  - creating or updating a campaign with ownership assigned through the existing `product()` and `organisation()` relationships
  - assigning ownership to an existing unowned demo campaign when that same controlled write path runs
  - rejecting silent reassignment when existing ownership conflicts
- the demo command resolves ownership before its transaction, including before the existing `--fresh` deletion path
- focused unit coverage for successful assignment and missing, ambiguous, or conflicting ownership context

Rule preserved:

- no historical or bulk backfill was introduced
- no CP campaign creation, campaign lifecycle, campaign audience, delivery, analytics, domain, or public workflow behaviour was changed
- no email-template write path was invented where none currently exists
- no ownership-aware read consumer was activated
- organisation-wide cross-product visibility and collection-handle uniqueness were not assumed
- the demo command's existing subscriber-group and audience writes remain unchanged and outside this session

Deferred:

- ownership assignment for CP campaign creation until product selection and failure handling are approved
- any operational email-template writer until its product source and workflow are defined
- ownership validation or assignment for the demo command's audience group
- a coordinator decision on a database uniqueness constraint for `products.primary_collection_handle`
- historical ownership backfill and broad read-path cutover

## Session 19 Demo Audience Ownership Consistency Notes

This session brings the demo campaign's top-level audience group into the same controlled ownership context as the Session 18 demo campaign write.

Chosen path:

- `newsletter:seed-demo-campaign` resolves one product from its explicit `--collection` value before opening its transaction
- the command now passes that same persisted product to both the subscriber-group writer and campaign writer
- this path is isolated to demo provisioning and does not alter CP or public audience workflows

Implemented:

- the demo command's group create/update step now uses `SubscriberGroupOwnershipWriter`
- demo subscriber groups receive `product_id` and `organisation_id` through existing Eloquent ownership relationships
- the existing shared product resolver remains the single source for both demo group and campaign ownership
- focused consistency coverage proves:
  - group and campaign ownership identifiers match
  - missing or duplicate product mappings prevent both writes
  - conflicting existing group ownership prevents a partial campaign write

Rule preserved:

- no bulk or historical backfill was introduced
- no non-demo group write path was changed
- no subgroup ownership columns were introduced; subgroups continue inheriting ownership from their parent group
- no broad audience reads, policy enforcement, lifecycle, domain, delivery, or analytics behaviour changed
- organisation-wide cross-product visibility was not assumed

Deferred:

- ownership assignment for CP group creation and other non-demo provisioners
- broad ownership-filtered audience or campaign reads
- a coordinator decision on a database uniqueness constraint for `products.primary_collection_handle`
- historical ownership backfill

## Session 20 CP Campaign Product Selection Notes

This session inspected the existing CP campaign create/store path but did not change runtime behaviour because the authenticated operator cannot yet be resolved safely to the canonical relational scope model.

Inspected path:

- `CampaignController::create` currently exposes the configured newsletter collections and audience tree without a product selector
- `CampaignController::store` currently writes campaigns directly and records the authenticated Statamic user UUID in `created_by`
- the CP guard uses the Statamic provider with the file-backed user repository
- canonical product visibility is implemented through `ScopeResolverInterface`, which requires an `App\Models\User` backed by the numeric `users.id` foreign key in `product_user_scope`

Blocker:

- no accepted identity bridge maps an authenticated Statamic file user UUID to the relational `App\Models\User` used by the scope records
- resolving that identity by email, creating a shadow user automatically, or deriving products from Statamic groups would introduce behaviour not approved by the source of truth
- listing every active product would assume cross-product visibility, which remains explicitly unresolved

Rule preserved:

- no product selector, request acceptance, campaign ownership assignment, or CP visibility rule was introduced
- Statamic roles and groups were not treated as canonical ownership sources
- the existing CP campaign create/store behaviour remains unchanged
- database records remain the intended ownership and scope source

Required next step:

- the coordinator must approve a single operator identity model that connects Statamic authentication to relational scope records
- that implementation must define stable identity keys, provisioning/synchronisation behaviour, missing and duplicate identity handling, and migration of existing operators
- Session 20 should then resume using direct active `product_user_scope` records, validate that the selected product's primary collection matches the campaign collection, and reuse `CampaignOwnershipWriter`

## Session 21 Statamic Relational User Identity Bridge Notes

This session implements the additive identity bridge required for future CP scope evaluation without replacing Statamic authentication.

Identity model:

- `users.statamic_user_id` is a nullable unique link to the stable Statamic user identifier
- the Statamic identifier is the only authoritative runtime lookup key
- normalized email is used only by the explicit provisioning command to find an existing relational user before the stable link exists
- runtime requests do not fall back to email and do not create or synchronize users

Provisioning model:

- `platform:provision-statamic-user {identifier}` explicitly provisions or synchronizes one relational identity
- the identifier may be the Statamic user ID or email, but Statamic itself must resolve the operator first
- an existing relational user with the same normalized email is linked when it has no conflicting Statamic link
- otherwise a relational user is created with an unknown generated password because Laravel's relational user provider is not the active auth provider
- rerunning the command synchronizes the linked relational name and email
- missing, invalid, duplicate, or conflicting identities fail closed

Implemented:

- `StatamicUserIdentityBridgeInterface` and its shared implementation
- config-driven container binding for the bridge
- explicit console command registration
- additive nullable unique `statamic_user_id` user column
- focused unit coverage for exact linked resolution, no email fallback, duplicate-link rejection, first-run provisioning, existing-user linking, and conflicting-link rejection

Rule preserved:

- Statamic remains the CP authentication provider and file-backed user repository
- no organisation or product scopes are granted by identity provisioning
- no Statamic role or group is treated as canonical scope
- no CP campaign, group, policy, lifecycle, domain, or analytics flow was changed
- database records remain the only production scope read path

Operational sequence:

1. Run the additive migration.
2. Run `php artisan platform:provision-statamic-user <statamic-id-or-email> --name="Operator Name"` for each operator who needs relational scope.
3. Create or maintain `organisation_user_scope` and `product_user_scope` separately through an approved scope-management path.
4. Future CP sessions may resolve the authenticated Statamic user through the bridge and then pass the resulting relational user to `ScopeResolverInterface`.

Repository note:

- `docs/` is currently ignored by `.gitignore`; this session updated the required documentation on disk but did not change the project-wide documentation tracking policy

## Session 22 Scoped CP Campaign Product Selection Notes

This session resumes the Session 20 CP campaign create/store path after the Session 21 operator identity bridge removed the blocking identity mismatch.

Implemented boundary:

- the existing campaign create page resolves the authenticated Statamic operator through `StatamicUserIdentityBridgeInterface`
- only active products from that relational user's direct active `product_user_scope` records are offered
- collection choices on the create page are limited to the primary collections owned by those scoped products
- the store path requires an explicit `product_id` and re-evaluates identity, active product scope, product status, and primary collection consistency
- missing identity, no usable active scope, an unscoped product, an inactive product, or a product/collection mismatch fails closed
- valid writes use `CampaignOwnershipWriter::createForProduct()` to assign both `product_id` and `organisation_id`

Rule preserved:

- Statamic roles and groups are not used to infer product access
- organisation scope does not expand into product scope
- no product choice is inferred from collection handles alone
- campaign edit/update, indexes, read boundaries, policies, lifecycle, domains, analytics, templates, and group creation remain unchanged
- no historical campaign ownership backfill or nullable-row cleanup was introduced

Deferred boundary:

- existing campaign audience synchronization remains collection-based and was not converted to ownership enforcement in this session
- CP audience-group ownership consistency must be handled in a separate controlled session before broad campaign policy or read cutover
- historical nullable audience ownership remains outside this session

## Session 23 Campaign Audience Ownership Consistency Notes

This session applies product ownership consistency to the scoped CP campaign create/store path introduced in Session 22.

Implemented boundary:

- `CampaignAudienceOwnershipService` validates the complete audience selection before `CampaignOwnershipWriter` creates the campaign
- send-to-all requires exactly one top-level subscriber group within the selected product and organisation ownership boundary and matching the product's primary collection
- selected subgroups inherit ownership only through their persisted top-level subscriber group; every selected subgroup parent must match the campaign product, organisation, and primary collection
- missing, duplicate, ambiguous, unowned, or cross-product audience records fail closed with field-specific validation errors
- inactive products or organisations fail before audience assignment; subscriber groups and subgroups have no accepted status column, so this session does not invent an independent audience-active state
- the create page limits its audience tree to product-owned groups from the operator's scoped products and displays a group only when both selected product and collection match
- validated audiences are assigned from resolved models rather than re-resolving submitted identifiers after campaign creation

Rule preserved:

- an empty selected-subgroup list remains valid for a draft with no audience; it does not imply send-to-all
- subgroup ownership remains inherited from the top-level group and no subgroup ownership columns were introduced
- campaign edit/update continues using the existing compatibility path and was not brought into this create-only cutover
- no historical backfill, broad audience read cutover, policy enforcement, CP group product selection, lifecycle, domain, delivery, or analytics behaviour changed
- organisation-wide cross-product visibility was not assumed

Verification:

- focused audience ownership, selector, and campaign writer coverage passed with `17 tests, 36 assertions`
- the in-memory foundation regression suite passed with `48 tests, 142 assertions`
- focused Pint checks and Blade template compilation passed
- application-boot coverage remains blocked in the sandbox because Statamic attempts to connect to the configured MySQL test database before tests execute

Deferred:

- CP campaign edit/update audience ownership parity
- an explicit product-selection and ownership path for CP audience-group creation
- historical nullable ownership backfill and broad ownership-aware campaign or audience reads
- real-database CP verification after migrations, identity provisioning, products, groups, and direct product scopes are configured

## Session 24 CP Campaign Edit Update Ownership Parity Notes

This session extends the accepted Session 22-23 campaign ownership boundary to the existing CP edit/update path.

Implemented boundary:

- `ScopedCampaignProductSelector::resolveCampaign()` requires a persisted campaign with matching product and organisation ownership, an active product and organisation, an exact primary collection match, and the current operator's direct active product scope
- edit access fails closed for missing, conflicting, inactive, unscoped, or collection-inconsistent campaign ownership
- campaign product ownership is immutable in the edit UI; collections, Statamic entries, and audience groups exposed by that page are restricted to the resolved product
- persisted campaign audiences are validated before the edit page renders, preventing malformed or cross-product rows from being silently normalised by a later save
- every update rechecks operator scope and submitted collection consistency before validating the complete submitted audience with the Session 23 ownership service
- `CampaignOwnershipWriter::updateForProduct()` preserves exact campaign ownership while applying approved field updates
- campaign and audience replacement occur in one database transaction after all scope and audience validation passes

Rule preserved:

- only draft and scheduled campaigns remain editable
- product reassignment is not supported by edit/update
- send, schedule, and draft action semantics remain unchanged
- no historical backfill or automatic repair of legacy ownership was introduced
- index, show, send, cancel, retry, preview, export, destroy, and other campaign actions remain outside this narrow cutover
- no CP audience-group product selection, broad policy/read cutover, lifecycle, domain, template, or analytics behaviour changed
- organisation-wide cross-product visibility was not assumed

Verification:

- focused campaign scope, ownership writer, and audience parity coverage passed with `26 tests, 57 assertions`
- the in-memory foundation regression suite passed with `57 tests, 163 assertions`
- syntax checks, focused Pint checks, and Blade compilation passed
- `AudienceResolverTest` and `NewsletterCampaignLifecycleTest` remain blocked during Statamic bootstrap because the sandbox cannot connect to the configured MySQL test database

Deferred:

- scope enforcement for campaign actions other than edit/update
- CP audience-group product selection and ownership assignment
- historical ownership backfill and broad ownership-aware campaign reads
- real-database CP edit/update verification with provisioned operators and representative owned audiences

## Session 25 CP Audience Group Product Selection Notes

This session applies explicit product selection and direct operator scope to the existing CP top-level audience-group create/edit/update path.

Implemented boundary:

- `ScopedSubscriberGroupProductSelector` composes the accepted Statamic identity and direct active product-scope resolver used by campaign workflows
- the create page offers only active products in the current operator's direct active scope whose organisations are active and whose primary collections exist in the newsletter registry
- the selected product determines `collection_handle`; CP requests do not submit or infer an independent collection-to-product mapping
- `SubscriberGroupOwnershipWriter::createForProduct()` assigns both product and organisation ownership on creation
- edit access requires existing non-null group ownership matching the active scoped product, its organisation, and its primary collection
- product and collection ownership are immutable in the edit UI; update rejects a conflicting submitted product and writes through `SubscriberGroupOwnershipWriter::updateForProduct()`
- nested subgroup create/update/delete operations inherit the same resolved parent-group scope and require an exact subgroup-to-parent route match

Rule preserved:

- subgroups retain only `subscriber_group_id`; no direct subgroup ownership columns or model redesign were introduced
- unowned or conflicting historical groups fail closed and are not repaired automatically
- the broad CP group index and top-level group deletion remain unchanged and outside this write-path session
- no broad audience read cutover, campaign runtime, subscriber lifecycle, domain, template, or analytics behaviour changed
- Statamic roles and groups are not treated as canonical ownership sources
- organisation-wide cross-product visibility was not assumed

Verification:

- focused group product selector and ownership writer coverage passed with `13 tests, 32 assertions`
- the in-memory directly affected foundation regression suite passed with `65 tests, 185 assertions`
- syntax checks, focused Pint checks, and Blade compilation passed
- the legacy group controllers retain pre-existing whole-file formatting debt and were not broadly reformatted
- `AudienceResolverTest` remains blocked during Statamic bootstrap because the sandbox cannot connect to the configured MySQL test database

Deferred:

- product-scoped filtering for the CP group index
- ownership enforcement for top-level group deletion
- historical group ownership repair or backfill
- real-database CP create/edit/update and nested subgroup verification with a provisioned operator

## Session 26 Group Index Visibility And Delete Enforcement Notes

This session applies the accepted direct product-scope boundary to the CP top-level audience-group index and delete action.

Implemented boundary:

- `ScopedSubscriberGroupProductSelector::groupsFor()` returns only groups whose product is in the current operator's direct active relational scope
- an index-visible group must have an exact `product_id`, `organisation_id`, and `collection_handle` match to that active product and its active organisation
- missing operator identity or no active direct product scope returns an empty CP index rather than exposing unscoped groups
- top-level deletion uses `ScopedSubscriberGroupDeletionService`, which reuses `resolveGroup()` and refuses the delete unless the persisted ownership triplet remains exact and in scope
- successful top-level deletion retains the existing database cascade for subgroups and subscriber-subgroup pivots
- subgroup ownership remains inherited through the parent top-level group

Rule preserved:

- no organisation-wide or cross-product visibility was introduced
- no unowned, conflicting, inactive, or historical record is repaired or made visible
- no audience read outside the controlled CP group index was changed
- no campaign runtime, lifecycle, domain, form, template, or analytics behaviour changed
- existing delete dependency semantics were not redesigned; polymorphic campaign audience records do not currently have a database foreign-key cascade and need separate integrity policy before cleanup behaviour changes

Verification:

- focused group visibility and guarded delete coverage passed with `7 tests, 30 assertions`
- the in-memory directly affected foundation regression suite passed with `67 tests, 202 assertions`
- syntax checks passed for the changed PHP files
- focused Pint checks passed for the new and directly changed service and test files
- the cumulative legacy `GroupController` still has pre-existing whole-file Pint debt; Session 26 did not perform an unrelated controller-wide formatting rewrite
- `AudienceResolverTest` remains blocked during Statamic bootstrap because the sandbox cannot connect to the configured MySQL test database

Deferred:

- historical group ownership repair or backfill
- broad audience read cutover outside the CP group index
- campaign-audience dependency policy for deletion and any cleanup of historical polymorphic references
- real-database CP index and delete verification with a migrated database, provisioned operator, direct product scope, and representative owned/unowned groups

## Session 27 Audience Structure Lifecycle Enforcement Notes

This session applies the approved delete-safety part of the audience-structure lifecycle policy to the existing scoped CP group and subgroup deletion paths.

Implemented boundary:

- top-level group deletion still requires the Session 26 product-scope resolver to return an exact active product match
- a group cannot be hard-deleted when it or any child subgroup has historical campaign audience usage
- a group cannot be hard-deleted while any child subgroup still has active subscriber membership rows
- subgroup deletion now uses `ScopedSubscriberGroupDeletionService` instead of directly deleting the model
- a subgroup cannot be hard-deleted when it has historical campaign audience usage
- a subgroup cannot be hard-deleted while it still has active subscriber membership rows
- campaign audience checks recognise both persisted morph aliases and class names so the guard remains stable in booted and isolated test contexts

Rule preserved:

- subscriber identity records are never deleted as a side effect of group or subgroup lifecycle changes
- no cross-product audience sharing or broad audience read path was introduced
- no historical ownership repair, campaign-audience cleanup, or destructive backfill was introduced
- this session blocks unsafe hard deletes; it does not yet add the CP archive action or archived-state persistence

Verification:

- syntax checks passed for the changed service, subgroup controller, and focused unit test
- focused lifecycle and scoped group selector coverage passed with `10 tests, 41 assertions`
- focused Pint checks passed for the changed service, controller, and test

Deferred:

- additive archived-state persistence for groups and subgroups
- CP archive action for previously used audience structures
- archive-aware exclusion from new campaign targeting and form/preference assignment
- real-database CP delete and future archive verification with migrated MySQL data, a provisioned operator, direct product scope, historical campaign audiences, and subscriber memberships

## Session 28 Audience Archive State And CP Archive Action Notes

This session applies additive archived-state persistence and scoped CP archive actions for audience groups and subgroups.

Implemented boundary:

- `subscriber_groups` now has nullable `archived_at` and `archived_by` fields
- `subscriber_sub_groups` now has nullable `archived_at` and `archived_by` fields
- group and subgroup models expose `isArchived()` without introducing soft deletes
- scoped group archive requires the existing product-scope resolver and campaign history on the group or one of its child subgroups
- scoped subgroup archive requires the existing subgroup-parent resolver and campaign history on that subgroup
- archived records remain visible to the scoped CP group management surface for audit and history
- campaign targeting validation excludes archived groups and archived subgroups from new audience assignment
- CP campaign create/edit audience trees exclude archived groups and subgroups from newly selectable targeting options
- public subscription form group resolution excludes archived groups
- public preference and unsubscribe collection lookups exclude archived groups, and preference option listing excludes archived subgroups
- auto-managed application target subgroups fail closed when the configured subgroup slug exists but is archived

Rule preserved:

- no historical campaign audience rows were deleted or remapped
- no subscriber identity record is deleted or remapped during archive
- no broad audience read cutover, historical ownership backfill, or campaign-audience cleanup was introduced
- archived structures remain ordinary records for historical campaign and analytics resolution

Verification:

- syntax checks passed for the changed migration, models, services, controllers, provider, and focused tests
- focused scoped lifecycle and campaign-audience ownership coverage passed with `26 tests, 80 assertions`
- narrow Pint checks passed for the new migration, models, core scoped services, and focused tests
- broader touched-file Pint still reports cumulative whole-file formatting issues in legacy large files such as `CampaignController`, `SubscriptionFormService`, `GroupController`, `CollectionRegistry`, and `NewsletterServiceProvider`; this session did not perform a broad formatting rewrite

Deferred:

- archive-aware filtering for CP subscriber create/edit and import default subgroup assignment
- real-database CP archive verification with migrated MySQL data, provisioned operator, direct product scope, historical campaign audiences, archived groups, and archived subgroups
- any dedicated unarchive policy or restore workflow
- historical cleanup, ownership repair, and campaign-audience orphan repair

## Session 29 Archive-Aware Subscriber Management And Import Assignment Notes

This session completes the targeted archive-aware assignment boundary for CP subscriber management and CSV import.

Implemented boundary:

- CP subscriber index, create, and edit subgroup option lists now show only subgroups that are not archived and whose parent group is not archived
- CP subscriber create rejects archived subgroup IDs even when they are posted directly
- CP subscriber update rejects archived subgroup IDs before subscriber profile or membership sync writes run
- subscriber import default subgroup options now show only assignable active subgroups
- subscriber import rejects archived default subgroup IDs before CSV processing starts
- subscriber import CSV slug mapping resolves only active, assignable subgroup slugs
- archived CSV subgroup slugs are treated as unknown or archived and are not attached to subscriber records
- collection group options now guard the archive filter behind a schema check so pre-migration bootstrap/test contexts do not fail before the additive archive migration is present

Rule preserved:

- no subscriber identity records are deleted
- no subscriber is silently remapped from an archived subgroup to another subgroup
- existing subscriber membership rows remain intact when an update attempt includes an archived subgroup and fails validation
- historical campaign audience rows and archive records remain untouched
- no unarchive, restore, historical cleanup, or ownership backfill behaviour was introduced

Verification:

- syntax checks passed for the changed CP subscriber controller, import controller, collection registry, and focused feature test
- focused archive assignment coverage passed with `6 tests, 13 assertions`
- related Session 27 to 29 lifecycle and campaign-audience regression coverage passed with `32 tests, 93 assertions`
- Pint passed for the new focused feature test
- targeted Pint across the touched legacy controllers and registry still reports cumulative whole-file formatting debt; this session did not perform a broad formatting rewrite across those large existing files

Deferred:

- real-database CP/browser verification with migrated MySQL data, provisioned operators, active and archived groups, active and archived subgroups, CSV uploads, and direct-post archived IDs
- dedicated unarchive or restore policy
- historical cleanup, ownership repair, and campaign-audience orphan repair
- broad formatting cleanup for existing legacy newsletter CP controllers and services

## Session 30 Audience Lifecycle CP Runtime Verification And Hardening Notes

This session verified the audience lifecycle implementation from Sessions 26 to 29 against the configured testing database and registered CP route surface.

Verified boundary:

- the configured testing database reports all migrations as run, including the organisation/product scope migrations, Statamic identity link migration, ownership-column migration, and audience archive-column migration
- `subscriber_groups.archived_at` exists
- `subscriber_sub_groups.archived_at` exists
- `subscriber_groups.product_id` exists
- `subscriber_groups.organisation_id` exists
- `users.statamic_user_id` exists
- CP subscriber routes are registered for index, create, store, show, edit, update, delete, import, export, GDPR export, and GDPR erase
- CP group routes are registered for group create/update/delete/archive and subgroup create/update/delete/archive
- the focused Session 26 to 29 lifecycle regression suite still passes

Rule preserved:

- no new lifecycle product rule was introduced during verification
- no schema cleanup, ownership repair, historical audience cleanup, unarchive flow, or subscriber remapping was introduced
- no broad formatting rewrite was run against existing legacy controllers or services

Verification:

- `php artisan --env=testing migrate:status` confirmed all relevant migrations have run
- `php artisan --env=testing tinker --execute="..."` confirmed the archive, ownership, and Statamic identity-link columns exist
- `php artisan route:list --path=newsletter/subscribers` confirmed subscriber CP route registration
- `php artisan route:list --path=newsletter/groups` confirmed group and subgroup lifecycle route registration
- focused lifecycle and archive assignment regression coverage passed with `32 tests, 93 assertions`

Deferred:

- browser-authenticated CP walkthrough with a provisioned Statamic operator and representative product scope was not completed in this execution environment
- real CSV upload verification through the CP UI remains a browser/runtime verification follow-up
- dedicated unarchive or restore policy remains unset
- historical cleanup, ownership repair, and campaign-audience orphan repair remain out of scope

## Session 31 Subscriber Signup Pending-To-Active Lifecycle Notes

This session aligns public newsletter signup and resubscribe behaviour with the accepted lifecycle rule that subscribers start as `pending` and become `active` only after signup email delivery is confirmed.

Implemented boundary:

- `pending` is now an allowed MySQL subscriber status through an additive enum expansion migration
- new public newsletter signups are saved as `pending` with `confirmed_at` unset
- public resubscribe flows move non-active subscribers to `pending`, not directly to `active`
- existing active subscribers remain active when they only update profile details or preferences
- subscription confirmation emails now include subscriber lifecycle headers
- Elastic Email transport passes subscriber lifecycle fields to provider recipient custom fields so webhooks can correlate delivery events back to the subscriber
- Elastic Email delivery, open, or click events for a correlated subscription confirmation email promote a `pending` subscriber to `active`
- bounce events for a correlated pending subscription confirmation email suppress the subscriber as `bounced` and do not set `confirmed_at`
- public preference and unsubscribe status sync paths no longer promote pending subscribers to active merely because active membership rows exist
- blank platform-domain fallback in product URL generation now falls back to Laravel's platform URL instead of generating malformed `https:///...` URLs

Rule preserved:

- queued mail does not count as delivered
- subscriber identity records are not deleted or recreated
- subgroup membership rows are preserved during pending signup, resubscribe, preference update, and webhook activation
- CP manual subscriber creation and CSV import status semantics were not changed
- application-form delivery tracking remains handled through the existing application submission tracking path
- no unarchive, historical cleanup, ownership backfill, or broad audience read cutover was introduced

Verification:

- syntax checks passed for the pending status migration, confirmation mail, Elastic Email transport, subscription form service, webhook job, subscriber factory, URL generator, and focused tests
- MySQL testing migration status confirms `2026_07_31_130000_add_pending_status_to_subscribers_table` has run
- direct MySQL schema inspection confirms `subscribers.status` is `enum('pending','active','unsubscribed','bounced','complained','erased')`
- focused public signup, webhook lifecycle, archive assignment, and audience lifecycle regression coverage passed with `61 tests, 190 assertions`
- a parallel PHPUnit run against the shared MySQL test database caused RefreshDatabase migration races; valid verification was rerun serially after `php artisan --env=testing migrate:fresh --force`
- Pint passed for the new pending status migration and the new archive assignment feature test
- targeted Pint across mixed legacy touched files still reports cumulative whole-file formatting debt; this session did not perform a broad formatting rewrite

Deferred:

- CP subscriber filters, exports, analytics widgets, and operator views still need explicit pending-state visibility review
- browser-authenticated CP walkthrough with a provisioned Statamic operator remains outstanding
- provider webhook verification against real Elastic Email payloads remains a runtime follow-up
- pending subscriber ageing, retry, expiry, and resend policy are not yet documented

## Session 32 Pending Subscriber CP Visibility And Reporting Hardening Notes

This session makes the new `pending` subscriber state visible and safe across the operator-facing subscriber and lightweight reporting surfaces touched by the lifecycle work.

Implemented boundary:

- CP subscriber status options now include `pending`
- CP subscriber index status filter can filter pending subscribers
- CP subscriber index badges style pending separately from active, unsubscribed, bounced, and complained
- CP subscriber create/edit forms use the same explicit status list; manual creation still defaults to `active`
- CP subscriber edit can preserve a pending subscriber without forcing an activation or suppression status
- CP subscriber detail view styles pending as its own non-active state
- subscriber CSV export can export pending subscribers through the existing status filter
- newsletter dashboard widget now reports pending, active, unsubscribed, bounced, and complained subscriber counts separately
- send eligibility remains active-only through `AudienceResolver` and `Subscriber::active()`

Rule preserved:

- Session 31 delivery-confirmed activation semantics were not changed
- queued mail still does not count as delivered
- pending subscribers are not counted as active in the widget
- pending subscribers with active membership rows are not campaign send-eligible
- no resend, expiry, or ageing policy was introduced
- no subscriber identity records were deleted or remapped
- no unarchive, historical cleanup, ownership backfill, or broad audience read cutover was introduced

Verification:

- syntax checks passed for the CP subscriber controller, newsletter widget, focused feature test, and audience resolver test
- focused CP pending filter, pending edit preservation, pending CSV export, widget pending count, and active-only send eligibility coverage passed with `14 tests, 29 assertions`
- focused public signup, webhook lifecycle, archive assignment, audience lifecycle, and pending visibility regression coverage passed with `69 tests, 206 assertions`
- Pint passed for the touched focused tests

Deferred:

- browser-authenticated CP walkthrough with a provisioned Statamic operator remains outstanding
- provider webhook verification against real Elastic Email payloads remains a runtime follow-up
- pending subscriber ageing, retry, expiry, and resend policy remain approval-sensitive and are not yet documented
- broad formatting cleanup for existing legacy newsletter CP controllers and services remains out of scope

## Session 34 Local Schema Readiness For Authenticated CP Verification Notes

This session did not change shared-platform code, but it did confirm one runtime prerequisite for trustworthy authenticated CP verification on `version/2`.

Verified boundary:

- the local authenticated Statamic CP session was available, but subscriber CP routes were initially reading against a stale local MySQL schema
- `/cp/newsletter/subscribers/create` failed with `500 SQLSTATE[42S22]` until the pending `2026-07-30` and `2026-07-31` migrations were applied locally
- `php artisan migrate --force` brought the local database forward to the current `version/2` schema, including the archive columns, ownership scaffolding, Statamic identity link, and pending subscriber status expansion
- once the schema matched the branch, authenticated CP verification of the subscriber index, filter, edit page, and export route could proceed successfully

Rule preserved:

- no ownership, scope, lifecycle, or authorization rule was changed in order to make CP verification pass
- no credentials were invented and no CP authorization was weakened
- the migration step only aligned the local database with the already-committed `version/2` schema expectations

Deferred:

- newsletter widget browser verification still needs a dashboard surface that actually mounts the widget for the authenticated operator

## Session 39 Real Elastic Email Payload Verification Notes

This session verified current webhook parsing and correlation logic against read-only provider payload-shape evidence from the main-era production app and applied narrow, evidence-backed webhook hardening without changing lifecycle semantics. Production application state is not treated as aligned with v2.

Verified boundary:

- read-only main-era provider payloads include a real lowercase payload family for `Sent`, `Opened`, `clicked`, `Unsubscribed`, and `Error` using keys such as `transaction`, `to`, `date`, `status`, `messageid`, and for clicks `target`, `ip`, and `useragent`
- read-only main-era provider payloads also include older/title-case payload families for `delivered` and `failed` using `EventType`, `TransactionID`, `To`, `_source`, `BounceError`, and `Date`
- the current webhook controller accepts JSON and form-encoded payloads and extracts event, transaction, and recipient fields from `eventtype` / `EventType` / `event_type` / `status`, `transaction` / `transactionid` / `TransactionID` / `transaction_id` / `msgid`, and `to` / `To` / `recipient` / `Recipient`
- the current lifecycle correlation path activates `pending` subscribers only when the webhook payload identifies `lifecycle_email = subscription_confirmation`, `subscription_status` is `subscribed` or `resubscribed`, and a matching `subscriber_id` resolves
- `ProcessWebhookJob` now treats `target` as a valid click URL field so real lowercase clicked payloads record `campaign_link_clicks` correctly
- focused serial webhook coverage now passes for real-shape lowercase `Opened`, `clicked`, `Unsubscribed`, and `Error` payloads, plus mixed `delivered` and `failed` event variants, alongside the existing confirmation delivery, bounce, unsubscribe, complaint, and non-confirmation lifecycle cases

Evidence limitation:

- main-era production counts for stored lifecycle custom-field echoes were all zero for `send_id`, `subscriber_id`, `lifecycle_email`, `subscription_status`, `subscription_confirmation`, and `subscription_updated`
- because main-era production does not include the v2 lifecycle sender, recipient custom fields such as `send_id`, `subscriber_id`, `lifecycle_email`, and `subscription_status` being available as top-level webhook fields remain unconfirmed for v2 subscription lifecycle mail

Rule preserved:

- no activation, resend, expiry, ageing, unarchive, cleanup, backfill, or broad read-cutover semantics were changed
- no Session 35 widget files were modified

Deferred:

- lifecycle webhook verification remains specifically blocked on v2-generated or exported provider evidence that shows whether Elastic Email echoes v2 subscription custom fields in webhook payloads
- any semantic change triggered by real provider payload evidence must return for coordinator approval before lifecycle behaviour changes

## Session 36 Pending Lifecycle Persistence And Test Bootstrap Notes

This session adds the minimum shared persistence needed for the approved pending resend and expiry baseline and makes one narrow test-bootstrap compatibility adjustment for local SQLite verification.

Implemented boundary:

- `subscribers` now persist:
  - `pending_confirmation_resend_count`
  - `pending_confirmation_last_resent_at`
  - `pending_confirmation_expires_at`
  - `pending_lifecycle_state`
- the new `PendingSubscriberLifecycleService` centralises:
  - pending expiry calculation
  - resend eligibility checks
  - resend audit updates
  - delivery-confirmed pending activation updates
  - operator-facing pending lifecycle snapshots for CP surfaces
- the original subscriber create migration now includes `pending` in the fresh-database status enum so local SQLite or other fresh test databases match the current v2 lifecycle contract
- the historical compatibility-view migration and campaign-status enum expansion migration now guard their MySQL-only assumptions so sandboxed local SQLite PHPUnit runs can reach current feature code

Rule preserved:

- active-only audience eligibility remains unchanged
- no broad authorization, ownership, analytics, or domain cutover was introduced
- the historical migration guards change only fresh local bootstrap behaviour for non-MySQL verification and do not alter the approved runtime lifecycle policy
- no Session 35 widget file semantics were changed and no Session 39 provider-payload rules were weakened

Verification:

- focused serial PHPUnit passed under local SQLite fallback with `66 tests, 183 assertions`
- the local fallback was required because the configured shared MySQL test database was unreachable from this sandbox

Deferred:

- coordinator should still rerun the same focused suite against the preferred shared MySQL test lane outside the sandbox if exact database-engine parity is required before merge

## Session 37 Audience Restore Notes

This session implements the approved restore policy for archived audience groups and subgroups inside the existing scoped lifecycle boundary.

Implemented boundary:

- authorised product-scoped operators can restore archived top-level groups and subgroups through the existing scoped lifecycle service and CP management surface
- restore clears only `archived_at` and `archived_by`; no new restore-specific audit columns or historical rewrite path were introduced
- group restore requires the same exact product ownership and operator scope resolution already used by archive and delete, and fails closed when the group is already active or outside scope
- subgroup restore requires the same exact parent-group and ownership resolution already used by archive and delete, and also fails closed when the subgroup is already active or its parent group is still archived
- restoring a top-level group does not restore any archived child subgroup
- restoring a subgroup does not restore its parent group
- restored groups and subgroups become selectable again only through the existing archive-aware query paths already used by:
  - campaign audience validation
  - CP subscriber assignment
  - subscriber import default subgroup assignment
  - public newsletter-form lookup
- the shared `NewsletterServiceProvider` route file was touched only to register the new CP restore endpoints required by this lifecycle action; no pending-lifecycle, widget, or webhook semantics changed there

Rule preserved:

- historical campaign audience rows remain intact and are not rewritten during restore
- subscriber identity and existing subscriber membership rows remain intact; no restore path deletes or remaps subscribers
- no broad audience read cutover, ownership backfill, lifecycle change, public preference rewrite, or campaign-audience cleanup was introduced

Verification:

- syntax checks passed for:
  - `app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php`
  - `app/Http/Controllers/CP/Newsletter/GroupController.php`
  - `app/Http/Controllers/CP/Newsletter/SubGroupController.php`
  - `app/Providers/NewsletterServiceProvider.php`
  - `tests/Unit/ScopedSubscriberGroupProductSelectorTest.php`
  - `tests/Unit/CampaignAudienceOwnershipServiceTest.php`
  - `tests/Feature/SubscriberArchiveAssignmentTest.php`
  - `tests/Feature/SubscriptionFormControllerTest.php`
- focused serial PHPUnit passed under local SQLite fallback:
  - `DB_CONNECTION=sqlite DB_DATABASE=$(mktemp -t mailserver-restore) ./vendor/bin/phpunit tests/Unit/ScopedSubscriberGroupProductSelectorTest.php tests/Unit/CampaignAudienceOwnershipServiceTest.php tests/Unit/AudienceResolverTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Feature/SubscriptionFormControllerTest.php`
  - result: `OK (57 tests, 221 assertions)`
- the focused coverage now explicitly proves:
  - group restore fails for unscoped operators, product mismatch, and already-active structures
  - subgroup restore fails for wrong parent, archived parent, missing scope, product mismatch, and already-active structures
  - restoring a group does not restore archived child subgroups
  - restore does not delete subscribers or rewrite existing subscriber membership rows
  - restored structures re-enter campaign targeting, CP subscriber assignment, subscriber import, and public newsletter-form lookup only through the existing archive-aware filters

Deferred:

- authenticated CP/browser verification of the new restore buttons and redirects still needs a provisioned operator and representative scoped archived audiences
- coordinator should still rerun the focused suite against the preferred shared MySQL test lane outside this sandbox if exact engine parity is required before merge

## Session 38 Historical Ownership Audit And Cleanup Planning Notes

This session adds a read-only dry-run audit for historical newsletter ownership and audience-integrity cleanup planning.

Implemented boundary:

- `HistoricalOwnershipAuditService` inventories current relational ownership blockers and audience-integrity exceptions without mutating historical rows
- `newsletter:audit-historical-ownership` emits the audit as human-readable console output or JSON and can write a report artifact for review
- the command fingerprints the relevant newsletter tables before and after execution so the dry-run can prove it left the database unchanged during the audit
- the audit reads current subscriber groups, inherited subgroups, campaigns, campaign audiences, subscribers, email templates, configured newsletter collections, and Statamic form mappings
- collection-to-product mapping is treated as safe only when exactly one active `products.primary_collection_handle` candidate exists under one active organisation

Rule preserved:

- no `UPDATE`, `DELETE`, or `INSERT` repair of historical ownership, campaign audiences, subscriber memberships, archive state, or product mappings was introduced
- subgroup ownership remains inherited from the parent group and is not given a speculative direct backfill path
- Statamic groups, form titles, campaign names, and collection labels remain corroborating evidence only; they are not promoted to canonical ownership sources
- missing campaign-audience targets and membershipless subscribers are reported as blockers rather than being normalised silently

Dry-run evidence on Tuesday, August 18, 2026:

- report artifact: [docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json](/Users/dataphytefoundation/Herd/mailserver/docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json)
- live local audit found:
  - `0` organisations
  - `0` products
  - `3` unowned subscriber groups
  - `9` subgroups inheriting from unowned parent groups
  - `16` unowned campaigns
  - `4` orphaned `campaign_audiences` rows referencing missing subgroup `id = 1`
  - `1` active subscriber (`john@example.com`) with no membership rows
- all three configured newsletter collections currently report `missing_product_mapping_source`
- the before/after audit fingerprints matched, so the dry-run itself did not change the audited database state

Blocked future work:

- no historical ownership backfill can proceed until approved `organisations` and `products` rows exist for the `insight_newsletters`, `foundation_newsletters`, and `policy_point_newsletters` collections
- orphaned campaign-audience rows and membershipless subscribers need a separate approved remediation rule even after ownership rows exist

Future mutation order once approved:

- backfill top-level `subscriber_groups` ownership first from exact `collection_handle -> products.primary_collection_handle`
- backfill `campaigns` ownership second from exact `collection -> products.primary_collection_handle`
- rerun the dry-run report before any orphaned campaign-audience or subscriber-membership remediation

## Session 40 Product-Owned Form Runtime Consumer Notes

This session adds the first relational product-owned form consumer on top of the existing shared ownership and domain foundations.

Implemented:

- new runtime consumers now write explicit `organisation_id` and `product_id` onto:
  - `product_forms`
  - `product_form_submissions`
- hosted public form URLs resolve through the shared domain resolver rather than hardcoded host logic
- newly created form-linked audience targets must already be active, product-owned, and unarchived
- the new admin list lives on a distinct `/cp/product-forms` path so it does not collide with existing Statamic form screens

Rule preserved:

- no historical audience or campaign row was backfilled or reinterpreted to support the new form baseline
- no newsletter subscriber activation, resend, expiry, archive, restore, or ownership-cleanup semantics changed
- the new form baseline treats stored submissions as operational truth only; it does not widen subscriber truth or audience read cutover
- domain verification authority remains whatever the shared domain resolver already honours; this session did not create a new verification workflow

Verification on Tuesday, August 18, 2026:

- focused serial feature coverage passed with `21 tests, 75 assertions`
- retained archive-assignment and pending-lifecycle suites still passed after the new relational form consumer was added

Deferred:

- deeper CP-native permission modelling for per-form submission actions
- relational `subscription` mode adoption
- historical ownership cleanup and backfill
