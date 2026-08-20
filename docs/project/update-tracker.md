# Project Update Tracker

## Purpose

This file tracks project-wide updates across sessions.

It should be updated whenever a session changes:

- product direction
- feature scope
- workflow rules
- implementation status
- documentation structure

## Update Format

Use one entry per session.

Each entry should record:

- session number or label
- date
- scope
- affected feature areas
- docs updated
- implementation status
- follow-up items

---

## Session 2 - 2026-07-30

### Scope

Defined the target role model, permission model direction, and shared workflow states for the `version/2` platform.

### Affected Feature Areas

- shared platform foundations
- campaign workflow governance
- submission workflow governance
- authorization boundaries

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/shared-platform-foundations/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md)
- [docs/features/shared-platform-foundations/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/workflow.md)
- [docs/features/shared-platform-foundations/backlog.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/backlog.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No code or schema changes were applied in this session.

### Dependencies Discovered

- Session 1 must fully define the organisation and product model before implementation can safely map Statamic groups to organisation and product scope.
- Integration Checkpoint A must confirm that the role and workflow vocabulary does not conflict with the eventual domain-resolution rules.

### Blockers

- Exact permission slug definitions are still deferred.
- Exact organization-to-group and product-to-group mapping is still deferred.

### Follow-Up

- coordinator should confirm whether the proposed baseline roles and workflow states are accepted as the shared vocabulary
- Session 1 should complete the organisation and product model in enough detail to unblock scoped authorization design
- Session 3 should align domain-sensitive actions such as send, publish, unsubscribe, and preference routes with these ownership boundaries

## Session 11 - 2026-07-30

### Scope

Implemented the additive shared persistence foundation scaffolding for the `version/2` platform model.

### Affected Feature Areas

- shared platform foundations
- organisation persistence
- product persistence
- relational authorization scope foundations
- additive database scaffolding

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Models/Organisation.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Organisation.php)
- [app/Models/Product.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Product.php)
- [app/Models/OrganisationUserScope.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/OrganisationUserScope.php)
- [app/Models/ProductUserScope.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/ProductUserScope.php)
- [app/Models/StatamicGroupScopeMap.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/StatamicGroupScopeMap.php)
- [app/Models/User.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/User.php)
- [database/migrations/2026_07_30_120000_create_organisations_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_120000_create_organisations_table.php)
- [database/migrations/2026_07_30_120100_create_products_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_120100_create_products_table.php)
- [database/migrations/2026_07_30_120200_create_organisation_user_scope_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_120200_create_organisation_user_scope_table.php)
- [database/migrations/2026_07_30_120300_create_product_user_scope_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_120300_create_product_user_scope_table.php)
- [database/migrations/2026_07_30_120400_create_statamic_group_scope_map_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_120400_create_statamic_group_scope_map_table.php)
- [tests/Unit/PlatformPersistenceModelsTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/PlatformPersistenceModelsTest.php)

### Implementation Status

Additive persistence scaffolding only.

No existing newsletter, subscriber, form, campaign, or domain-management runtime behaviour was cut over in this session.

### Follow-Up

- run the additive migrations once the target database is available
- define the next controlled implementation session for ownership-aware authorization or dependent feature-table ownership columns
- keep domain-verification authority and complained-subscriber recovery policy as separate future decisions

## Session 12 - 2026-07-30

### Scope

Implemented additive ownership-aware authorization scope scaffolding only, using the new relational organisation and product scope records as the canonical shared scope layer.

### Affected Feature Areas

- shared platform foundations
- authorization scaffolding
- organisation and product ownership resolution
- future policy and service-layer authorization helpers

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Contracts/Authorization/PermissionRegistryInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Authorization/PermissionRegistryInterface.php)
- [app/Contracts/Authorization/ScopeResolverInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Authorization/ScopeResolverInterface.php)
- [app/Providers/AppServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/AppServiceProvider.php)
- [app/Models/User.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/User.php)
- [app/Support/Platform/Authorization/PermissionSlugs.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Authorization/PermissionSlugs.php)
- [app/Support/Platform/Authorization/PlatformPermissionRegistry.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Authorization/PlatformPermissionRegistry.php)
- [app/Support/Platform/Authorization/ScopeResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Authorization/ScopeResolver.php)
- [app/Support/Platform/Authorization/ResolvesAuthorizationScope.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Authorization/ResolvesAuthorizationScope.php)
- [config/platform.php](/Users/dataphytefoundation/Herd/mailserver/config/platform.php)
- [tests/Unit/PlatformAuthorizationScaffoldingTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/PlatformAuthorizationScaffoldingTest.php)

### Decisions Made

- relational `organisation_user_scope` and `product_user_scope` records are now the implemented canonical scope lookup layer for future authorization work
- permission slugs are registered centrally and grouped by platform capability area for later role and policy mapping
- container-resolved scope services and helper traits are preferred over direct role checks in feature code
- Statamic roles remain capability inputs and are not yet the active enforcement layer for feature runtime flows

### Implementation Status

Additive authorization scaffolding only.

No existing newsletter, subscriber, form, domain-management, or analytics runtime authorization behaviour was cut over in this session.

### Tests Run

- `php -l app/Providers/AppServiceProvider.php`
- `php -l config/platform.php`
- `find app/Contracts/Authorization app/Support/Platform/Authorization tests/Unit/PlatformAuthorizationScaffoldingTest.php -name '*.php' -print0 | xargs -0 -n 1 php -l`
- `./vendor/bin/phpunit tests/Unit/PlatformAuthorizationScaffoldingTest.php`

Result:

- focused authorization scaffolding test passed: `OK (4 tests, 12 assertions)`

### Dependencies Discovered

- a later controlled session must map approved Statamic roles and groups onto the new permission slug registry and scope records without bypassing the relational scope layer
- downstream feature sessions must adopt the resolver and policy scaffolding incrementally instead of reintroducing direct feature-specific ownership checks

### Blockers

- exact runtime enforcement cutover order across newsletter, forms, subscriber management, and CP actions remains a coordinator decision
- domain-verification authority is still unresolved and must not be assumed by later authorization or domain-management sessions
- complained-subscriber recovery policy remains unresolved and must not be encoded into future authorization or workflow logic yet

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The codebase is ready for the next additive session that introduces non-breaking policy usage or feature-level ownership checks behind the new shared resolver layer, provided it does not attempt a broad runtime cutover.

### What The Coordinator Or Another Session Must Do Next

- define the first narrow feature surface that should consume the shared scope resolver and permission registry
- decide the cutover order for CP actions, service methods, and public workflow operations
- keep unresolved domain-verification and complained-subscriber policy questions explicitly out of implementation scope until they are separately approved

## Session 13 - 2026-07-30

### Scope

Implemented additive shared domain scaffolding only for the `version/2` platform model.

### Affected Feature Areas

- shared platform foundations
- product and organisation domain fallback scaffolding
- request context resolution scaffolding
- scaffold URL generation helpers
- controlled implementation tracking

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [config/platform.php](/Users/dataphytefoundation/Herd/mailserver/config/platform.php)
- [app/Support/Platform/Domain/DomainResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/DomainResolver.php)
- [app/Support/Platform/Domain/ProductUrlGenerator.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/ProductUrlGenerator.php)
- [app/Support/Platform/Domain/RequestContextResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/RequestContextResolver.php)
- [tests/Unit/PlatformDomainScaffoldingTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/PlatformDomainScaffoldingTest.php)

### Decisions Made

- domain resolution remains additive and helper-only in this phase; no public runtime surface was cut over
- fallback order is implemented as documented: `product -> organisation -> platform`
- product-facing surfaces can map to different product domain fields without changing current route ownership
- request host resolution can now identify product, organisation, and platform context from the additive shared models
- unresolved domain-verification authority remains deferred; this session only honours existing `domain_status` and `domain_verified_at` fields

### Implementation Status

Additive shared domain scaffolding only.

No existing newsletter, form, preferences, unsubscribe, browser-view, CP, webhook, or campaign runtime behaviour was rewritten in this session.

### Tests Run

- `php -l config/platform.php`
- `php -l app/Support/Platform/Domain/DomainResolver.php`
- `php -l app/Support/Platform/Domain/ProductUrlGenerator.php`
- `php -l app/Support/Platform/Domain/RequestContextResolver.php`
- `php -l tests/Unit/PlatformDomainScaffoldingTest.php`
- `./vendor/bin/phpunit tests/Unit/PlatformDomainScaffoldingTest.php`

### Dependencies Discovered

- a later controlled feature session must decide where these helpers first become the canonical URL/domain source for newsletter and form surfaces
- a later session must define the operator and CP workflow for domain verification, validation, and authority boundaries before any management UI is implemented
- final branded tracking-link behaviour and browser-view canonical rules remain dependent on newsletter-surface decisions from the approved roadmap

### Blockers

- no implementation blocker for this additive scaffolding session
- cutover order is still a coordinator decision and must be controlled per surface
- domain-verification authority is still unresolved and must not be assumed by downstream sessions

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The codebase is ready for a narrow next session that adopts these shared helpers for one approved surface at a time without broad public-route cutover.

### What The Coordinator Or Another Session Must Do Next

- choose the first controlled consumer of the shared domain helpers, likely newsletter public links or hosted form URLs, but not both at once
- define the smallest safe cutover boundary for canonical URL generation before any host-based routing switch
- keep domain-management UI, verification authority, and cross-surface redirect policy deferred until separately approved

## Session 8 - 2026-07-30

### Scope

Defined the guarded, non-breaking implementation order for the `version/2` build and recorded migration, testing, rollback, and documentation rules for future implementation sessions.

### Affected Feature Areas

- project governance
- implementation sequencing
- migration safety
- verification and rollback discipline
- cross-feature drift prevention

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No application code or schema changes were applied in this session.

## Session 9 - 2026-07-30

### Scope

Implemented the minimum shared platform scaffolding for the `version/2` revamp without introducing feature behaviour.

### Affected Feature Areas

- shared platform foundations
- analytics driver contract scaffolding
- domain service contract scaffolding
- config-driven platform bindings
- implementation tracking

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Providers/AppServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/AppServiceProvider.php)
- [config/platform.php](/Users/dataphytefoundation/Herd/mailserver/config/platform.php)
- [app/Contracts/Analytics/AnalyticsReaderInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Analytics/AnalyticsReaderInterface.php)
- [app/Contracts/Analytics/AnalyticsWriterInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Analytics/AnalyticsWriterInterface.php)
- [app/Contracts/Analytics/AnalyticsEventStoreInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Analytics/AnalyticsEventStoreInterface.php)
- [app/Contracts/Domain/DomainResolverInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Domain/DomainResolverInterface.php)
- [app/Contracts/Domain/ProductUrlGeneratorInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Domain/ProductUrlGeneratorInterface.php)
- [app/Contracts/Domain/RequestContextResolverInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Domain/RequestContextResolverInterface.php)
- [app/Support/Platform/Analytics/DatabaseAnalyticsReader.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Analytics/DatabaseAnalyticsReader.php)
- [app/Support/Platform/Analytics/DatabaseAnalyticsWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Analytics/DatabaseAnalyticsWriter.php)
- [app/Support/Platform/Analytics/NullAnalyticsEventStore.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Analytics/NullAnalyticsEventStore.php)
- [app/Support/Platform/Analytics/UnavailableAnalyticsReader.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Analytics/UnavailableAnalyticsReader.php)
- [app/Support/Platform/Analytics/UnavailableAnalyticsWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Analytics/UnavailableAnalyticsWriter.php)
- [app/Support/Platform/Domain/DomainResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/DomainResolver.php)
- [app/Support/Platform/Domain/ProductUrlGenerator.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/ProductUrlGenerator.php)
- [app/Support/Platform/Domain/RequestContextResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/RequestContextResolver.php)
- [tests/Unit/PlatformContractsTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/PlatformContractsTest.php)

### Implementation Status

Additive scaffolding only.

No schema changes were applied.
No feature behaviour changes were intentionally introduced.

### Follow-Up

- run targeted tests for the new bindings and contracts
- keep analytics reads on `database`
- do not switch to `clickhouse`
- do not implement domain-management behaviour until the unresolved authority and persistence decisions are accepted

Documentation only.

No code or schema changes were applied in this session.

## Session 14 - 2026-07-30

### Scope

Implemented the first controlled runtime consumer of the shared domain scaffolding, limited to hosted form URLs and closely related public form links.

### Affected Feature Areas

- shared platform foundations
- form and data collection platform
- hosted public form URL generation
- additive domain-aware form link wiring

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/features/form-data-collection-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/SubscriptionFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/SubscriptionFormService.php)
- [app/Http/Controllers/Public/ObserverApplicationPageController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/ObserverApplicationPageController.php)
- [tests/Feature/SubscriptionFormControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriptionFormControllerTest.php)
- [tests/Feature/ObserverApplicationPageControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ObserverApplicationPageControllerTest.php)

### Decisions Made

- form-to-product domain resolution is inferred only through the existing form collection handle and the additive `products.primary_collection_handle` lookup path
- hosted form submit URLs use the shared `ProductUrlGenerator` when a matching product context exists
- hosted form schema and auxiliary public links reuse named routes for path structure and the shared `DomainResolver` for host selection
- no host-based route middleware, canonical redirect policy, or broader newsletter public-surface cutover was introduced in this session

### Implementation Status

Additive hosted-form URL wiring only.

Existing behaviour is preserved when no product record, organisation fallback, or verified forms domain is available.

No newsletter browser-view, preferences, unsubscribe, tracking, webhook, or CP routing behaviour was changed in this session.

### Tests Run

- `php -l app/Services/Newsletter/SubscriptionFormService.php`
- `php -l app/Http/Controllers/Public/ObserverApplicationPageController.php`
- `php -l tests/Feature/SubscriptionFormControllerTest.php`
- `php -l tests/Feature/ObserverApplicationPageControllerTest.php`
- `./vendor/bin/phpunit tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ObserverApplicationPageControllerTest.php tests/Unit/PlatformDomainScaffoldingTest.php`
- `DB_CONNECTION=sqlite DB_DATABASE=':memory:' ./vendor/bin/phpunit tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ObserverApplicationPageControllerTest.php`

Result:

- syntax checks passed for all changed PHP files
- `tests/Unit/PlatformDomainScaffoldingTest.php` passed: `OK (4 tests, 18 assertions)`
- the focused feature suites could not be completed in this session environment because:
  - the default test configuration attempted to connect to the sandbox-blocked MySQL test database
  - the SQLite retry hit an existing migration incompatibility in `database/migrations/2026_04_13_180000_create_subscriber_sub_group_pivot_compatibility_view.php`, which queries `information_schema.tables`

### Dependencies Discovered

- later sessions can adopt the same form URL helpers for externally rendered schema consumers and any future reusable hosted form surfaces without redefining path or host logic
- newsletter mail links, preferences, unsubscribe, and browser-view surfaces still need their own narrow cutover sessions

### Blockers

- no new implementation blocker was discovered for this narrow cutover
- domain-verification authority remains unresolved and was intentionally not assumed beyond existing verified-status checks
- canonical redirect behaviour between platform and branded form hosts remains a future coordinator decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The codebase is ready for another narrow public-surface cutover session, provided it keeps the same additive boundary and does not jump to broad host-routing or mail-link rewrites.

### What The Coordinator Or Another Session Must Do Next

- choose the next isolated public surface for shared domain-helper adoption, likely preferences/unsubscribe links or newsletter browser-view URLs, but not both at once
- keep any redirect-policy or domain-management UI work separate from this hosted-form URL lane

## Session 15 - 2026-07-30

### Scope

Implemented additive ownership-column scaffolding on existing product-owned relational records only.

### Affected Feature Areas

- shared platform foundations
- audience ownership persistence
- campaign ownership persistence
- persisted template ownership persistence

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Models/Organisation.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Organisation.php)
- [app/Models/Product.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Product.php)
- [app/Models/SubscriberGroup.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/SubscriberGroup.php)
- [app/Models/Campaign.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Campaign.php)
- [app/Models/EmailTemplate.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/EmailTemplate.php)
- [database/migrations/2026_07_30_130000_add_ownership_columns_to_product_owned_records.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_130000_add_ownership_columns_to_product_owned_records.php)
- [tests/Unit/ProductOwnershipScaffoldingTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ProductOwnershipScaffoldingTest.php)

### Decisions Made

- nullable `organisation_id` and `product_id` ownership columns were added only to relational records explicitly supported by the approved baseline:
  - `subscriber_groups`
  - `campaigns`
  - `email_templates`
- `subscriber_sub_groups` were intentionally left without direct ownership columns because the approved persistence model keeps subgroup ownership inherited from the parent top-level audience group
- no existing runtime flow was cut over to require or populate the new ownership columns in this session
- no speculative backfill was introduced for historical rows

### Implementation Status

Additive schema and model scaffolding only.

No ownership-aware query scoping, policy enforcement, runtime write-path changes, or UI cutover was introduced in this session.

### Tests Run

- `php -l app/Models/Organisation.php`
- `php -l app/Models/Product.php`
- `php -l app/Models/SubscriberGroup.php`
- `php -l app/Models/Campaign.php`
- `php -l app/Models/EmailTemplate.php`
- `php -l database/migrations/2026_07_30_130000_add_ownership_columns_to_product_owned_records.php`
- `php -l tests/Unit/ProductOwnershipScaffoldingTest.php`
- `./vendor/bin/phpunit tests/Unit/ProductOwnershipScaffoldingTest.php tests/Unit/PlatformPersistenceModelsTest.php`

### Dependencies Discovered

- a later controlled session can now add ownership-aware query boundaries and write-path assignment on top of these columns without redefining the persistence contract
- any historical backfill must be handled in a separate audited session using explicit sources such as `products.primary_collection_handle` or other accepted ownership mappings

### Blockers

- no new blocker was introduced for this additive scaffolding lane
- exact historical ownership backfill rules remain unresolved and should stay separate from this schema session
- form-owned relational records still need an explicit approved ownership-column session if they later become relational persistence targets

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The codebase is now ready for a narrow ownership-aware consumer session, provided it uses the approved persistence baseline and keeps backfill, enforcement, and behavioural cutover isolated.

### What The Coordinator Or Another Session Must Do Next

- open a narrowly scoped session for ownership-aware read/query boundaries or write-path assignment, not both at once
- keep historical backfill logic separate unless the coordinator first approves the exact mapping source and rollback plan

## Session 16 - 2026-07-30

### Scope

Implemented reusable ownership-aware read/query boundaries for the existing product-owned relational records only.

### Affected Feature Areas

- shared platform foundations
- audience ownership reads
- campaign ownership reads
- persisted template ownership reads

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Support/Platform/Ownership/HasOwnershipReadScopes.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/HasOwnershipReadScopes.php)
- [app/Models/SubscriberGroup.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/SubscriberGroup.php)
- [app/Models/Campaign.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Campaign.php)
- [app/Models/EmailTemplate.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/EmailTemplate.php)
- [tests/Unit/OwnershipReadBoundaryTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/OwnershipReadBoundaryTest.php)

### Decisions Made

- the shared read boundary is expressed as reusable Eloquent scopes for explicit product, allowed-product set, organisation, and combined organisation/product filters
- an empty allowed-product set fails closed
- ownership-scoped reads exclude nullable legacy rows by design
- no user-derived visibility helper was added because organisation-wide cross-product visibility remains unresolved
- no existing runtime consumer was cut over because ownership columns are still nullable and are not yet assigned or backfilled consistently
- Statamic roles and groups remain outside the ownership query source; callers can later compose relational `ScopeResolverInterface` product IDs with the shared allowed-product scope

### Implementation Status

Additive ownership-aware query scaffolding only.

No write-path assignment, historical backfill, policy enforcement, domain routing, newsletter lifecycle, form, analytics, or user-facing behaviour changed.

### Tests Run

- `php -l app/Support/Platform/Ownership/HasOwnershipReadScopes.php`
- `php -l app/Models/SubscriberGroup.php`
- `php -l app/Models/Campaign.php`
- `php -l app/Models/EmailTemplate.php`
- `php -l tests/Unit/OwnershipReadBoundaryTest.php`
- `./vendor/bin/phpunit tests/Unit/OwnershipReadBoundaryTest.php`
- `./vendor/bin/phpunit tests/Unit/OwnershipReadBoundaryTest.php tests/Unit/ProductOwnershipScaffoldingTest.php tests/Unit/PlatformAuthorizationScaffoldingTest.php`

Result:

- focused ownership read-boundary coverage passed: `OK (3 tests, 19 assertions)`
- ownership read-boundary and directly related foundation coverage passed: `OK (11 tests, 53 assertions)`

### Dependencies Discovered

- a later consumer session may compose `ScopeResolverInterface::productIds()` with `ownedByProducts()` after its exact visibility rules are approved
- safe runtime adoption depends on ownership assignment or an approved backfill for the selected consumer's records

### Blockers

- organisation-wide cross-product visibility is still unresolved, so no `visibleToUser` or equivalent resolver-backed query helper should be introduced yet
- existing consumers cannot safely be cut over while valid historical records may still have nullable ownership columns

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for a narrow write-path ownership-assignment session or a coordinator decision session on organisation-wide visibility.

It is not yet ready for broad ownership-filtered consumer cutover.

### What The Coordinator Or Another Session Must Do Next

- choose one controlled write path for product-owned records and assign explicit organisation and product ownership there
- separately decide whether active organisation scope grants reads across all products in that organisation or whether explicit product scope is always required
- keep historical backfill isolated until its mapping source, audit evidence, and rollback plan are approved

## Session 17 - 2026-07-30

### Scope

Implemented product and organisation ownership assignment on exactly one controlled `SubscriberGroup` write path.

### Chosen Write Path And Why

- chosen path: the Insight provisioner's subscriber-group create/update step
- the provisioner uses the fixed `insight_newsletters` collection handle
- product ownership is resolved from the accepted relational `products.primary_collection_handle` mapping
- the provisioner is a manual operational command and does not alter public or CP workflow behaviour

### Affected Feature Areas

- shared platform foundations
- subscriber-group ownership writes
- Insight operational provisioning

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php)
- [app/Services/Newsletter/InsightProvisioner.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/InsightProvisioner.php)
- [tests/Unit/SubscriberGroupOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/SubscriberGroupOwnershipWriterTest.php)

### Decisions Made

- the Insight product must be resolved before any provisioning mutation so missing or ambiguous ownership context fails safely
- product resolution requires exactly one `Product` whose `primary_collection_handle` is `insight_newsletters`
- subscriber-group ownership is assigned through the existing `product()` and `organisation()` relationships
- an existing unowned Insight group may receive ownership when the controlled provisioner writes it; this is write-time assignment, not a bulk backfill
- existing conflicting ownership is never silently reassigned
- no other product-owned record type or subscriber-group write path was changed

### Implementation Status

One controlled subscriber-group write path now assigns ownership.

No historical backfill, read-path cutover, policy enforcement, lifecycle, domain, form-submission, analytics, or user-facing behaviour changed.

### Tests Run

- `php -l app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php`
- `php -l app/Services/Newsletter/InsightProvisioner.php`
- `php -l tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/pint --test app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php app/Services/Newsletter/InsightProvisioner.php tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/pint --test app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/phpunit tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/phpunit tests/Unit/SubscriberGroupOwnershipWriterTest.php tests/Unit/OwnershipReadBoundaryTest.php tests/Unit/ProductOwnershipScaffoldingTest.php tests/Unit/PlatformAuthorizationScaffoldingTest.php tests/Unit/PlatformPersistenceModelsTest.php`

Result:

- focused subscriber-group ownership writer coverage passed: `OK (5 tests, 10 assertions)`
- new PHP files passed the focused Pint formatting check
- the full-file Pint check still reports existing formatting debt in `InsightProvisioner`; it was not bulk-reformatted because that would exceed this session's narrow scope
- ownership write path and directly related foundation coverage passed: `OK (21 tests, 82 assertions)`
- no broader feature test was blocked; no existing Insight provisioner feature suite was available

### Dependencies Discovered

- operators must persist the Insight organisation and product before running `newsletter:provision-insight`
- the Insight product must use `insight_newsletters` as its `primary_collection_handle`
- future subscriber-group write paths may reuse the writer only through separately controlled sessions

### Blockers

- `products.primary_collection_handle` is indexed but not database-unique; the writer rejects duplicate mappings instead of selecting one
- organisation-wide cross-product visibility remains unresolved but is not required by this write path

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another single write-path assignment session after the coordinator selects an explicit ownership source.

It is not ready for broad ownership-filtered read cutover or historical backfill.

### What The Coordinator Or Another Session Must Do Next

- confirm the next isolated write path and its explicit product source before implementation
- decide separately whether `products.primary_collection_handle` should be protected by a database uniqueness constraint
- keep CP group creation unchanged until its product-selection and authorization workflow is approved
- keep historical backfill separate with an approved mapping source, audit evidence, and rollback plan

## Session 18 - 2026-07-30

### Scope

Implemented product and organisation ownership assignment on exactly one additional controlled write path, for `Campaign` records only.

### Chosen Write Path And Why

- chosen path: the operator-only `newsletter:seed-demo-campaign` campaign create/update step
- its `--collection` option is one explicit collection handle that can resolve a relational product through `products.primary_collection_handle`
- product resolution occurs before the command's transaction and before any existing `--fresh` deletion
- `EmailTemplate` was not selected because no operational email-template write path currently exists
- CP campaign creation was not selected because missing ownership context would alter an active user-facing workflow

### Affected Feature Areas

- shared platform foundations
- campaign ownership writes
- local demo campaign provisioning

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Support/Platform/Ownership/ProductOwnershipResolver.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/ProductOwnershipResolver.php)
- [app/Support/Platform/Ownership/CampaignOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/CampaignOwnershipWriter.php)
- [app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php)
- [app/Console/Commands/Newsletter/SeedDemoCampaign.php](/Users/dataphytefoundation/Herd/mailserver/app/Console/Commands/Newsletter/SeedDemoCampaign.php)
- [tests/Unit/CampaignOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignOwnershipWriterTest.php)
- [tests/Unit/SubscriberGroupOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/SubscriberGroupOwnershipWriterTest.php)

### Decisions Made

- collection-to-product and product-to-organisation resolution now live in one shared ownership resolver
- campaign ownership is assigned through existing Eloquent ownership relationships
- an existing unowned demo campaign may receive ownership when the controlled demo command writes it; this is not a bulk backfill
- missing or duplicate collection mappings fail before the command mutates data
- existing conflicting campaign ownership is never silently reassigned
- existing demo subscriber-group, audience, send, link, and analytics data writes remain unchanged

### Implementation Status

One controlled campaign write path now assigns ownership.

No historical backfill, CP campaign change, read-path cutover, policy enforcement, lifecycle, domain, analytics, email-template, or public behaviour changed.

### Tests Run

- `php -l app/Support/Platform/Ownership/ProductOwnershipResolver.php`
- `php -l app/Support/Platform/Ownership/CampaignOwnershipWriter.php`
- `php -l app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php`
- `php -l app/Console/Commands/Newsletter/SeedDemoCampaign.php`
- `php -l tests/Unit/CampaignOwnershipWriterTest.php`
- `php -l tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/pint --test app/Support/Platform/Ownership/ProductOwnershipResolver.php app/Support/Platform/Ownership/CampaignOwnershipWriter.php app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/phpunit tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/phpunit tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/SubscriberGroupOwnershipWriterTest.php tests/Unit/OwnershipReadBoundaryTest.php tests/Unit/ProductOwnershipScaffoldingTest.php tests/Unit/PlatformAuthorizationScaffoldingTest.php tests/Unit/PlatformPersistenceModelsTest.php`
- `./vendor/bin/phpunit tests/Unit/CampaignSenderTest.php`

Result:

- campaign and subscriber-group ownership writer coverage passed: `OK (10 tests, 20 assertions)`
- changed ownership helpers and tests passed the focused Pint formatting check
- ownership write paths and directly related foundation coverage passed: `OK (26 tests, 92 assertions)`
- `CampaignSenderTest` could not start because the sandbox was denied access to the configured MySQL test database at `127.0.0.1:3306`; all 7 tests failed during application bootstrap with zero assertions

### Dependencies Discovered

- the selected product and its organisation must exist before running `newsletter:seed-demo-campaign`
- the command's `--collection` value must map to exactly one product through `primary_collection_handle`
- the command's existing audience group may still have nullable ownership because that record type is outside this session

### Blockers

- `products.primary_collection_handle` remains non-unique at database level; shared resolution rejects duplicate mappings instead of guessing
- the demo command is not ready for ownership-filtered end-to-end reads until its audience-group ownership is addressed separately
- organisation-wide cross-product visibility remains unresolved but is not required by this write path

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another explicitly scoped ownership or workflow session whose product source is approved.

It is not ready for broad ownership-filtered campaign reads, historical backfill, or CP campaign ownership enforcement.

### What The Coordinator Or Another Session Must Do Next

- decide whether the next step is demo audience-group consistency, CP campaign product selection, or email-template workflow definition
- approve user-facing failure handling before assigning ownership in CP campaign creation
- decide separately whether `products.primary_collection_handle` needs a database uniqueness constraint
- keep historical backfill and broad read cutover isolated

## Session 19 - 2026-07-30 - Demo Audience Ownership Consistency

### Scope

Brought the demo campaign's top-level `SubscriberGroup` write into the same explicit product and organisation ownership context as the Session 18 campaign write.

### Chosen Path And Why

- chosen path: the audience-group create/update inside `newsletter:seed-demo-campaign`
- the command already resolves exactly one product from its explicit `--collection` option before any transaction mutation
- the same resolved `Product` now drives both group and campaign ownership
- the change is isolated to demo provisioning and does not alter CP or public audience behaviour

### Affected Feature Areas

- shared platform foundations
- demo subscriber-group ownership
- demo campaign ownership consistency

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Console/Commands/Newsletter/SeedDemoCampaign.php](/Users/dataphytefoundation/Herd/mailserver/app/Console/Commands/Newsletter/SeedDemoCampaign.php)
- [tests/Unit/DemoAudienceOwnershipConsistencyTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/DemoAudienceOwnershipConsistencyTest.php)

### Decisions Made

- demo group and campaign ownership must use the same `Product` resolved before the transaction
- demo group creation now reuses `SubscriberGroupOwnershipWriter`; no local ownership assignment was added to the command
- missing or duplicate product mapping fails before group, campaign, or existing `--fresh` mutation
- conflicting existing group ownership fails inside the transaction and prevents a partial campaign write
- subgroup ownership remains inherited from the parent top-level group

### Implementation Status

The controlled demo campaign path now assigns matching product and organisation ownership to its campaign and top-level audience group.

No historical backfill, non-demo group change, broad read cutover, policy, lifecycle, domain, or analytics behaviour changed.

### Tests Run

- `php -l app/Console/Commands/Newsletter/SeedDemoCampaign.php`
- `php -l tests/Unit/DemoAudienceOwnershipConsistencyTest.php`
- `./vendor/bin/pint --test tests/Unit/DemoAudienceOwnershipConsistencyTest.php`
- `./vendor/bin/phpunit tests/Unit/DemoAudienceOwnershipConsistencyTest.php tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/SubscriberGroupOwnershipWriterTest.php`
- `./vendor/bin/phpunit tests/Unit/DemoAudienceOwnershipConsistencyTest.php tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/SubscriberGroupOwnershipWriterTest.php tests/Unit/OwnershipReadBoundaryTest.php tests/Unit/ProductOwnershipScaffoldingTest.php tests/Unit/PlatformAuthorizationScaffoldingTest.php tests/Unit/PlatformPersistenceModelsTest.php`
- `./vendor/bin/phpunit tests/Unit/CampaignSenderTest.php`

Result:

- focused demo consistency and ownership writer coverage passed: `OK (14 tests, 30 assertions)`
- the new demo consistency test passed its focused Pint formatting check
- demo consistency and directly related foundation coverage passed: `OK (30 tests, 102 assertions)`
- `CampaignSenderTest` could not start because the sandbox was denied access to the configured MySQL test database at `127.0.0.1:3306`; all 7 tests failed during application bootstrap with zero assertions

### Dependencies Discovered

- the demo command's collection must map to exactly one persisted product with a persisted organisation
- demo subgroup ownership continues to depend on the parent group as defined by the accepted persistence model
- future non-demo audience writes require separately approved product-selection and failure-handling rules

### Blockers

- `products.primary_collection_handle` remains non-unique at database level; shared resolution rejects duplicate mappings instead of guessing
- non-demo and historical audience records may still have nullable ownership and remain unsafe for broad ownership-filtered read cutover
- organisation-wide cross-product visibility remains unresolved but is not required by the demo path

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another narrow ownership or workflow session with an explicit product source.

The demo campaign and top-level audience group are now consistent, but the codebase is not ready for broad audience or campaign read cutover.

### What The Coordinator Or Another Session Must Do Next

- choose whether to define CP campaign product selection, CP group product selection, or the operational email-template workflow next
- decide separately whether `products.primary_collection_handle` needs a database uniqueness constraint
- keep historical backfill and broad read cutover isolated

## Session 20 - 2026-07-30 - CP Campaign Product Selection

### Scope

Inspected the CP campaign create/store path and evaluated the narrow explicit product-selection implementation against the accepted relational scope model.

### Chosen CP Path And Why

- candidate path: `CampaignController::create` and `CampaignController::store`
- this is the existing explicit CP campaign creation surface and can technically accept a `product_id`
- implementation stopped before runtime changes because the authenticated Statamic operator cannot currently be resolved to canonical relational product scope without inventing an identity or visibility rule

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

None.

### Decisions Made

- an explicit selector must show only products the operator can select through approved relational scope
- the selector must not list every product because organisation-wide cross-product visibility remains unresolved
- Statamic roles/groups cannot replace `product_user_scope`
- email matching, automatic relational-user creation, or UUID-to-integer coercion are not accepted identity rules and were not introduced
- after the identity bridge exists, store validation must reject missing, invalid, unscoped, inactive, or collection-conflicting products before campaign or audience writes

### Implementation Status

Blocked safely before application code changes. The existing CP campaign create/store behaviour is unchanged.

### Tests Run

- `php -l app/Http/Controllers/CP/Newsletter/CampaignController.php`
- `php -l app/Contracts/Authorization/ScopeResolverInterface.php`
- `php -l app/Support/Platform/Authorization/ScopeResolver.php`
- `php -l app/Support/Platform/Ownership/CampaignOwnershipWriter.php`
- `./vendor/bin/phpunit tests/Unit/CampaignOwnershipWriterTest.php tests/Unit/PlatformAuthorizationScaffoldingTest.php tests/Unit/OwnershipReadBoundaryTest.php tests/Unit/DemoAudienceOwnershipConsistencyTest.php`

Result:

- all inspected PHP files passed syntax checks
- focused ownership, authorization, read-boundary, and demo-consistency coverage passed: `OK (16 tests, 51 assertions)`

### Dependencies Discovered

- the CP guard authenticates a Statamic file-backed user whose stable identifier is a UUID
- `ScopeResolverInterface` requires `App\Models\User`
- `product_user_scope.user_id` is a foreign key to the numeric relational `users.id`
- no accepted service or persistence field bridges those two identities

### Blockers

- the operator identity bridge between Statamic authentication and relational scope records is unresolved
- without that bridge, product options cannot be filtered by canonical active product scope
- exposing all active products would assume the unresolved cross-product visibility policy

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

No, not for CP product selection or another CP ownership write path that depends on operator scope.

The existing demo ownership paths remain stable and ready for unrelated narrow work, but Session 20 must not resume until operator identity resolution is approved and implemented.

### What The Coordinator Or Another Session Must Do Next

- open a narrow shared-foundation session to define and implement the Statamic-operator-to-relational-user identity bridge
- specify the stable mapping key, provisioning/synchronisation ownership, migration path for current CP users, and fail-closed behaviour for missing or duplicate mappings
- rerun Session 20 after that bridge is accepted, using direct active product scopes plus product-to-collection consistency validation

## Session 21 - 2026-07-30 - Statamic Relational User Identity Bridge

### Scope

Implemented the additive identity bridge needed to resolve an authenticated Statamic CP operator to the relational `App\Models\User` used by the accepted scope resolver.

### Identity-Bridge Approach And Why

- Statamic remains the authentication authority
- nullable unique `users.statamic_user_id` is the stable authoritative runtime link
- runtime resolution is read-only and returns no user when the link is missing or ambiguous
- provisioning is explicit through a command; ordinary CP requests never lazily create or synchronize relational users
- normalized email is a command-time bootstrap field only and is never a runtime authorization fallback

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Contracts/Authorization/StatamicUserIdentityBridgeInterface.php](/Users/dataphytefoundation/Herd/mailserver/app/Contracts/Authorization/StatamicUserIdentityBridgeInterface.php)
- [app/Support/Platform/Authorization/StatamicUserIdentityBridge.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Authorization/StatamicUserIdentityBridge.php)
- [app/Console/Commands/Platform/ProvisionStatamicUser.php](/Users/dataphytefoundation/Herd/mailserver/app/Console/Commands/Platform/ProvisionStatamicUser.php)
- [app/Models/User.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/User.php)
- [app/Providers/AppServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/AppServiceProvider.php)
- [config/platform.php](/Users/dataphytefoundation/Herd/mailserver/config/platform.php)
- [database/migrations/2026_07_30_140000_add_statamic_user_id_to_users_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_30_140000_add_statamic_user_id_to_users_table.php)
- [tests/Unit/StatamicUserIdentityBridgeTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/StatamicUserIdentityBridgeTest.php)

### Decisions Made

- the stable Statamic user identifier is authoritative after provisioning
- email is used only by the explicit command to bootstrap or synchronize the link
- identity provisioning creates no organisation or product scope records
- existing relational users are linked only when their email matches and no conflicting Statamic link exists
- missing, duplicate, malformed, or conflicting identities fail closed
- CP campaign product selection remains outside this session

### Tests Run And Results

- syntax checks passed for all changed and directly related authorization PHP files
- focused identity bridge coverage passed: `OK (6 tests, 14 assertions)`
- identity bridge plus directly affected authorization, persistence, ownership, read-boundary, and demo-consistency coverage passed: `OK (27 tests, 84 assertions)`
- focused Pint formatting check passed
- command registration and help output were verified with an isolated SQLite application bootstrap
- `PlatformContractsTest` could not start because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; all 5 tests failed during Statamic application bootstrap with zero assertions

### Dependencies Discovered

- the additive migration must run before the bridge or provisioning command is used
- each operator requiring scoped CP work must be provisioned explicitly
- organisation and product scope grants remain a separate approved operational responsibility

### Blockers

- no implementation blocker remains for the identity bridge
- production operator provisioning and scope assignment require the target database and approved operator list
- application-level contract verification remains environmentally blocked by sandboxed MySQL access
- the project-wide `docs/` directory remains ignored by `.gitignore`; these updated handoff records exist on disk but require a separate coordinator decision before they can be included in normal Git integration

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The identity foundation is ready for Session 20 to resume, provided the target operator is explicitly provisioned and has direct active product scope records. It is not approval for broad policy enforcement or organisation-wide product visibility.

### What The Coordinator Or Another Session Must Do Next

- run the additive migration in the controlled target environment
- provision the relevant Statamic operator identities with `platform:provision-statamic-user`
- assign direct product scopes separately
- resume Session 20 using the bridge followed by `ScopeResolverInterface`; fail closed when either identity or product scope is missing

## Session 22 - 2026-07-31 - Resume CP Campaign Product Selection

### Scope

Implemented explicit, relationally scoped product selection and ownership assignment on the existing CP campaign create/store path only.

### Chosen CP Path And Why

- `CampaignController::create` now presents only active products from the current operator's direct active product scopes
- `CampaignController::store` revalidates the selected product against the same identity and scope boundary before writing
- this path was chosen because Session 21 now provides a stable fail-closed operator identity and the existing create form needs only one additional product field

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/ScopedCampaignProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedCampaignProductSelector.php)
- [app/Support/Platform/Ownership/CampaignOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/CampaignOwnershipWriter.php)
- [app/Http/Controllers/CP/Newsletter/CampaignController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/CampaignController.php)
- [resources/views/newsletter/cp/campaigns/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/create.blade.php)
- [tests/Unit/ScopedCampaignProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedCampaignProductSelectorTest.php)
- [tests/Unit/CampaignOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignOwnershipWriterTest.php)

### Decisions Made

- product options come only from direct active `product_user_scope`; organisation scope is not expanded
- products must be active and have a primary collection before they are selectable
- submitted product and collection must match exactly
- create requests with no usable linked operator scope return `403`
- invalid, inactive, unscoped, or collection-conflicting selections fail validation before campaign creation
- successful campaign creation assigns product and organisation ownership through the shared writer
- edit/update and existing audience synchronization remain unchanged

### Tests Run And Results

- syntax checks passed for every changed PHP file
- focused selector and campaign writer coverage passed: `OK (10 tests, 21 assertions)`
- selector plus directly affected identity, authorization, persistence, ownership, read-boundary, and demo-consistency coverage passed: `OK (41 tests, 127 assertions)`
- focused Pint checks passed for the new selector, writer, and focused tests
- Blade template compilation passed and the compiled view cache was cleared afterward
- the legacy campaign controller still has pre-existing project formatting violations, so a whole-file Pint check was not applied as a broad unrelated reformat
- `NewsletterCampaignLifecycleTest` could not start because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; all 7 tests failed during Statamic application bootstrap with zero assertions

### Dependencies Discovered

- the Session 21 identity migration must be applied and each CP operator must be explicitly provisioned
- direct active product scope records must exist before the operator can open the campaign create page
- selected products must have active status, a persisted organisation, and a configured primary collection used by the newsletter registry

### Blockers

- no implementation blocker remains for scoped CP campaign creation
- the target database still requires migration, operator provisioning, product records, and product-scope grants before the flow is operational
- `docs/` remains ignored by `.gitignore`, so these handoff updates require the existing separate documentation-tracking decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The CP campaign create path now assigns product and organisation ownership through a fail-closed direct product-scope boundary. It is not yet approval for broad campaign reads, edit/update scope enforcement, or audience ownership cutover.

### What The Coordinator Or Another Session Must Do Next

- open a narrow CP campaign audience ownership-consistency session so selected subgroups and send-to-all groups cannot cross the campaign product boundary
- keep historical nullable audience ownership and broad read/policy enforcement in separate sessions
- verify the migrated and provisioned flow against a real target database and CP operator before production use

## Session 23 - 2026-07-31 - Campaign Audience Ownership Consistency

### Scope

Implemented product-consistent audience validation and assignment on the scoped CP campaign create/store path only.

### Chosen Validation Path And Why

- the Session 22 create/store path already resolves one explicitly selected in-scope product before writing
- audience validation now runs immediately after that resolution and before `CampaignOwnershipWriter` creates the campaign
- send-to-all resolves exactly one owned top-level group; selected subgroup ownership is inherited and validated through each subgroup's parent group
- this path changes only new CP campaign audience selection and leaves edit/update compatibility behaviour unchanged

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Exceptions/Newsletter/CampaignAudienceOwnershipException.php](/Users/dataphytefoundation/Herd/mailserver/app/Exceptions/Newsletter/CampaignAudienceOwnershipException.php)
- [app/Services/Newsletter/ValidatedCampaignAudience.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ValidatedCampaignAudience.php)
- [app/Services/Newsletter/CampaignAudienceOwnershipService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/CampaignAudienceOwnershipService.php)
- [app/Http/Controllers/CP/Newsletter/CampaignController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/CampaignController.php)
- [resources/views/newsletter/cp/campaigns/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/create.blade.php)
- [tests/Unit/CampaignAudienceOwnershipServiceTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignAudienceOwnershipServiceTest.php)

### Decisions Made

- campaign audience validation uses the selected product, its persisted organisation, and its primary collection as one exact boundary
- send-to-all fails unless exactly one matching owned top-level group exists
- each selected subgroup must exist and have a parent group inside that exact boundary
- product and organisation status must both be active; no unsupported group or subgroup status field was invented
- invalid audience ownership fails before campaign creation and returns validation against `product_id`, `send_to_all`, or `sub_groups` as applicable
- an empty subgroup list remains a valid no-audience draft and does not imply send-to-all
- the create-page audience tree is restricted to groups owned by products in the operator's active scope, then filtered by the currently selected product and collection

### Tests Run And Results

- syntax checks passed for every changed PHP file
- focused audience ownership, product selector, and campaign writer coverage passed: `OK (17 tests, 36 assertions)`
- audience ownership plus directly affected identity, authorization, persistence, ownership-writer, read-boundary, and demo-consistency coverage passed: `OK (48 tests, 142 assertions)`
- focused Pint checks passed for the new exception, validated audience value object, ownership service, and unit test
- Blade template compilation passed and the compiled view cache was cleared afterward
- `AudienceResolverTest` could not start its four cases because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; the remaining 48 tests in that combined run completed before the four bootstrap errors
- `NewsletterCampaignLifecycleTest` could not start because of the same sandbox MySQL denial; all 7 tests failed during Statamic application bootstrap with zero assertions

### Dependencies Discovered

- the Session 21 identity migration, explicit operator provisioning, and direct active product scopes remain prerequisites
- each campaign product needs one unambiguous owned top-level group for send-to-all
- subgroup ownership depends on its parent top-level group having matching product, organisation, and collection ownership
- real CP verification requires the additive ownership data to be populated for the selected product's groups

### Blockers

- no implementation blocker remains for create-path campaign audience consistency
- application-boot and lifecycle verification remains blocked by sandbox access to the configured MySQL test database
- CP edit/update parity is deliberately deferred and therefore must not be represented as ownership-safe yet
- `docs/` remains ignored by `.gitignore`, so these handoff updates require the existing separate documentation-tracking decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another narrow session that respects the create-only boundary and existing unresolved cross-product visibility rule.

The scoped CP create path now validates campaign and audience ownership together before writing. This is not approval for broad read/policy cutover or for assuming historical rows are owned.

### What The Coordinator Or Another Session Must Do Next

- choose either a narrow CP campaign edit/update ownership-parity session or a separate CP audience-group product-selection session
- do not combine those write paths with historical backfill or broad read-policy enforcement
- run the migrated CP create flow against a real MySQL test database with a provisioned operator, direct product scope, one owned group, and owned subgroups before production use

## Session 24 - 2026-07-31 - CP Campaign Edit Update Ownership Parity

### Scope

Extended direct active product-scope and product-bound audience enforcement to the existing CP campaign edit/update path only.

### Chosen Update Path And Why

- the existing edit/update route already receives one persisted campaign and submits the complete campaign and audience form state
- campaign product ownership remains fixed; the current operator must resolve that exact campaign through the Session 21 identity bridge and direct active product scope
- edit validates persisted audience ownership before rendering, while update revalidates the submitted collection and complete audience before any write
- this preserves existing draft/schedule/send form behaviour without introducing product reassignment or a broad CP redesign

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/ScopedCampaignProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedCampaignProductSelector.php)
- [app/Services/Newsletter/CampaignAudienceOwnershipService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/CampaignAudienceOwnershipService.php)
- [app/Support/Platform/Ownership/CampaignOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/CampaignOwnershipWriter.php)
- [app/Http/Controllers/CP/Newsletter/CampaignController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/CampaignController.php)
- [resources/views/newsletter/cp/campaigns/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/edit.blade.php)
- [tests/Unit/ScopedCampaignProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedCampaignProductSelectorTest.php)
- [tests/Unit/CampaignOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignOwnershipWriterTest.php)
- [tests/Unit/CampaignAudienceOwnershipServiceTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignAudienceOwnershipServiceTest.php)

### Decisions Made

- edit/update requires non-null campaign product and organisation ownership matching one active persisted product and its active organisation
- the authenticated Statamic operator must resolve to a relational user with direct active scope for that exact product
- the campaign's collection must remain the product's primary collection; product reassignment is not accepted by this path
- the edit page exposes only the resolved product's collection, entries, and owned audience groups
- persisted audience rows must already represent either no audience, one exact send-to-all group, or only in-product subgroups; invalid legacy rows fail edit access rather than being repaired implicitly
- submitted audience state is always validated before campaign mutation because the existing update form submits the complete audience selection
- campaign field updates and audience replacement are transactional
- the legacy `syncAudiences()` method remains unused but was not removed because cleanup is outside this additive session

### Tests Run And Results

- syntax checks passed for every changed PHP file
- focused campaign resolver, ownership writer, and audience parity coverage passed: `OK (26 tests, 57 assertions)`
- directly affected identity, authorization, persistence, ownership-writer, read-boundary, demo-consistency, selector, and audience coverage passed: `OK (57 tests, 163 assertions)`
- focused Pint checks passed for the shared services, writer, and focused unit tests
- Blade template compilation passed and the compiled view cache was cleared afterward
- the legacy campaign controller still has pre-existing whole-file formatting violations, so no broad controller reformat was performed
- `AudienceResolverTest` and `NewsletterCampaignLifecycleTest` could not start because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; all 11 cases failed during Statamic bootstrap with zero assertions

### Dependencies Discovered

- the Session 21 identity migration, operator provisioning, and direct active product scopes remain operational prerequisites
- editable campaigns must have explicit matching product and organisation ownership and use the product's registered primary collection
- the owning organisation and product must be active
- existing audiences must already satisfy the Session 23 product boundary; this session does not backfill or repair legacy rows

### Blockers

- no implementation blocker remains for the narrow CP campaign edit/update ownership boundary
- application-boot, audience resolver, lifecycle, and real CP verification remain blocked by sandbox access to the configured MySQL test database
- legacy campaigns or audiences without accepted ownership intentionally fail closed until a separately approved backfill or repair process exists
- `docs/` remains ignored by `.gitignore`, so these handoff updates require the existing separate documentation-tracking decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another narrow ownership session that does not assume broad campaign-action authorization or historical-row readiness.

Create/store and edit/update now share the same explicit product, organisation, operator-scope, collection, and audience ownership boundary. Other campaign actions are not yet included in this scope claim.

### What The Coordinator Or Another Session Must Do Next

- choose a separate CP audience-group product-selection and ownership-assignment session, or explicitly scope one remaining campaign action at a time
- keep historical ownership repair/backfill separate from operator-facing write-path changes
- run real-MySQL CP create and edit/update verification with a migrated database, provisioned operator, direct product scope, owned campaign, and representative send-to-all and subgroup audiences before production use

## Session 25 - 2026-07-31 - CP Audience Group Product Selection

### Scope

Implemented explicit direct-scope product selection and ownership assignment for CP top-level audience-group creation and ownership-safe edit/update, with inherited scope guards for nested subgroup writes.

### Chosen CP Group Path And Why

- the existing group create form already had one collection selector, so replacing it with one explicit product selector is a narrow UI change
- each accepted product owns one primary collection, allowing the server to derive `collection_handle` without ambiguous collection-only inference
- existing group edit/update keeps persisted product ownership immutable because reassignment could silently move subgroups, subscribers, and campaign targeting across products
- nested subgroup records continue inheriting ownership from their parent group and therefore use the same parent scope resolver without new columns

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/ScopedSubscriberGroupProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupProductSelector.php)
- [app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Ownership/SubscriberGroupOwnershipWriter.php)
- [app/Http/Controllers/CP/Newsletter/GroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/GroupController.php)
- [app/Http/Controllers/CP/Newsletter/SubGroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubGroupController.php)
- [resources/views/newsletter/cp/groups/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/create.blade.php)
- [resources/views/newsletter/cp/groups/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/edit.blade.php)
- [tests/Unit/ScopedSubscriberGroupProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedSubscriberGroupProductSelectorTest.php)
- [tests/Unit/SubscriberGroupOwnershipWriterTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/SubscriberGroupOwnershipWriterTest.php)

### Decisions Made

- product choices come only from the current Statamic operator's explicitly linked relational identity and direct active `product_user_scope`
- both product and organisation must be active, and the product's primary collection must exist in the newsletter registry
- creation assigns product and organisation through the shared writer and derives the group collection from the selected product
- edit/update requires existing matching product, organisation, and collection ownership; null, conflicting, inactive, unscoped, or collection-mismatched rows fail closed
- product reassignment is rejected on update rather than moving the group's inherited audience tree
- nested subgroup writes require the parent group to resolve in scope and update/delete route pairs must reference a subgroup that belongs to that exact parent
- subgroup ownership remains inherited through `subscriber_group_id`
- group index filtering and top-level deletion enforcement remain separate controlled boundaries

### Tests Run And Results

- syntax checks passed for every changed PHP file
- focused group product selector and ownership writer coverage passed: `OK (13 tests, 32 assertions)`
- directly affected identity, authorization, persistence, ownership writer, campaign selector, campaign audience, read-boundary, demo-consistency, and group-selector coverage passed: `OK (65 tests, 185 assertions)`
- focused Pint checks passed for the new selector, ownership writer, and focused unit tests
- whole-file Pint identified pre-existing formatting violations in `GroupController` and `SubGroupController`; no unrelated controller-wide reformat was performed
- Blade template compilation passed and the compiled view cache was cleared afterward
- `AudienceResolverTest` could not start because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; all 4 cases failed during Statamic bootstrap with zero assertions

### Dependencies Discovered

- the Session 21 identity migration and explicit operator provisioning remain prerequisites
- direct active product scope records, active organisations/products, and registered primary collections are required before group creation or editing
- existing groups must already have matching product, organisation, and collection ownership
- nested subgroup scope remains dependent on the persisted parent group relationship

### Blockers

- no implementation blocker remains for the narrow create/edit/update group boundary
- HTTP-level CP and audience-resolver verification remains blocked by sandbox access to the configured MySQL test database
- unowned or conflicting historical groups intentionally remain inaccessible until a separate repair/backfill process is approved
- the CP group index remains broad and top-level group deletion remains unscoped because both are outside this session's write boundary
- `docs/` remains ignored by `.gitignore`, so these handoff updates require the existing separate documentation-tracking decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another narrow session that explicitly chooses group read visibility, top-level deletion enforcement, or another isolated product-owned write path.

CP group creation and edit/update now share the same direct product-scope and relational ownership model as campaign creation and editing. This is not approval for broad audience read cutover or historical ownership assumptions.

### What The Coordinator Or Another Session Must Do Next

- decide whether the next session should scope the CP group index and top-level delete action together or separately
- keep historical ownership repair/backfill isolated from user-facing access changes
- run real-MySQL CP group create/edit/update and nested subgroup verification with a migrated database, provisioned operator, direct product scope, and owned audience tree before production use

## Session 26 - 2026-07-31 - Group Index Visibility And Delete Enforcement

### Scope

Implemented direct product-scoped visibility for the CP top-level audience-group index and fail-closed enforcement for the existing top-level delete action only.

### Chosen Index/Delete Path And Why

- the existing CP index already receives one Eloquent group collection, so replacing its broad query with the shared product-scope selector is a narrow controller cutover without view redesign
- index filtering validates the complete persisted ownership triplet against products already resolved through the accepted Statamic identity bridge and direct active relational scope
- top-level deletion uses a small shared service around the same group resolver so the controller cannot delete before scope and ownership validation succeeds
- existing subgroup cascade semantics are preserved rather than redesigned in this session

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/ScopedSubscriberGroupProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupProductSelector.php)
- [app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php)
- [app/Http/Controllers/CP/Newsletter/GroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/GroupController.php)
- [tests/Unit/ScopedSubscriberGroupProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedSubscriberGroupProductSelectorTest.php)

### Decisions Made

- an index group is visible only when its product is in the operator's direct active product scope, its organisation is active, and its `product_id`, `organisation_id`, and `collection_handle` exactly match that product
- missing relational identity or no active product scope produces an empty index and does not fall back to Statamic groups, organisation scope, or broad visibility
- delete access uses the exact same ownership resolution as edit/update and fails closed before model deletion for unowned, conflicting, inactive, unscoped, or cross-product state
- successful deletion retains the existing subgroup and subscriber-pivot database cascades
- the existing polymorphic campaign-audience dependency semantics are unchanged because cleanup or delete-denial policy was outside this session

### Tests Run And Results

- syntax checks passed for the changed PHP files
- focused group visibility and guarded delete coverage passed: `OK (7 tests, 30 assertions)`
- directly affected identity, authorization, persistence, ownership writer, campaign selector, campaign audience, read-boundary, demo-consistency, and group-selector coverage passed: `OK (67 tests, 202 assertions)`
- focused Pint checks passed for the scoped selector, deletion service, and focused test
- whole-file Pint still identifies cumulative pre-existing formatting violations in `GroupController`; no unrelated controller-wide formatting rewrite was performed
- `AudienceResolverTest` could not start because the sandbox denied the configured MySQL connection at `127.0.0.1:3306`; all 4 cases failed during Statamic bootstrap with zero assertions

### Dependencies Discovered

- the Session 21 identity migration and explicit operator provisioning remain prerequisites
- direct active product scope records and matching active organisation/product records are required for index visibility and deletion
- existing groups must already have exact product, organisation, and collection ownership; no fallback mapping is used
- top-level deletion continues to rely on existing database cascades for subgroups and subscriber-subgroup pivots

### Blockers

- no implementation blocker remains for the narrow index and guarded-delete boundary
- real application/HTTP verification still depends on the configured MySQL test database being reachable in the execution environment
- historical unowned/conflicting rows remain intentionally hidden and undeletable until a separate repair/backfill process is approved
- campaign audience rows use polymorphic identifiers without a target foreign key; deletion dependency policy and historical orphan cleanup require a separate controlled decision
- `docs/` remains ignored by `.gitignore`, so these handoff updates require the existing separate documentation-tracking decision

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes, for another narrow, coordinator-selected boundary that consumes the accepted identity, scope, and ownership services without assuming broad organisation visibility or historical ownership.

This is not approval for broad audience read cutover, historical backfill, or deletion cleanup policy.

### What The Coordinator Should Do Next

- choose the next isolated CP ownership boundary rather than broadening all audience reads at once
- separately decide whether top-level audience groups referenced by campaigns should be delete-blocked, soft-deleted, or cleaned through an explicit dependency workflow
- keep historical ownership repair/backfill isolated and additive
- run real-MySQL CP index and delete verification with a migrated database, provisioned operator, direct active product scope, and representative owned, unowned, conflicting, inactive, and cross-product groups

### Decisions Made

- implementation must proceed in phases rather than feature-by-feature in isolation
- shared contracts and config skeletons must land before persistence-sensitive feature behaviour
- additive persistence changes must precede behaviour switches
- destructive cleanup must be deferred to later isolated sessions
- each implementation session must record verification and rollback expectations
- unresolved persistence, permission, scope, and domain-management blockers remain out of bounds for assumption-based implementation

### Dependencies Discovered

- coordinator must explicitly accept the persistence model for any area before Phase 2 work touching that area begins
- analytics implementation depends on lifecycle, newsletter, and form behaviours being stable enough to report canonically
- permission enforcement work depends on exact ownership and scope data being settled

### Blockers

- exact persistence schema for lifecycle event history remains unresolved
- exact persistence model for product, campaign, template, organisation, and group scoping remains unresolved
- exact permission slug definitions remain unresolved
- exact group-to-scope implementation model remains unresolved
- exact domain-management and domain-verification authority rules remain unresolved
- exact operator recovery policy for complained subscribers remains unresolved
- exact single-preference versus multi-preference subgroup policy remains unresolved

### Whether Implementation Can Begin Safely

Yes, but only for:

- shared contracts
- config skeletons
- policy scaffolding
- additive, coordinator-approved foundations

No, not yet for:

- permission-sensitive enforcement
- persistence-sensitive feature cutovers
- domain-management behaviour changes
- lifecycle or workflow behaviour that assumes unresolved blockers are already settled

### Follow-Up

- coordinator should preserve this build order as the required implementation guardrail baseline
- coordinator should explicitly clear blocker subsets before implementation sessions touching those areas begin
- downstream code sessions must reference this session before making persistence, permission, or domain-sensitive changes

## Integration Checkpoint A - 2026-07-30

### Scope

Reconciled Sessions 1 to 3 for the `version/2` revamp to confirm the shared baseline before subscriber, newsletter, and form feature sessions continue.

### Accepted Shared Baseline

The following items are accepted as the current shared foundation:

- `Organisation` is the highest operational owner
- `Product` is the main operating unit and belongs to exactly one organisation
- each product owns one primary public collection by default in `version/2`
- each form belongs to exactly one product
- each top-level audience group belongs to exactly one product
- each subgroup belongs to exactly one top-level product audience group
- roles define capability, groups define scope, and authorization must combine both
- campaign and submission workflow states are shared platform vocabulary
- domain resolution follows product -> organisation -> platform fallback
- public URL generation and inbound request resolution must use shared domain services rather than local feature logic

## Session 4 - 2026-07-30

### Scope

Defined the stable subscriber lifecycle, audience membership rules, and send-eligibility baseline for the `version/2` platform.

### Affected Feature Areas

- subscriber lifecycle
- audience and preference architecture
- send eligibility
- suppression and reactivation rules

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/newsletter-platform/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md)
- [docs/features/newsletter-platform/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md)
- [docs/features/newsletter-platform/backlog.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/backlog.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No code or schema changes were applied in this session.

### Dependencies Discovered

- Session 5 must incorporate this lifecycle into campaign and send-eligibility workflow
- Session 6 must align subscription, application, and data collection modes with the pending-to-active activation rule
- Session 7 must define the canonical persistence and reporting contract for lifecycle event history

### Blockers

- exact persistence schema for lifecycle event history remains unresolved
- exact operator recovery policy for complained subscribers remains unresolved
- exact single-preference versus multi-preference subgroup policy remains unresolved

### Follow-Up

- coordinator should reconcile this lifecycle baseline with newsletter and form workflow outputs before downstream analytics and implementation planning continue

## Session 5 - 2026-07-30

### Scope

Defined the newsletter platform as a product-owned publishing and delivery workflow for `version/2`.

### Affected Feature Areas

- newsletter platform
- campaign workflow
- product-to-collection ownership
- audience targeting
- template ownership
- newsletter public surfaces

### Docs Updated

- [docs/features/newsletter-platform/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md)
- [docs/features/newsletter-platform/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md)
- [docs/features/newsletter-platform/backlog.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/backlog.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No code or schema changes were applied in this session.

### Dependencies Discovered

- Session 4 remains the source of truth for subscriber lifecycle and suppression behaviour
- Session 7 must define canonical reporting metrics and analytics contracts
- shared foundations still need final permission slug naming
- shared foundations and the coordinator still need to settle persistence for product, campaign, and template ownership records

### Blockers

- unresolved persistence details for product, campaign, and template ownership
- unresolved permission slug details
- unresolved domain-management and verification implementation details

### Follow-Up

- coordinator should reconcile this platform spec with the subscriber and form feature outputs before implementation-sensitive sessions continue

## Session 6 - 2026-07-30

### Scope

Defined the full form and data collection platform spec for `version/2`, including workflow families, template families, customisation boundaries, and external integration patterns.

### Affected Feature Areas

- form and data collection platform
- subscription mode
- application mode
- data collection mode
- public integration patterns
- review and export workflow

### Docs Updated

- [docs/features/form-data-collection-platform/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md)
- [docs/features/form-data-collection-platform/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/workflow.md)
- [docs/features/form-data-collection-platform/backlog.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/backlog.md)
- [docs/features/form-data-collection-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No code or schema changes were applied in this session.

### Dependencies Discovered

- Session 4 must finalise subscriber lifecycle behaviour for subscription-mode activation and suppression assumptions
- Session 5 must finalise newsletter interaction rules for product audience and subscription behaviour
- Session 2 still needs to settle permission-slug and review-authority details
- Session 7 will need to define analytics and export contract details
- shared foundations still need final answers on persistence-sensitive organisation, product, and group scoping

### Blockers

- exact persistence model remains unresolved
- exact permission slug definitions remain unresolved
- exact group-to-scope implementation remains unresolved
- exact domain-management authority remains unresolved

### Follow-Up

- coordinator should reconcile form workflow, subscriber lifecycle, and newsletter audience rules before analytics and build-order planning continue

## Integration Checkpoint B - 2026-07-30

### Scope

Reconciled Sessions 4 to 6 to confirm that subscriber lifecycle, newsletter workflow, and form/data collection workflow remain aligned before analytics and implementation-guardrail sessions continue.

### Accepted Shared Baseline

The following items are accepted as the current feature-interaction baseline:

- subscriber lifecycle is `pending`, `active`, `unsubscribed`, `bounced`, `complained`
- new subscription signups become `pending` first and only become `active` after signup email delivery is confirmed
- only `active` subscribers are send-eligible
- unsubscribe, bounce, and complaint states stop send eligibility immediately
- resubscribe returns a subscriber to `pending`, not directly to `active`
- audience ownership remains product-scoped:
  - each parent subscriber group belongs to exactly one product
  - each subgroup belongs to exactly one parent group
  - form preference mapping stays inside the owning product audience tree
- the newsletter platform is product-owned and must not redefine subscriber truth, permission truth, analytics backend assumptions, or domain logic locally
- the form and data collection platform supports exactly three canonical modes:
  - `subscription`
  - `application`
  - `data_collection`
- subscription mode must follow the shared pending-to-active lifecycle rule
- application and data collection modes may optionally link to subscribers, but must not assume subscriber creation by default
- newsletter and form public surfaces both depend on the shared domain-resolution architecture rather than feature-local domain logic
- GA4 remains limited to behavioural and acquisition analytics, not workflow or operational truth

### Conflicts Found

No fundamental conflicts were found between Sessions 4 to 6.

The outputs are aligned at the documentation baseline level.

### Blockers Found

The following items remain unresolved and still block implementation-sensitive work:

- exact persistence schema for lifecycle event history
- exact persistence model for product, campaign, template, organisation, and group scoping records
- exact permission slug definitions
- exact group-to-scope implementation model
- exact domain-management and domain-verification authority rules
- exact operator recovery policy for complained subscribers
- exact single-preference versus multi-preference subgroup policy

### Whether Sessions 7 To 8 May Proceed Safely

Yes, Sessions 7 and 8 may proceed safely for documentation, reporting-contract, and implementation-planning work.

No, they must not treat the unresolved blockers above as settled implementation facts.

### What The Coordinator Must Resolve Next

- preserve this checkpoint as the required baseline for Sessions 7 and 8
- prevent analytics and implementation-planning sessions from inventing persistence, permission, or domain-management details
- ensure Session 7 defines contracts and reporting shape without pretending unresolved data-model details are already implemented
- ensure Session 8 produces a guarded build order that keeps persistence-sensitive and permission-sensitive implementation behind explicit coordinator approval

## Session 7 - 2026-07-30

### Scope

Defined the internal analytics model and reporting contract for `version/2` using the accepted baseline from Integration Checkpoint B.

### Affected Feature Areas

- shared platform foundations
- analytics and reporting contract
- newsletter analytics boundaries
- subscriber and audience reporting
- form and submission reporting
- GA4 boundary enforcement

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/shared-platform-foundations/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md)
- [docs/features/shared-platform-foundations/analytics-reporting-contract.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/analytics-reporting-contract.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No code or schema changes were applied in this session.

### Dependencies Discovered

- Session 8 must use this contract when defining non-breaking implementation order for reporting tables, analytics interfaces, and downstream dashboard work
- shared foundations and coordinator must still settle exact persistence details before physical schema work starts
- permission and scope decisions must still be finalised before analytics exposure is implemented for multi-organisation access

### Blockers

- exact persistence schema for organisation, product, and scoped reporting records remains unresolved
- exact permission slug model remains unresolved
- exact group-to-scope implementation model remains unresolved
- exact domain-management authority remains unresolved for any analytics surfaces that may later expose domain-sensitive URLs

### Follow-Up

- coordinator should treat this analytics contract as documentation-ready but not fully implementation-ready
- Session 8 should use this contract as a guardrail, not as proof that persistence and permission blockers are solved
- downstream implementation may scaffold interfaces and reporting directions, but must not hard-settle unresolved scope or persistence assumptions

## Session 10 - 2026-07-30

### Scope

Resolved the shared platform persistence and scope model blockers at architecture level so downstream implementation sessions can work from a stable ownership and authorization baseline.

### Affected Feature Areas

- shared platform foundations
- organisation and product persistence model
- audience, campaign, and template ownership model
- multi-organisation and product scope enforcement
- permission slug baseline
- coordinator dependency tracking

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/shared-platform-foundations/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md)
- [docs/features/shared-platform-foundations/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/workflow.md)
- [docs/features/shared-platform-foundations/backlog.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/backlog.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/features/shared-platform-foundations/persistence-and-scope-model.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/persistence-and-scope-model.md)
- [docs/features/shared-platform-foundations/analytics-reporting-contract.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/analytics-reporting-contract.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No application code or schema changes were applied in this session.

### Decisions Made

- organisations and products are now defined as dedicated relational records for `version/2`
- authorization scope is now defined as relational through `organisation_user_scope` and `product_user_scope`
- Statamic groups remain operator-facing team containers and optional mapping helpers, not the canonical scope layer
- audience ownership is product-scoped
- campaign ownership is product-scoped with `product_id`, `organisation_id`, and `created_by`
- persisted operator-managed template ownership is product-scoped by default
- the baseline permission slug set is now defined and stable enough for implementation planning
- permission checks must always be combined with scope checks, and workflow-state checks where applicable

### Dependencies Discovered

- the first persistence implementation session must translate this architecture into migration-safe table designs without redefining ownership semantics
- newsletter and form implementation sessions must consume this scope model rather than introducing product-local authorization rules
- domain-management implementation still depends on a separate coordinator-approved authority model
- subscriber complaint handling still depends on a separate recovery-policy decision

### Blockers

- exact domain-management and domain-verification authority workflow remains unresolved
- exact complained-subscriber recovery policy remains unresolved
- exact physical migration order for dependent feature tables remains implementation work, not yet completed in this session

### Whether The Codebase Is Ready For The Next Controlled Implementation Session

Yes.

The codebase is now documentation-ready for the next controlled implementation session on persistence or authorization scaffolding, provided that session stays within the settled ownership and scope model and does not invent the still-open domain-authority or complaint-recovery rules.

### Follow-Up

- coordinator should now open the first persistence implementation session for organisations, products, and scope tables
- the next implementation session should treat [docs/features/shared-platform-foundations/persistence-and-scope-model.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/persistence-and-scope-model.md) as the required baseline
- downstream sessions should stop treating permission slugs, organisation/product persistence, and group-to-scope modelling as undefined
- domain-management and subscriber-recovery sessions must still escalate their unresolved policy questions before implementation

## Current Coordinator Watch - 2026-08-18

### Current Coordinator Status

- `merge_readiness_completed`

### Last Coordinator Check

- `2026-08-18`

### Current Approval Question

- `Should coordinator populate and approve the missing organisation/product ownership map before any historical cleanup or backfill mutation session is opened?`

### Current Blocker

- `No relational organisation or product rows exist for the newsletter collections, so historical ownership backfill has no reliable canonical target today.`

### Current Routed Session

- `none`

### Last Reviewed Handoff

- `Session 41 - Documentation Tracking And Merge Readiness`

### Next Coordinator Action

- Review, commit, and push the accumulated `version/2` implementation and documentation changes, then prepare the controlled v2-to-main merge.
- Keep Session 38 historical cleanup/backfill blocked until a canonical organisation/product ownership map is approved and populated.
- Keep a narrow follow-up webhook verification task on the backlog only if v2-generated or exported provider payloads later capture lifecycle custom-field echo evidence for `subscriber_id`, `lifecycle_email`, or `subscription_status`.

### Why This Is The Current Step

- Sessions 27 to 30 completed audience delete/archive enforcement, archive-aware assignment filtering, and runtime verification
- Sessions 31 to 34 completed pending-to-active signup lifecycle, CP pending visibility, route-level/runtime verification, and authenticated CP verification for index, filter, edit, and export surfaces
- Session 35 mounted the existing newsletter dashboard widget, added a clearer `Subscriber status:` label, and verified the dashboard widget route with a focused authenticated feature test
- real Elastic Email provider payload compatibility remains the next low-risk technical verification lane
- Session 36 completed the approved pending resend, retry, ageing, and expiry baseline with focused tests
- Session 37 completed the approved audience unarchive/restore baseline with focused scoped-lifecycle and assignable-query coverage
- Session 38 completed a read-only historical ownership and audience cleanup audit/dry-run and blocked only historical cleanup/backfill mutation
- Session 40 completed the first newly created product-owned form and submission implementation baseline
- Session 41 completed documentation tracking and merge-readiness review
- no routed implementation session is currently active
- ownership backfill, subscriber remapping, broad read cutover, and v2-generated subscription-confirmation webhook capture remain follow-up work outside the completed implementation lane

### Current Expected Outcome

- The coordinator should now reconcile the dirty shared checkout, commit the accepted v2 implementation/docs, push `version/2`, and keep the documented follow-ups separate from the merge.
- The dry-run confirmed database fingerprints were unchanged before and after execution, but it also confirmed that no safe historical ownership backfill can proceed until relational organisation and product rows exist.

### Current Pause Condition

- Pause for user approval before changing application behaviour beyond the completed v2 scope, deleting task history, mutating historical cleanup/backfill data, subscriber remapping, unrestricted form-builder scope, broad domain-management redesign, organisation-wide visibility, broad read cutover, pending lifecycle change, or external provider/runtime semantic change.

### Monitoring Note

As of `2026-08-18`, Sessions 27 to 35 are recorded as completed in the tracker and Session 39 has been selected as the next controlled technical verification lane.

Session 33 was started from the current working tree with setup client id `client-new-thread:763a8b90-6e70-4536-b38d-abec57fb1461`.

Concrete Codex task id: `019fb925-6ea3-77c1-81fb-0d4e5f38aaf8`.

Session 33 completed with no lifecycle-rule blocker. The coordinator accepted the handoff, reconciled the narrow webhook hardening patch into the main checkout, reran the focused verification suite, and routed Session 34 for the remaining authenticated CP visual pass.

Session 34 was started from the current working tree with setup client id `client-new-thread:bd6f0796-ecc3-4854-81d9-cda8e016d2bc`.

Concrete Codex task id: `019fb92f-0be9-7621-b14b-fcf1b234aee4`.

Session 34 completed authenticated CP verification for pending subscriber index visibility, pending filtering, pending edit persistence, and filtered CSV export.

Session 35 completed the remaining widget gap by mounting the existing newsletter widget on the CP dashboard, adding the clearer `Subscriber status:` label, and verifying the mounted widget with a focused authenticated dashboard test.

Session 39 completed a narrow provider-payload-shape hardening pass without changing activation semantics.

Read-only main-era production data was used only to learn Elastic Email generic payload shapes and mixed event variants, not as the v2 implementation baseline. The webhook suite was hardened for those provider shapes. Because main-era production does not include v2 lifecycle sending, it cannot confirm whether Elastic Email will echo v2 lifecycle custom fields such as `send_id`, `subscriber_id`, `lifecycle_email`, `subscription_status`, `subscription_confirmation`, or `subscription_updated`.

Session 36 is now routed with an approved implementation baseline for pending subscriber resend, retry, ageing, and expiry. It must preserve delivery-confirmed activation as the only pending-to-active path.

Session 36 completed that baseline with focused lifecycle, webhook, CP, and audience eligibility coverage. Session 37 is now routed to implement safe audience restore without deleting subscribers, remapping membership, repairing historical campaign rows, or changing pending lifecycle semantics.

Session 37 completed the approved restore baseline. Session 38 is now routed as a read-only audit and cleanup planning lane; it must not mutate historical data or silently infer ownership where the mapping source is ambiguous.

Session 38 completed its read-only audit and remains blocked for historical cleanup/backfill mutation until a canonical ownership map exists. Session 40 is now routed for new product-owned form implementation only; it must not mutate historical newsletter rows or depend on the blocked cleanup path.

Session 40 completed the first product-owned form baseline with hosted render, allowed-origin submit, stored operational submissions, admin listing, export, domain fallback tests, and archive-aware audience safety checks. Session 41 is now routed for documentation tracking and merge readiness only.

Session 41 completed the documentation tracking decision and merge-readiness checklist. The coordinated v2 implementation lane is complete pending review, commit, push, and controlled merge preparation.

## Session 39 - 2026-08-18 - Elastic Email Real Payload Contract Verification

### Session Status

- `completed`

### Scope

Verified current webhook parsing and correlation logic against read-only provider payload-shape evidence from the main-era production app, then applied only evidence-backed hardening for generic Elastic Email event variants. This did not treat production application state as aligned with v2.

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Implementation Status

Focused webhook hardening and test coverage only.

### Payload Evidence Reviewed

- read-only main-era provider payload families with lowercase keys:
  - `Sent`: `transaction,to,from,date,status,channel,account,category,subject,messageid`
  - `Opened`: `transaction,to,from,date,status,channel,account,category,ip,country,state,city,useragent,subject,messageid`
  - `clicked`: `transaction,to,from,date,status,channel,account,category,target,ip,country,useragent,subject,messageid`
  - `Unsubscribed`: `transaction,to,from,date,status,channel,account,category,subject,messageid`
  - `Error`: `transaction,to,from,date,status,channel,account,category,subject,messageid`
- read-only main-era provider older/title-case payload families:
  - `delivered`: `EventType,TransactionID,To,_source`
  - `failed`: `EventType,TransactionID,To,_source,BounceError,Date`
- read-only main-era counts of stored lifecycle custom-field echoes:
  - `send_id: 0`
  - `subscriber_id: 0`
  - `lifecycle_email: 0`
  - `subscription_status: 0`
  - `subscription_confirmation: 0`
  - `subscription_updated: 0`
- current webhook ingress parsing in [app/Http/Controllers/Public/WebhookController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/WebhookController.php)
- current webhook event normalisation and lifecycle correlation in [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php)

### Decisions Made

- activation semantics remain unchanged because no real provider payload evidence contradicts the accepted Session 31 to 35 contract
- generic campaign webhook parsing now has evidence-backed coverage for the lowercase payload family and mixed event casing observed from Elastic Email provider data
- lowercase clicked payloads use `target` for the destination URL, so click tracking must treat `target` as a first-class provider field
- lifecycle activation remains unconfirmed specifically on whether Elastic Email echoes `subscriber_id`, `lifecycle_email`, and `subscription_status` into webhook payloads for v2 subscription confirmation mail

### Code Updated

- [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php)
- [tests/Feature/WebhookControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/WebhookControllerTest.php)
- [tests/Feature/ProcessWebhookJobTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProcessWebhookJobTest.php)

### Verification Run

- serial focused webhook verification passed with `./vendor/bin/phpunit tests/Feature/WebhookControllerTest.php tests/Feature/ProcessWebhookJobTest.php`
- result: `OK (35 tests, 64 assertions)`

### Blockers Discovered

- the available main-era payload evidence contains no stored examples of v2 lifecycle custom-field echo values such as `subscriber_id`, `lifecycle_email`, or `subscription_status`
- because of that missing v2-generated provider evidence, the pending-to-active activation path remains unconfirmed specifically on lifecycle custom-field echo behaviour even though generic event parsing is now hardened

### Next Recommended Coordinator Action

- coordinator can treat generic campaign payload compatibility hardening as complete and route the next approved lane
- if v2-generated or exported provider evidence later captures webhook payloads for v2 subscription confirmation mail, route a narrow follow-up verification session to compare those exact fields with the current lifecycle correlation path
- if any captured lifecycle payload disproves the current activation contract, route the follow-up as `needs_approval` before changing lifecycle semantics

## Planned Next Controlled Lanes - 2026-08-18

### Planning Status

- `approved_for_planning`

### Purpose

Define the next implementation queue as narrow, coordinator-routed sessions so the v2 build does not restart broad, overlapping work.

### Planned Session Queue

1. `Session 35 - Newsletter Dashboard Widget Mount And Visual Verification`
2. `Session 36 - Pending Subscriber Resend Retry Ageing And Expiry Policy`
3. `Session 37 - Audience Unarchive And Restore Policy`
4. `Session 38 - Historical Ownership And Audience Cleanup Plan`
5. `Session 39 - Elastic Email Real Payload Contract Verification`
6. `Session 40 - Form And Data Collection Implementation Baseline`
7. `Session 41 - Documentation Tracking And Merge Readiness`

### Dependency Rules

- Session 35 may proceed first because it is verification and dashboard configuration scoped.
- Session 36 requires an explicit product decision before code changes because resend, retry, ageing, and expiry affect subscriber lifecycle and operator expectations.
- Session 37 requires an explicit product decision before code changes because unarchive/restore can re-enable audience structures.
- Session 38 must be planning-first and must not mutate historical data until the cleanup/backfill map, rollback path, and audit criteria are approved.
- Session 39 may inspect and test real provider payload compatibility, but must not change activation semantics unless the payload evidence proves the current contract is wrong and coordinator approval is obtained.
- Session 40 must build on accepted ownership, domain, embed allow-list, archive, and pending-lifecycle rules without redefining them.
- Session 41 must decide whether docs should become tracked project assets before the v2-to-main merge; it must not change application behaviour.

### Hard Stop Conditions

- Do not implement resend, retry, ageing, expiry, unarchive, historical cleanup, ownership backfill, broad read cutover, or real-provider semantic changes without explicit coordinator approval.
- Do not start multiple sessions that touch the same lifecycle state machine at the same time.
- Do not let form/data-collection work bypass product ownership, allowed-platform embed rules, or archive-aware audience assignment.
- Do not treat GA4, ClickHouse, or any external analytics service as operational truth for workflow state.

### Current Recommendation

Start with Session 35.

Reason:

- it closes the only remaining visual verification gap from Session 34
- it should be low-risk if limited to mounting or verifying the existing newsletter widget
- it should not require changing subscriber lifecycle semantics

### Follow-Up

- add Session 35 to the active prompt pack before routing it
- keep Sessions 36 to 41 as planned lanes until the coordinator explicitly routes each one
- update this tracker after every completed session handoff before opening the next lane

## Session 35 - 2026-08-18 - Newsletter Dashboard Widget Mount And Visual Verification

### Session Status

- `completed`

### Scope

Closed the remaining authenticated CP dashboard widget verification gap by mounting the existing newsletter widget through Statamic CP configuration and verifying the mounted widget through the authenticated dashboard route.

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)

### Code Updated

- [config/statamic/cp.php](/Users/dataphytefoundation/Herd/mailserver/config/statamic/cp.php)
- [tests/Feature/NewsletterDashboardWidgetTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/NewsletterDashboardWidgetTest.php)

### Decisions Made

- the safest fix is to mount the existing `newsletter` widget through `config('statamic.cp.widgets')`
- the dashboard route can be verified through an authenticated CP feature test without changing subscriber lifecycle semantics
- the existing local `admin@mailserver.test` Statamic user has no stored widget preference overriding the config fallback
- widget data semantics remain unchanged; this session only exposes the already-tested widget on the dashboard

### Verification Run

- authenticated dashboard feature verification added for `cp_route('dashboard')`
- the mounted widget is verified to render separate `pending`, `active`, `unsubscribed`, `bounced`, and `complained` counts

### Blockers Discovered

- no new implementation blocker was introduced by Session 35
- manual browser-only screenshot evidence is still absent in this delegated session, but authenticated dashboard route verification now covers the mounted widget surface directly

### Next Recommended Coordinator Action

- route Session 36 only if the project is ready to decide the pending subscriber resend, retry, ageing, and expiry policy
- if policy work should wait, the next low-risk technical lane is Session 39 for real Elastic Email payload contract verification

## Session 27 - 2026-07-31 - Audience Structure Lifecycle Enforcement

### Session Status

- `completed`

### Scope

Implemented the approved delete-safety policy for top-level audience groups and subgroups inside the existing scoped CP/service boundary.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php)
- [app/Http/Controllers/CP/Newsletter/SubGroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubGroupController.php)
- [tests/Unit/ScopedSubscriberGroupProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedSubscriberGroupProductSelectorTest.php)

### Decisions Made

- hard delete is denied when a group or subgroup has historical campaign audience usage
- hard delete is denied when a group or subgroup still has active subscriber membership rows
- subgroup deletion now uses the shared scoped deletion service instead of deleting the model directly
- subscriber identity records are preserved during audience-structure lifecycle changes
- campaign-audience usage detection recognises both persisted morph aliases and class names

### Dependencies Discovered

- archive-state persistence and the CP archive action remain the next lifecycle step
- archived groups and subgroups still need exclusion from new campaign targeting and form/preference assignment once the archived state exists
- real-database CP verification still needs a migrated MySQL database with a provisioned operator, direct product scope, historical campaign audiences, and subscriber memberships

### Blockers

- no new coordinator blocker was introduced by Session 27
- no user approval is needed to route the next scoped implementation session

### Verification

- syntax checks passed for the changed service, controller, and focused test
- focused unit coverage passed with `10 tests, 41 assertions`
- focused Pint checks passed for the changed service, controller, and test

### What The Coordinator Or Another Session Must Do Next

- route Session 28 for additive archive-state persistence and CP archive action
- keep historical cleanup, ownership backfill, and broad audience read cutover out of Session 28 unless separately approved

## Session 28 - 2026-07-31 - Audience Archive State And CP Archive Action

### Session Status

- `completed`

### Scope

Implemented additive archived-state persistence and scoped CP archive actions for audience groups and subgroups.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [database/migrations/2026_07_31_120000_add_archive_columns_to_audience_structures.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_31_120000_add_archive_columns_to_audience_structures.php)
- [app/Models/SubscriberGroup.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/SubscriberGroup.php)
- [app/Models/SubscriberSubGroup.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/SubscriberSubGroup.php)
- [app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php)
- [app/Services/Newsletter/CampaignAudienceOwnershipService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/CampaignAudienceOwnershipService.php)
- [app/Services/Newsletter/CollectionRegistry.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/CollectionRegistry.php)
- [app/Services/Newsletter/SubscriptionFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/SubscriptionFormService.php)
- [app/Http/Controllers/CP/Newsletter/GroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/GroupController.php)
- [app/Http/Controllers/CP/Newsletter/SubGroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubGroupController.php)
- [app/Http/Controllers/CP/Newsletter/CampaignController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/CampaignController.php)
- [app/Http/Controllers/Public/PreferencesController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/PreferencesController.php)
- [app/Http/Controllers/Public/UnsubscribeController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/UnsubscribeController.php)
- [app/Providers/NewsletterServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/NewsletterServiceProvider.php)
- [resources/views/newsletter/cp/groups/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/index.blade.php)
- [resources/views/newsletter/cp/groups/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/edit.blade.php)
- [tests/Unit/ScopedSubscriberGroupProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedSubscriberGroupProductSelectorTest.php)
- [tests/Unit/CampaignAudienceOwnershipServiceTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignAudienceOwnershipServiceTest.php)

### Decisions Made

- audience archive state is additive through nullable `archived_at` and `archived_by`
- archive is a lifecycle action, not soft delete
- archive actions require the same scoped ownership and subgroup-parent checks as delete
- archive is only allowed for audience structures with campaign history
- archived audience structures remain visible in scoped CP group management for audit
- archived audience structures are excluded from new campaign targeting and public form/preference assignment paths touched in this session
- historical campaign audience rows remain intact after archive

### Dependencies Discovered

- CP subscriber create/edit and import assignment still need archive-aware filtering
- real-database CP archive verification still needs migrated MySQL data with provisioned operators and representative audience history
- no unarchive or restore policy is documented yet

### Blockers

- no new coordinator blocker was introduced by Session 28
- no user approval is needed to route the next scoped implementation session

### Verification

- syntax checks passed for the changed migration, models, services, controllers, provider, and focused tests
- focused scoped lifecycle and campaign-audience ownership coverage passed with `26 tests, 80 assertions`
- narrow Pint checks passed for the new migration, models, core scoped services, and focused tests
- broader touched-file Pint still reports cumulative whole-file formatting debt in several legacy large files; no broad formatting rewrite was performed

### What The Coordinator Or Another Session Must Do Next

- route Session 29 for archive-aware CP subscriber management and import assignment filtering
- keep unarchive policy, historical cleanup, ownership backfill, and broad audience read cutover out of Session 29 unless separately approved

## Session 29 - 2026-07-31 - Archive-Aware Subscriber Management And Import Assignment

### Session Status

- `completed`

### Scope

Completed archive-aware filtering and validation for CP subscriber create/edit and subscriber import assignment surfaces.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)

### Code Updated

- [app/Http/Controllers/CP/Newsletter/SubscriberController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubscriberController.php)
- [app/Http/Controllers/CP/Newsletter/ImportController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/ImportController.php)
- [app/Services/Newsletter/CollectionRegistry.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/CollectionRegistry.php)
- [tests/Feature/SubscriberArchiveAssignmentTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriberArchiveAssignmentTest.php)

### Decisions Made

- CP subscriber assignment surfaces should use assignable subgroup queries that exclude archived subgroups and subgroups whose parent group is archived
- direct POST validation must reject archived subgroup IDs even when the UI no longer lists them
- import CSV slug resolution should use only active assignable subgroup slugs
- archived CSV subgroup slugs should not be attached to subscriber records
- pre-migration bootstrap paths should guard archive-column filters with schema checks where needed

### Dependencies Discovered

- real CP/browser verification still needs migrated MySQL data, provisioned operators, and representative active/archived audience structures
- existing large newsletter CP controllers and registry still contain cumulative Pint formatting debt outside the targeted changed lines
- no unarchive or restore policy is documented yet

### Blockers

- no new coordinator blocker was introduced by Session 29
- no user approval is needed to route the next verification and hardening session

### Verification

- syntax checks passed for the changed CP subscriber controller, import controller, collection registry, and focused feature test
- focused archive assignment coverage passed with `6 tests, 13 assertions`
- related lifecycle and campaign-audience regression coverage passed with `32 tests, 93 assertions`
- Pint passed for the new focused feature test
- targeted Pint across the touched legacy controllers and registry still reports cumulative whole-file formatting debt; no broad formatting rewrite was performed

### What The Coordinator Or Another Session Must Do Next

- route Session 30 for MySQL-backed CP/runtime verification and narrow hardening of the audience lifecycle flows
- keep unarchive policy, historical cleanup, ownership backfill, broad audience read cutover, and broad formatting rewrites out of Session 30 unless separately approved

## Session 30 - 2026-07-31 - Audience Lifecycle CP Runtime Verification And Hardening

### Session Status

- `completed`

### Scope

Verified the audience lifecycle implementation from Sessions 26 to 29 against the configured testing database, CP route registration, and focused lifecycle regression tests.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)

### Code Updated

- none

### Decisions Made

- command-level verification is sufficient to route the next lifecycle implementation step, but it is not a substitute for later browser-authenticated CP walkthroughs
- no new lifecycle product rule should be introduced during verification
- the next implementation risk is the accepted pending-to-active signup lifecycle, not more archive/delete behaviour

### Dependencies Discovered

- browser-authenticated CP walkthrough still needs a provisioned Statamic operator and representative product scope
- signup activation depends on finding or defining a reliable delivery-confirmed signal in the existing mail/webhook flow
- no unarchive or restore policy is documented yet

### Blockers

- no new coordinator blocker was introduced by Session 30
- no user approval is needed to route the next scoped lifecycle implementation session

### Verification

- `php artisan --env=testing migrate:status` confirmed all relevant migrations have run
- direct schema checks confirmed `subscriber_groups.archived_at`, `subscriber_sub_groups.archived_at`, `subscriber_groups.product_id`, `subscriber_groups.organisation_id`, and `users.statamic_user_id`
- `php artisan route:list --path=newsletter/subscribers` confirmed subscriber CP route registration
- `php artisan route:list --path=newsletter/groups` confirmed group and subgroup lifecycle route registration
- focused Session 26 to 29 lifecycle regression coverage passed with `32 tests, 93 assertions`

### What The Coordinator Or Another Session Must Do Next

- route Session 31 for the pending-to-active signup lifecycle baseline
- if Session 31 cannot find a reliable delivery-confirmed activation signal, record that as a blocker rather than inventing activation semantics
- keep unarchive policy, historical cleanup, ownership backfill, broad audience read cutover, and broad formatting rewrites out of Session 31 unless separately approved

## Session 31 - 2026-07-31 - Subscriber Signup Pending-To-Active Lifecycle

### Session Status

- `completed`

### Scope

Aligned public newsletter signup and resubscribe with the accepted pending-to-active subscriber lifecycle.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)

### Code Updated

- [database/migrations/2026_07_31_130000_add_pending_status_to_subscribers_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_07_31_130000_add_pending_status_to_subscribers_table.php)
- [app/Services/Newsletter/SubscriptionFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/SubscriptionFormService.php)
- [app/Mail/SubscriptionConfirmationMail.php](/Users/dataphytefoundation/Herd/mailserver/app/Mail/SubscriptionConfirmationMail.php)
- [app/Mail/Transport/ElasticEmailTransport.php](/Users/dataphytefoundation/Herd/mailserver/app/Mail/Transport/ElasticEmailTransport.php)
- [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php)
- [app/Http/Controllers/Public/PreferencesController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/PreferencesController.php)
- [app/Http/Controllers/Public/UnsubscribeController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/UnsubscribeController.php)
- [app/Support/Platform/Domain/ProductUrlGenerator.php](/Users/dataphytefoundation/Herd/mailserver/app/Support/Platform/Domain/ProductUrlGenerator.php)
- [database/factories/SubscriberFactory.php](/Users/dataphytefoundation/Herd/mailserver/database/factories/SubscriberFactory.php)
- [tests/Feature/SubscriptionFormControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriptionFormControllerTest.php)
- [tests/Feature/ProcessWebhookJobTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProcessWebhookJobTest.php)

### Decisions Made

- queued signup mail is not considered delivered
- provider delivery/open/click webhooks are the accepted activation trigger when correlated to a subscription confirmation lifecycle email
- public preference edits must not activate pending subscribers merely because membership rows exist
- CP manual subscriber creation and CSV import status semantics remain unchanged for now

### Dependencies Discovered

- CP subscriber filters, exports, widgets, and analytics summaries need explicit pending-state visibility review
- provider webhook verification against real Elastic Email payloads remains a runtime follow-up
- pending subscriber retry, expiry, resend, and ageing policy is not documented yet

### Blockers

- no new coordinator blocker was introduced by Session 31
- no user approval is needed to route the next scoped lifecycle hardening session

### Verification

- syntax checks passed for the changed migration, mail, transport, signup service, webhook job, public controllers, URL generator, factory, and focused tests
- MySQL migration status confirmed the pending status migration has run
- direct MySQL schema inspection confirmed `subscribers.status` includes `pending`
- focused public signup, webhook lifecycle, archive assignment, and audience lifecycle regression coverage passed with `61 tests, 190 assertions`
- a parallel PHPUnit attempt against the shared MySQL test database produced RefreshDatabase migration races; valid verification was rerun serially after `php artisan --env=testing migrate:fresh --force`
- Pint passed for the new pending status migration and the new archive assignment test
- targeted Pint across mixed legacy touched files still reports cumulative whole-file formatting debt; no broad formatting rewrite was performed

### What The Coordinator Or Another Session Must Do Next

- route Session 32 for CP/reporting visibility of the new pending subscriber state
- keep activation semantics, resend/expiry policy, unarchive policy, historical cleanup, ownership backfill, broad audience read cutover, and broad formatting rewrites out of Session 32 unless separately approved

## Session 32 - 2026-07-31 - Pending Subscriber CP Visibility And Reporting Hardening

### Session Status

- `completed`

### Scope

Made the new `pending` subscriber state visible and safe across targeted CP subscriber, export, widget, and send-eligibility surfaces.

### Docs Updated

- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)

### Code Updated

- [app/Http/Controllers/CP/Newsletter/SubscriberController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubscriberController.php)
- [app/Widgets/NewsletterWidget.php](/Users/dataphytefoundation/Herd/mailserver/app/Widgets/NewsletterWidget.php)
- [resources/views/newsletter/cp/subscribers/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/index.blade.php)
- [resources/views/newsletter/cp/subscribers/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/create.blade.php)
- [resources/views/newsletter/cp/subscribers/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/edit.blade.php)
- [resources/views/newsletter/cp/subscribers/show.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/show.blade.php)
- [resources/views/newsletter/cp/widgets/newsletter.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/widgets/newsletter.blade.php)
- [tests/Feature/SubscriberArchiveAssignmentTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriberArchiveAssignmentTest.php)
- [tests/Unit/AudienceResolverTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/AudienceResolverTest.php)

### Decisions Made

- pending is an explicit CP-visible subscriber status
- manual CP subscriber creation still defaults to active, but pending can be selected or preserved when needed
- pending subscribers are counted separately from active subscribers in the dashboard widget
- pending subscribers remain excluded from campaign send eligibility

### Dependencies Discovered

- browser-authenticated CP walkthrough with provisioned Statamic operator remains outstanding
- provider webhook verification against real Elastic Email payloads remains a runtime follow-up
- pending subscriber retry, expiry, resend, and ageing policy is approval-sensitive and not documented yet

### Blockers

- no new coordinator blocker was introduced by Session 32
- no user approval is needed to route the next verification session

### Verification

- syntax checks passed for the changed CP subscriber controller, newsletter widget, focused feature test, and audience resolver test
- focused pending visibility and send-eligibility coverage passed with `14 tests, 29 assertions`
- focused public signup, webhook lifecycle, archive assignment, audience lifecycle, and pending visibility regression coverage passed with `69 tests, 206 assertions`
- Pint passed for the touched focused tests

### What The Coordinator Or Another Session Must Do Next

- route Session 33 for browser/runtime verification of the subscriber lifecycle surfaces
- if Session 33 proves the browser-authenticated CP flow is unavailable in this environment, record the exact limitation and strongest substitute evidence
- keep resend/expiry policy, activation semantics, unarchive policy, historical cleanup, ownership backfill, broad audience read cutover, and broad formatting rewrites out of Session 33 unless separately approved

## Coordinator Policy Update - 2026-07-31

### Scope

Accepted the audience-structure lifecycle rule for group and subgroup archive/delete behaviour so downstream sessions stop inventing deletion semantics independently.

### Affected Feature Areas

- newsletter platform
- form and data collection platform
- audience lifecycle
- subscriber reassignment workflow
- coordinator dependency tracking

### Docs Updated

- [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
- [docs/features/newsletter-platform/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md)
- [docs/features/newsletter-platform/workflow.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md)
- [docs/features/form-data-collection-platform/README.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Implementation Status

Documentation only.

No application code or schema changes were applied in this update.

### Decisions Made

- a group or subgroup that has ever been used in campaign targeting is not hard-deletable
- a previously used group or subgroup must be archived instead
- an unused and empty group or subgroup may be hard-deleted
- an unused group or subgroup with subscribers requires reassignment or explicit membership removal before delete
- deleting a group or subgroup must not delete subscriber identity records as a side effect
- archived audience structures must be excluded from new targeting and new form-assignment flows while remaining available for historical reporting and audit

### Dependencies Discovered

- implementation sessions must introduce an explicit usage check against historical campaign audience references before delete
- implementation sessions must define the operator reassignment flow for subscribers attached to unused groups or subgroups slated for deletion
- form configuration sessions must fail closed when a configured audience structure becomes archived or deleted

### Blockers

- the exact runtime UI and service behaviour for subscriber reassignment before delete is still implementation work
- the exact persistence/status model for archived groups and subgroups is still implementation work

### Follow-Up

- downstream sessions should treat this delete/archive policy as settled documentation baseline
- no implementation session should auto-delete subscriber identity records during group or subgroup cleanup
- the next relevant implementation session should add archive eligibility checks, reassignment gating, and historical-usage delete guards rather than redesigning the policy

## Coordinator Routing Update - 2026-07-31

### Scope

Converted the newly approved audience-structure lifecycle policy into an explicit next-session routing instruction so the coordinator can continue the loop without waiting for another manual framing step.

### Docs Updated

- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the next controlled implementation lane is `Session 27. Audience Structure Lifecycle Enforcement`
- Session 27 must consume the accepted archive/delete policy rather than redesign it
- Session 27 must stay within the narrowest safe CP and service boundary
- Session 27 must not broaden audience reads, perform historical cleanup, or auto-delete subscriber identity records

### Dependencies Discovered

- Session 27 depends on the Session 21 identity bridge, direct active product scope, and the Session 26 scoped group boundary already being present
- Session 27 must update both tracker and feature implementation notes so later sessions inherit the exact lifecycle enforcement outcome
- MySQL-backed or CP-interaction verification may still need explicit follow-up if the current execution environment cannot prove those paths fully

### Blockers

- no new policy blocker remains for opening Session 27
- the exact runtime implementation details are still session work and must be verified from the current codebase rather than assumed from the docs alone

### Follow-Up

- start Session 27 using the prompt now recorded in [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- keep the coordinator loop active after Session 27 and only pause if that session returns a real cross-feature blocker or approval need

## Coordinator Governance Update - 2026-07-31

### Scope

Strengthened the control-plane rules so automatic coordinator follow-up is a standing project rule rather than a one-off note attached only to Session 27.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the coordinator must continue routing after every completed session handoff, not only after specific named sessions
- completed sessions do not by themselves justify waiting for another manual user prompt
- the coordinator should pause only for a real blocker, approval need, governance conflict, or unresolved cross-feature decision returned by the session

### Dependencies Discovered

- future session handoffs must keep clearly stating whether they ended with a blocker or are safe for the next routed session
- the coordinator prompt remains the source used to enforce this behaviour consistently in later tasks

### Blockers

- no new blocker was introduced by this governance update

### Follow-Up

- continue monitoring for the next completed session handoff
- if Session 27 completes without a real blocker, route the next safe session immediately instead of waiting for a manual nudge

## Coordinator Handoff Contract Update - 2026-07-31

### Scope

Strengthened the session handoff contract so the coordinator can classify completed work faster without inferring status from prose.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- every feature or implementation session must now return `session status: completed | blocked | needs_approval`
- the coordinator handoff must now return `coordinator status: routing_next | waiting_for_approval | blocked`
- the session roadmap now requires every session to end with one explicit handoff status value

### Dependencies Discovered

- later sessions must follow the updated handoff shape for the coordinator loop to remain easy to monitor
- the coordinator can now distinguish a normal completion from an approval gate without relying on summary wording alone

### Blockers

- no new blocker was introduced by this handoff-contract update

### Follow-Up

- use the new explicit status fields in all future session and coordinator handoffs
- continue monitoring for the next completed session handoff, starting with Session 27

## Coordinator Live Watch Update - 2026-07-31

### Scope

Strengthened the coordinator loop so each reviewed session handoff must refresh the live watch section, not just the historical tracker entries.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the coordinator prompt now explicitly requires updating the `Current Coordinator Watch` section after every reviewed session handoff
- the session roadmap now treats live-watch refresh as part of the automatic continuation rule
- the live watch is now the authoritative quick-read surface for current routed session, coordinator status, and pause condition

### Dependencies Discovered

- future coordinator passes must keep the live watch synchronized with the most recent accepted session handoff
- historical tracker entries remain useful for audit, but should no longer be the only place where next-step state is inferred

### Blockers

- no new blocker was introduced by this live-watch update

### Follow-Up

- continue monitoring for the next completed session handoff
- when Session 27 completes, update the live watch first, then route the next safe session unless the handoff returns `needs_approval` or `blocked`

## Coordinator Status Mapping Update - 2026-07-31

### Scope

Defined the exact mapping from returned session status values into the live coordinator watch so later coordinator passes do not have to infer status transitions manually.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- `completed` must map to live watch status `routing_next`
- `needs_approval` must map to live watch status `waiting_for_approval`
- `blocked` must map to live watch status `blocked`
- the coordinator should update the live watch status before writing the next coordinator handoff

### Dependencies Discovered

- future coordinator passes must keep the live watch note aligned with the exact blocker or approval question when the status is not `routing_next`
- future session handoffs must keep using the explicit `session status` field for the mapping to remain reliable

### Blockers

- no new blocker was introduced by this status-mapping update

### Follow-Up

- continue monitoring for the next completed session handoff
- when Session 27 reports back, apply the status mapping first, then either route forward or pause for approval based on the returned session status

## Coordinator Watch Field Update - 2026-07-31

### Scope

Added explicit approval-question and blocker fields to the live watch so non-routing states can be tracked without relying on surrounding prose.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the live watch now always carries `Current Approval Question` and `Current Blocker`
- when coordinator status is `routing_next`, both fields must be `none`
- when coordinator status is `waiting_for_approval`, the approval question must be explicit
- when coordinator status is `blocked`, the blocker must be explicit

### Dependencies Discovered

- future coordinator passes must keep these two fields synchronized with the current live status
- the watch can now show a clean state transition without requiring readers to infer whether an approval or blocker exists

### Blockers

- no new blocker was introduced by this watch-field update

### Follow-Up

- continue monitoring for the next completed session handoff
- if Session 27 returns `needs_approval` or `blocked`, update these explicit fields before reporting the next coordinator state

## Coordinator Watch Actionability Update - 2026-07-31

### Scope

Added explicit last-reviewed and next-action fields to the live watch so the coordinator's current monitoring state is immediately actionable.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the live watch now keeps `Last Reviewed Handoff`
- the live watch now keeps `Next Coordinator Action`
- the coordinator prompt now requires these fields to remain explicit so the monitoring surface stays actionable at a glance

### Dependencies Discovered

- future coordinator passes must keep both fields synchronized with the most recent accepted handoff and next routed step
- the live watch can now communicate both current state and immediate next move without reading the surrounding historical entries

### Blockers

- no new blocker was introduced by this watch-actionability update

### Follow-Up

- continue monitoring for the next completed session handoff
- when Session 27 reports back, update `Last Reviewed Handoff`, `Next Coordinator Action`, and the live status fields before reporting the next coordinator state

## Coordinator Watch Template Update - 2026-07-31

### Scope

Defined the canonical field order for the live coordinator watch so later updates stay uniform and easy to scan.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the live watch now has a canonical field order
- the order is: status, approval question, blocker, routed session, last reviewed handoff, next coordinator action, why this is the current step, current expected outcome, current pause condition, monitoring note
- future coordinator updates should preserve this order so the watch remains stable and quickly readable

### Dependencies Discovered

- future coordinator passes must keep using the same field order when refreshing the live watch
- the coordinator prompt and roadmap now both point to the same watch structure

### Blockers

- no new blocker was introduced by this watch-template update

### Follow-Up

- continue monitoring for the next completed session handoff
- when Session 27 reports back, update the live watch using the canonical field order before routing the next state

## Coordinator Watch Timestamp Update - 2026-07-31

### Scope

Added an explicit last-check field to the live watch so stale coordinator state is easier to detect during monitoring.

### Docs Updated

- [docs/project/session-roadmap.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md)
- [docs/project/codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md)
- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Decisions Made

- the live watch now includes `Last Coordinator Check`
- the canonical live watch field order now starts with status, then last coordinator check
- future coordinator passes should refresh that field whenever they review the current state

### Dependencies Discovered

- future coordinator passes must keep the check date current so stale watch entries are obvious
- the coordinator prompt and roadmap now align on the expanded watch template

### Blockers

- no new blocker was introduced by this watch-timestamp update

### Follow-Up

- continue monitoring for the next completed session handoff
- when Session 27 reports back, refresh `Last Coordinator Check` together with the other live watch fields before reporting the next coordinator state

## Session 33 - 2026-07-31 - Subscriber Lifecycle CP Browser And Runtime Verification

### Scope

Verified the pending subscriber lifecycle against public signup routes, provider-webhook-shaped lifecycle payloads, CP-facing subscriber surfaces, newsletter widget data, and active-only audience resolution.

### Affected Feature Areas

- newsletter public signup lifecycle
- webhook-driven subscriber activation
- CP subscriber index, edit, and export visibility
- newsletter dashboard widget counts
- campaign audience resolution

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)

### Code Updated

- [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php)
- [tests/Feature/ProcessWebhookJobTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProcessWebhookJobTest.php)

### Decisions Made

- public signup and resubscribe flows must preserve `pending` until a correlated subscription confirmation lifecycle email is delivered, opened, or clicked
- CP subscriber index filtering, CP edit persistence, CSV export, and newsletter widget counts must continue to expose `pending` separately from `active`
- campaign audience resolution remains `active`-only
- lifecycle webhook correlation is now hardened so only `subscription_status` values `subscribed` and `resubscribed` may change subscriber lifecycle state through the subscription-confirmation webhook path

### Verification Evidence

- `tests/Feature/SubscriptionFormControllerTest.php` passed 10 focused checks covering public schema routing, verified product form domains, pending signup creation, pending preference updates, and resubscribe returning to `pending`
- `tests/Feature/ProcessWebhookJobTest.php` passed 20 focused checks covering provider-shaped delivery, open, click, bounce, unsubscribe, and complaint payloads, including a new regression proving a `subscription_updated` lifecycle email delivery does not activate a pending subscriber
- `tests/Feature/SubscriberArchiveAssignmentTest.php` passed 10 focused checks covering CP subscriber pending filters, edit persistence, export filtering, and widget pending counts
- `tests/Unit/AudienceResolverTest.php` passed 4 checks proving pending and unsubscribed subscribers remain excluded from resolved campaign audiences
- executed serial command:
  `php artisan test tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ProcessWebhookJobTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Unit/AudienceResolverTest.php`
- coordinator reconciled the narrow code/test patch into the main checkout and reran the same focused command with `44 tests, 128 assertions`
- coordinator reran focused webhook regression verification with `20 tests, 35 assertions`
- Pint passed for [tests/Feature/ProcessWebhookJobTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProcessWebhookJobTest.php)
- Pint still reports legacy whole-file formatting debt in [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php); no broad production-job reformat was performed

### Dependencies Discovered

- browser-authenticated Statamic CP verification was not available in this delegated worktree session; the strongest executed substitute was focused controller, export, widget, and route-level Laravel test coverage against the same pending-state paths
- the delegated worktree initially lacked a local `vendor/` tree and SQLite test file; both were provisioned locally so verification ran against this worktree instead of the main checkout

### Blockers

- no product-rule blocker was discovered in the pending lifecycle itself
- authenticated CP/browser walkthrough evidence is still absent until a session can run with usable CP auth in browser context

### Implementation Status

Safe for implementation now on the verified pending lifecycle boundary, with the authenticated CP/browser walkthrough still outstanding as follow-up verification rather than a behaviour blocker.

### Follow-Up

- coordinator can treat pending lifecycle activation, pending CP visibility, widget separation, and active-only audience resolution as verified on `version/2`
- Session 34 has been routed for one narrow authenticated CP visual pass over subscriber index, status filter, edit screen, widget count, and export trigger
- if browser-authenticated CP access is unavailable, Session 34 must record the exact limitation and strongest substitute evidence without changing lifecycle policy

## Session 34 - 2026-07-31 - Authenticated CP Pending Subscriber Surface Verification

### Scope

Performed the narrow authenticated Statamic CP verification pass for pending subscriber surfaces on the local `version/2` app, refreshed the stale local schema first, then verified the subscriber index, pending filter, edit persistence, and export path against a real pending subscriber.

### Affected Feature Areas

- newsletter CP subscriber index
- newsletter CP subscriber status filtering
- newsletter CP subscriber edit persistence
- newsletter CP subscriber export path
- newsletter CP dashboard widget availability
- local `version/2` schema readiness for CP verification

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Code Updated

No application code changed in this session.

### Decisions Made

- authenticated CP access is available locally through the existing Statamic operator login at `/` posting to `statamic.login`
- the local `mailserver` database had to be migrated before CP verification was meaningful because the `version/2` branch expected archive and pending-status columns that were still missing
- the subscriber index at `statamic.cp.newsletter.subscribers.index` (`/cp/newsletter/subscribers`) displays a pending subscriber row and a distinct `Pending` badge in the actual CP browser surface
- the pending status filter on the same route preserves `status=pending` in the query string and reduces the grid to the pending subscriber in the actual CP browser surface
- the subscriber edit screen at `statamic.cp.newsletter.subscribers.edit` (`/cp/newsletter/subscribers/145/edit`) preserves `pending` after save and redirect through `statamic.cp.newsletter.subscribers.show`
- the export trigger exposed from the filtered index reaches `statamic.cp.newsletter.subscribers.export` (`/cp/newsletter/subscribers/export/csv?status=pending`) and returns a filtered CSV response
- the newsletter widget could not be visually verified in the current authenticated dashboard because the dashboard surface did not mount it; treat that as a dashboard-configuration verification limitation, not a product-rule blocker

### Verification Evidence

- local authenticated browser session landed on `https://mailserver.test/cp/dashboard` and exposed the newsletter CP navigation
- initial authenticated GET to `https://mailserver.test/cp/newsletter/subscribers/create` returned `500` with `SQLSTATE[42S22]` because `subscriber_groups.archived_at` and `subscriber_sub_groups.archived_at` were missing locally
- `php artisan migrate --force` applied all pending `2026-07-30` and `2026-07-31` migrations, including:
  - `2026_07_31_120000_add_archive_columns_to_audience_structures`
  - `2026_07_31_130000_add_pending_status_to_subscribers_table`
- seeded one minimal pending subscriber fixture locally:
  - `pending-session34@example.test`
  - status `pending`
  - subgroup `Regular`
- authenticated browser verification on `https://mailserver.test/cp/newsletter/subscribers` showed:
  - rendered heading `Subscribers`
  - status filter options `Pending`, `Active`, `Unsubscribed`, `Bounced`, `Complained`
  - pending row `pending-session34@example.test`
  - distinct rendered badge label `Pending`
- authenticated browser verification on `https://mailserver.test/cp/newsletter/subscribers?search=&status=pending&sub_group=` showed:
  - `Pending` selected in the status combobox
  - filtered export link `https://mailserver.test/cp/newsletter/subscribers/export/csv?status=pending`
  - only the pending subscriber row remained in the table
- authenticated browser verification on `https://mailserver.test/cp/newsletter/subscribers/145/edit` showed:
  - `Pending` selected in the status combobox before save
  - save redirected to `https://mailserver.test/cp/newsletter/subscribers/145`
  - details view rendered `Status` = `Pending`
  - reloading the edit screen preserved `Pending` as the selected status
- authenticated HTTP verification using the same local operator login returned:
  - `/cp/newsletter/subscribers` -> `200 text/html`
  - `/cp/newsletter/subscribers?status=pending` -> `200 text/html`
  - `/cp/newsletter/subscribers/145/edit` -> `200 text/html`
  - `/cp/newsletter/subscribers/export/csv?status=pending` -> `200 text/csv` with `Content-Disposition: attachment; filename=\"subscribers-2026-07-31.csv\"`
- the CSV response begins with the expected header row and includes `pending-session34@example.test,...,pending,...`
- authenticated dashboard verification at `https://mailserver.test/cp/dashboard` showed only the default Statamic starter cards; no newsletter widget surface was mounted for this operator
- `config/statamic/cp.php` currently declares an empty default widget list

### Dependencies Discovered

- authenticated CP verification depends on the local database being migrated to the current `version/2` schema before any browser result is trusted
- a future widget-specific browser pass needs either a dashboard with the newsletter widget mounted or an operator preference path that safely mounts it for verification

### Blockers

- no lifecycle-rule blocker was discovered for pending subscribers
- widget visual separation remains unverified in-browser because the current authenticated dashboard does not include the newsletter widget surface

### Implementation Status

Safe for implementation now on the pending subscriber CP index, filter, edit, and export boundary. The widget data path remains verified only by prior focused tests until a mounted dashboard widget can be inspected in-browser.

### Follow-Up

- coordinator can treat authenticated CP verification for pending index visibility, pending filtering, pending edit persistence, and filtered CSV export as completed on `version/2`
- a later narrow session should verify the newsletter widget visually once a dashboard surface actually mounts it for an authenticated operator
- no lifecycle semantics, resend policy, expiry policy, unarchive behaviour, or historical cleanup should be changed as a result of this verification pass

## Session 36 - 2026-08-18

### Scope

Implemented the approved pending subscriber resend, cooldown, ageing, and expiry baseline on `version/2` with focused lifecycle, webhook, CP, and audience-eligibility coverage.

### Affected Feature Areas

- newsletter subscriber lifecycle
- subscriber confirmation resend operations
- webhook-confirmed activation
- CP subscriber pending-state visibility
- active-only audience eligibility
- local PHPUnit bootstrap compatibility for serial SQLite fallback verification

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Code Updated

- [app/Services/Newsletter/PendingSubscriberLifecycleService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/PendingSubscriberLifecycleService.php)
- [app/Services/Newsletter/SubscriptionFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/SubscriptionFormService.php)
- [app/Http/Controllers/CP/Newsletter/SubscriberController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubscriberController.php)
- [app/Providers/NewsletterServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/NewsletterServiceProvider.php)
- [app/Models/Subscriber.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/Subscriber.php)
- [app/Jobs/Newsletter/ProcessWebhookJob.php](/Users/dataphytefoundation/Herd/mailserver/app/Jobs/Newsletter/ProcessWebhookJob.php)
- [resources/views/newsletter/cp/subscribers/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/index.blade.php)
- [resources/views/newsletter/cp/subscribers/show.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/show.blade.php)
- [resources/views/newsletter/cp/subscribers/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/edit.blade.php)
- [database/migrations/2026_08_18_120000_add_pending_lifecycle_columns_to_subscribers_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_08_18_120000_add_pending_lifecycle_columns_to_subscribers_table.php)
- [database/migrations/2026_04_10_235000_create_subscribers_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_04_10_235000_create_subscribers_table.php)
- [database/migrations/2026_04_13_180000_create_subscriber_sub_group_pivot_compatibility_view.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_04_13_180000_create_subscriber_sub_group_pivot_compatibility_view.php)
- [database/migrations/2026_05_08_180000_expand_campaign_status_enum.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_05_08_180000_expand_campaign_status_enum.php)
- [tests/Feature/SubscriptionFormControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriptionFormControllerTest.php)
- [tests/Feature/ProcessWebhookJobTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProcessWebhookJobTest.php)
- [tests/Feature/PendingSubscriberLifecycleTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/PendingSubscriberLifecycleTest.php)
- [tests/Unit/AudienceResolverTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/AudienceResolverTest.php)

### Decisions Made

- pending subscribers remain the only lifecycle state eligible for confirmation resend, and only delivery, open, or click webhook events for a correlated `subscription_confirmation` lifecycle email can promote a non-expired pending subscriber to `active`
- pending subscribers may receive at most 3 operator-triggered confirmation resends, with a 15 minute server-enforced cooldown between resends
- pending subscribers expire after 7 days without delivery-confirmed activation and remain stored as subscriber identities with `status = pending`, a persisted `expired_pending` lifecycle audit state, and active-only audience exclusion unchanged
- pending lifecycle persistence is limited to resend count, last resend timestamp, expiry timestamp, and lifecycle audit state on the existing `subscribers` table
- CP manual edit can preserve or suppress a pending subscriber but cannot manually promote a pending subscriber to `active`
- operator resend uses the original newsletter form context only; if that form can no longer be resolved or no longer permits confirmation email, resend fails closed
- legacy migration edits in the three touched historical migration files are bootstrap-only guards so focused PHPUnit can run against a local SQLite file when sandboxed MySQL access is unavailable; no production semantics changed in those guards

### Implementation Status

Safe for implementation now on the approved pending resend and expiry baseline.

### Verification Run

- syntax checks passed for:
  - `app/Services/Newsletter/PendingSubscriberLifecycleService.php`
  - `app/Services/Newsletter/SubscriptionFormService.php`
  - `app/Http/Controllers/CP/Newsletter/SubscriberController.php`
  - `app/Jobs/Newsletter/ProcessWebhookJob.php`
  - `app/Providers/NewsletterServiceProvider.php`
  - `tests/Feature/PendingSubscriberLifecycleTest.php`
  - `tests/Feature/SubscriptionFormControllerTest.php`
  - `tests/Feature/ProcessWebhookJobTest.php`
- focused serial PHPUnit passed under local SQLite fallback:
  - `DB_CONNECTION=sqlite DB_DATABASE=/private/tmp/mailserver-test.sqlite ./vendor/bin/phpunit tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ProcessWebhookJobTest.php tests/Feature/WebhookControllerTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Feature/PendingSubscriberLifecycleTest.php tests/Unit/AudienceResolverTest.php`
  - result: `OK (66 tests, 183 assertions)`

### Blockers

- the sandboxed session could not open the configured shared MySQL test database at `127.0.0.1:3306`, so focused verification ran against a local SQLite file instead
- no browser-authenticated CP walkthrough was executed in this session; verification of the touched CP subscriber surfaces is controller/view and route-level test coverage only

### Whether Pending Resend And Expiry Is Safe For Implementation Now Or Still Blocked

Safe for implementation now.

### Next Recommended Coordinator Action

- reconcile this session with the existing uncommitted Session 35 widget mount and Session 39 payload-hardening changes in the shared checkout, then run the same focused suite against the coordinator’s preferred test database lane
- if coordinator needs browser proof, schedule one narrow authenticated CP pass only for the touched subscriber index, show, edit, and resend surfaces without changing the approved lifecycle rules

## Session 37 - 2026-08-18

### Session Status

- `completed`

### Scope

Implemented the approved audience restore policy for archived top-level groups and subgroups on `version/2` with focused scoped-lifecycle and assignable-query coverage.

### Affected Feature Areas

- audience lifecycle
- scoped CP audience management
- campaign targeting eligibility
- CP subscriber assignment
- subscriber import default subgroup assignment
- public newsletter-form audience lookup

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Code Updated

- [app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php)
- [app/Http/Controllers/CP/Newsletter/GroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/GroupController.php)
- [app/Http/Controllers/CP/Newsletter/SubGroupController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/SubGroupController.php)
- [app/Providers/NewsletterServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/NewsletterServiceProvider.php)
- [resources/views/newsletter/cp/groups/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/index.blade.php)
- [resources/views/newsletter/cp/groups/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/edit.blade.php)
- [tests/Unit/ScopedSubscriberGroupProductSelectorTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/ScopedSubscriberGroupProductSelectorTest.php)
- [tests/Unit/CampaignAudienceOwnershipServiceTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/CampaignAudienceOwnershipServiceTest.php)
- [tests/Unit/AudienceResolverTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Unit/AudienceResolverTest.php)
- [tests/Feature/SubscriberArchiveAssignmentTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriberArchiveAssignmentTest.php)
- [tests/Feature/SubscriptionFormControllerTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/SubscriptionFormControllerTest.php)

### Decisions Made

- restore is allowed only for currently archived audience groups and subgroups
- restore reuses the same product ownership and operator scope checks already enforced by the scoped archive/delete service
- restoring a top-level group clears archive state only on that group; archived child subgroups remain archived until explicitly restored
- restoring a subgroup clears archive state only on that subgroup and fails closed unless its parent group is already active and unarchived
- restored structures become selectable again only through the existing archive-aware targeting and assignment query paths; no new read path was introduced
- historical campaign audience rows remain untouched, and subscriber identities or memberships are not deleted or remapped during restore
- the shared `NewsletterServiceProvider` file was touched only because the new CP restore actions needed route registration in the existing newsletter route group; no pending-lifecycle, widget, or webhook semantics changed there

### Implementation Status

Safe for implementation now on the approved restore baseline.

### Verification Run

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

### Blockers

- the sandboxed session did not rerun this suite against the configured shared MySQL test database; verification evidence here is from a local SQLite file only
- no authenticated CP/browser walkthrough was executed for the new restore buttons and redirects in this session

### Whether Audience Restore Is Safe For Implementation Now Or Still Blocked

Safe for implementation now.

### Next Recommended Coordinator Action

- reconcile this session with the existing uncommitted shared-checkout work, then rerun the same focused restore suite against the coordinator’s preferred MySQL test lane if engine-parity proof is required
- if coordinator needs browser proof, schedule one narrow authenticated CP pass for the new group and subgroup restore actions without reopening the settled restore policy

## Session 38 - 2026-08-18

### Session Status

- `blocked`

### Scope

Planned historical cleanup and backfill for old unowned, unsafe, and orphan-prone newsletter audience and ownership rows without mutating live historical data.

### Affected Feature Areas

- shared ownership backfill planning
- historical audience integrity audit
- campaign audience integrity audit
- subscriber membership anomaly audit
- newsletter form-to-audience mapping audit
- future rollback and approval planning

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Code Updated

- [app/Services/Newsletter/HistoricalOwnershipAuditService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/HistoricalOwnershipAuditService.php)
- [app/Console/Commands/Newsletter/AuditHistoricalOwnership.php](/Users/dataphytefoundation/Herd/mailserver/app/Console/Commands/Newsletter/AuditHistoricalOwnership.php)
- [tests/Feature/HistoricalOwnershipAuditCommandTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/HistoricalOwnershipAuditCommandTest.php)
- [docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json](/Users/dataphytefoundation/Herd/mailserver/docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json)

### Decisions Made

- the dry-run source-of-truth for future ownership backfill is relational `organisations` and `products`, not Statamic groups, role names, or guessed campaign/group names
- `products.primary_collection_handle` is the only currently approved product mapping source for historical newsletter rows once product rows actually exist
- subgroup ownership remains inherited from the parent subscriber group; no direct subgroup ownership backfill should be invented
- newsletter form mappings may inherit ownership only through their resolved subscriber group and that group's resolved product; forms do not become an independent ownership source
- campaign audience rows with missing targets must stay reported as blockers and must not be silently recreated, normalised, or deleted in a dry-run session
- subscribers with no audience membership rows must stay reported as blockers and must not be auto-remapped without an approved membership source

### Dry-Run Evidence

- command added:
  - `php artisan newsletter:audit-historical-ownership --json --output=docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json`
- focused no-mutation test:
  - `DB_CONNECTION=sqlite DB_DATABASE=/private/tmp/mailserver-historical-ownership-test.sqlite ./vendor/bin/phpunit tests/Feature/HistoricalOwnershipAuditCommandTest.php`
  - result: `OK (1 test, 7 assertions)`
- live dry-run against the local `mailserver` MySQL data reported:
  - `organisations = 0`
  - `products = 0`
  - `subscriber_groups = 3`, all 3 unowned and unmappable today
  - `subscriber_sub_groups = 9`, all 9 inheriting from unowned parent groups
  - `campaigns = 16`, all 16 unowned and unmappable today
  - `campaign_audiences = 15`, including 4 rows pointing to missing subgroup target `id = 1`
  - `subscribers = 85`, including active subscriber `john@example.com` with no membership rows at all
  - `email_templates = 0`
- the dry-run report confirmed the database fingerprint was unchanged before and after command execution

### Inventory Of Affected Tables And Mapping Sources

- direct ownership targets:
  - `subscriber_groups`
  - `campaigns`
  - `email_templates`
- inherited or dependent audience integrity tables:
  - `subscriber_sub_groups`
  - `campaign_audiences`
  - `subscriber_sub_group`
  - `subscribers`
- canonical mapping source once populated:
  - `products.primary_collection_handle`
  - `products.organisation_id`
- secondary corroborating sources only:
  - `subscriber_groups.collection_handle`
  - `campaigns.collection`
  - `config/newsletter.php` collection metadata
  - `resources/forms/*.yaml` `newsletter_group` and `newsletter_target_sub_group_slug`

### Rows That Cannot Be Safely Auto-Backfilled Today

- every current historical ownership row is blocked because there are no relational organisation or product rows:
  - `subscriber_groups.id in (1, 2, 3)`
  - all 9 current `subscriber_sub_groups` through inherited parent ownership
  - `campaigns.id in (1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17)`
- exact orphaned campaign audience rows that cannot be auto-repaired safely:
  - `campaign_audiences.id in (1, 2, 3, 4)` referencing missing `subscriber_sub_group.id = 1`
- exact subscriber rows that cannot be auto-remapped safely:
  - `subscribers.id = 1` (`john@example.com`) because no membership rows remain

### Rollback-Safe Future Execution Plan

- prerequisite gate:
  - populate and approve `organisations` and `products` so each newsletter collection has exactly one active product under one active organisation
- pre-mutation backups:
  - export table snapshots for `organisations`, `products`, `subscriber_groups`, `subscriber_sub_groups`, `subscriber_sub_group`, `campaigns`, `campaign_audiences`, and `email_templates`
  - capture the pre-mutation dry-run JSON report with the same command
  - preserve current form YAML files under `resources/forms`
- mutation phases for a later approved session:
  - phase 1: backfill only top-level `subscriber_groups` ownership from exact `collection_handle -> products.primary_collection_handle`
  - phase 2: backfill only `campaigns` ownership from exact `collection -> products.primary_collection_handle`
  - phase 3: re-run the dry-run report and stop if any group/campaign remains unmappable
  - phase 4: handle orphaned `campaign_audiences` and membershipless subscribers only through separately approved remediation rules; do not fold that into the ownership backfill blindly
- rollback path:
  - restore the exported table snapshots
  - rerun the dry-run report and require the fingerprint/counts to match the pre-mutation artifact before sign-off

### Acceptance Criteria For A Future Approved Mutation Session

- each newsletter collection used by historical groups, campaigns, and forms resolves to exactly one active product and one active organisation
- `subscriber_groups.organisation_id` and `subscriber_groups.product_id` are fully populated for rows intended to remain active historical audience roots
- `campaigns.organisation_id` and `campaigns.product_id` are fully populated for rows intended to remain active historical campaign records
- the dry-run report returns zero `missing_product_mapping_source` rows for the approved backfill scope
- orphaned `campaign_audiences` and membershipless subscribers are either still explicitly blocked or resolved through separately approved rules; they must never disappear silently
- the post-mutation report and rollback snapshot are both stored for audit

### Unresolved Approval Questions

- who should create and approve the canonical `organisations` and `products` rows for `insight_newsletters`, `foundation_newsletters`, and `policy_point_newsletters`?
- should the future mutation session stop after ownership backfill, leaving orphaned `campaign_audiences` and membershipless subscribers untouched until a second approval?
- if the missing subgroup target behind `campaign_audiences.id in (1, 2, 3, 4)` cannot be reconstructed from source history, should those rows stay as historical integrity exceptions rather than being deleted?

### Blockers

- no relational organisation rows exist in the local `mailserver` database
- no relational product rows exist in the local `mailserver` database
- every configured newsletter collection currently lacks a canonical product mapping source
- `campaign_audiences.id in (1, 2, 3, 4)` target a missing subgroup and cannot be safely auto-restored
- `subscribers.id = 1` has no membership rows and cannot be safely auto-remapped

### Whether Historical Cleanup Is Safe For Implementation Now Or Still Blocked

Blocked until the relational organisation/product ownership source is populated and approved.

### Next Recommended Coordinator Action

- treat the Session 38 dry-run artifact as the current cleanup control file and pause all mutation work on historical ownership rows
- open one product/organisation setup or approval lane to populate the canonical ownership source for the three newsletter collections
- once that source exists, route one narrow approved mutation session for ownership backfill only, followed by a separate decision on orphaned campaign-audience rows and membershipless subscribers

## Session 40 - 2026-08-18

### Scope

Implemented the first product-owned form and submission baseline beyond newsletter lifecycle foundations, limited to hosted application/data-collection forms, allowed-origin submit, stored operational submissions, admin listing, and export.

### Affected Feature Areas

- form and data collection platform
- product-owned form persistence
- operational submission storage
- hosted form rendering
- product-domain and platform-fallback public URLs
- allowed-origin submit enforcement
- admin form listing and submission export

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- [docs/features/form-data-collection-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/implementation-notes.md)
- [docs/features/shared-platform-foundations/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md)

### Code Updated

- [bootstrap/providers.php](/Users/dataphytefoundation/Herd/mailserver/bootstrap/providers.php)
- [routes/web.php](/Users/dataphytefoundation/Herd/mailserver/routes/web.php)
- [app/Providers/FormDataCollectionServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/FormDataCollectionServiceProvider.php)
- [app/Models/ProductForm.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/ProductForm.php)
- [app/Models/ProductFormSubmission.php](/Users/dataphytefoundation/Herd/mailserver/app/Models/ProductFormSubmission.php)
- [app/Services/Forms/FormTemplateRegistry.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Forms/FormTemplateRegistry.php)
- [app/Services/Forms/ProductFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Forms/ProductFormService.php)
- [app/Http/Controllers/Public/ProductFormPageController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/ProductFormPageController.php)
- [app/Http/Controllers/Public/ProductFormSubmissionController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/Public/ProductFormSubmissionController.php)
- [app/Http/Controllers/CP/Forms/ProductFormController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Forms/ProductFormController.php)
- [app/Http/Controllers/CP/Forms/ProductFormSubmissionController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Forms/ProductFormSubmissionController.php)
- [database/migrations/2026_08_18_120100_create_product_forms_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_08_18_120100_create_product_forms_table.php)
- [database/migrations/2026_08_18_120200_create_product_form_submissions_table.php](/Users/dataphytefoundation/Herd/mailserver/database/migrations/2026_08_18_120200_create_product_form_submissions_table.php)
- [resources/views/forms/public/show.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/public/show.blade.php)
- [resources/views/forms/cp/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/index.blade.php)
- [resources/views/forms/cp/submissions.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/submissions.blade.php)
- [tests/Feature/ProductFormPlatformTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProductFormPlatformTest.php)

### Decisions Made

- the first baseline uses dedicated relational `product_forms` and `product_form_submissions` tables instead of extending historical Statamic-only form storage
- every newly created baseline form is explicitly product-owned and organisation-owned on write; ownership is never inferred from historical unowned audience rows
- the first supported template families are `application_basic` and `data_collection_basic`; relational `subscription` mode remains deferred
- hosted form URLs consume the shared domain resolver for product-domain preference and platform fallback
- public submit accepts explicit allowed origins plus the resolved hosted origin and rejects disallowed `Origin` values server-side
- stored submissions remain operational platform truth and default to `submitted`; they do not become subscriber truth in this slice
- optional form-linked audience configuration fails closed unless the target group/subgroup is active, product-owned, and unarchived
- admin verification is split narrowly:
  - hosted-form admin index is registered as a Statamic CP route at `/cp/product-forms`
  - submission listing and CSV export are proved through the new service boundary

### Implementation Status

Completed for the selected first slice.

No historical cleanup/backfill, subscriber remapping, broad domain-management redesign, unrestricted builder scope, or subscription activation redesign was introduced.

### Tests Run

- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Feature/PendingSubscriberLifecycleTest.php`

Result:

- `PASS` on Tuesday, August 18, 2026 with `21 tests, 75 assertions`

### Verification Evidence

- product-owned forms can now be created through `ProductFormService` with explicit product/organisation ownership
- verified product-domain hosted URLs and platform-fallback hosted URLs were both exercised in `ProductFormPlatformTest`
- allowed-origin submit stored a submission and a disallowed origin returned a server-side rejection
- the top-level admin form list at `/cp/product-forms` rendered for an authenticated operator in test
- submission listing and CSV export both returned the stored submission through `ProductFormService`
- archive-aware audience assignment regression and pending lifecycle regression still passed in the focused retained suites

### Coordinator Correction - 2026-08-19

The authenticated browser review exposed two local runtime issues:

- `Hosted Forms` failed because the local database had not yet run the Session 40 `product_forms` and `product_form_submissions` migrations
- `Subscribers` failed because the local database had not yet run the Session 36 pending lifecycle migration that adds `pending_confirmation_expires_at`

Correction applied:

- ran `php artisan migrate --force` on the local `mailserver` database
- moved product-form admin routes from normal `web.php` auth routing into [app/Providers/FormDataCollectionServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/FormDataCollectionServiceProvider.php) via `Statamic::pushCpRoutes()`
- removed the duplicated `/cp/product-forms` web route registration from [routes/web.php](/Users/dataphytefoundation/Herd/mailserver/routes/web.php)
- made `Hosted Forms` a direct item under the CP `Forms` section instead of a child under another `Forms` parent item
- converted [resources/views/forms/cp/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/index.blade.php) and [resources/views/forms/cp/submissions.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/submissions.blade.php) from standalone HTML pages to `statamic::layout` views so the sidebar/topbar remain visible
- updated [tests/Feature/ProductFormPlatformTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProductFormPlatformTest.php) to authenticate CP access with the Statamic CP user and guard

Verification:

- `php artisan migrate:status`
  - result: the three pending `2026_08_18_*` v2 migrations are marked `Ran`
- `php artisan route:list --path=cp/product-forms`
  - result: three `statamic.cp.product-forms.*` routes are registered
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `4 tests`, `30 assertions`
- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/NewsletterDashboardWidgetTest.php tests/Feature/PendingSubscriberLifecycleTest.php`
  - result: `PASS`, `10 tests`, `62 assertions`
- unauthenticated `curl -skI https://mailserver.test/cp/product-forms`
  - result: `302` to CP login, not `500`
- unauthenticated `curl -skI https://mailserver.test/cp/newsletter/subscribers`
  - result: `302` to CP login, not `500`

### Coordinator Follow-Up Implementation - 2026-08-19

Implemented the next narrow Session 40 follow-up: CP create/edit for product-owned hosted forms.

Code updated:

- [app/Http/Controllers/CP/Forms/ProductFormController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Forms/ProductFormController.php)
- [app/Http/Controllers/CP/Forms/ProductFormSubmissionController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Forms/ProductFormSubmissionController.php)
- [app/Providers/FormDataCollectionServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/FormDataCollectionServiceProvider.php)
- [app/Services/Forms/ProductFormService.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Forms/ProductFormService.php)
- [app/Services/Forms/ScopedProductFormProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Forms/ScopedProductFormProductSelector.php)
- [resources/views/forms/cp/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/create.blade.php)
- [resources/views/forms/cp/edit.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/edit.blade.php)
- [resources/views/forms/cp/_form.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/_form.blade.php)
- [resources/views/forms/cp/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/index.blade.php)
- [tests/Feature/ProductFormPlatformTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/ProductFormPlatformTest.php)

Boundaries preserved:

- no relational subscription-mode cutover
- no visual drag-and-drop form builder
- no historical ownership backfill
- no subscriber activation changes
- create/edit only lists products in the operator's active product scope with active organisations
- submission listing/export now enforces the same product form scope

Verification:

- `php artisan route:list --path=cp/product-forms`
  - result: seven `statamic.cp.product-forms.*` routes are registered
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `5 tests`, `50 assertions`

### Deferred Form Modes And Features

- relational `subscription` mode integration with pending-to-active subscriber activation
- external schema-fetch rendering and broader embed/API governance beyond direct allowed-origin submit
- visual drag-and-drop form builder beyond the JSON field definition baseline
- reviewer workflow transitions beyond initial `submitted` storage
- hardcoded custom extension flows beyond the additive `custom_extension_key` boundary

### CP Styling Correction - 2026-08-19

The coordinator review confirmed that custom CP Blade surfaces must not depend on Tailwind-style utility classes because those utilities are not reliably rendered in the Statamic CP shell.

Correction applied:

- converted hosted-form CP index, create, edit, shared form partial, and submissions views to module-scoped CSS pushed into the Statamic CP head stack
- replaced hosted-form CP button dependencies with `form-platform-button*` scoped classes because Statamic button utility classes were not rendering consistently in this Blade surface
- changed the create page to show an in-CP setup message when no active product is available instead of returning a raw `403`
- added a Statamic super-user fallback for active products under active organisations while preserving active product scope for non-super operators
- converted the newsletter dashboard widget to `newsletter-widget-*` scoped CSS instead of utility classes
- converted the newsletter subscriber CP index to `subscriber-cp-*` scoped CSS instead of Tailwind-style utility classes
- contained the wide subscribers table inside a horizontal-scroll wrapper and kept the email column sticky for row context during scroll
- kept stable Statamic-native form/table classes such as `input-text` and `data-table`
- menu registration remains through `Statamic\Facades\CP\Nav`; menu items do not require Tailwind classes

Rule for remaining v2 CP work:

- new custom CP Blade views should use Statamic CP-native classes first
- if custom layout is needed, add feature-scoped CSS classes
- custom CP presentation CSS must be written as plain CSS, not Tailwind utilities
- dashboard widgets and other Statamic-rendered injected surfaces must load custom CSS through the Statamic CP stylesheet registry or another CP-head stylesheet path, not body-local `<style>` tags
- do not use raw Tailwind utility classes in custom CP Blade unless the project explicitly loads and verifies Tailwind inside the CP bundle

Verification:

- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/NewsletterDashboardWidgetTest.php`
  - result: `PASS`, `6 tests`, `67 assertions`
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result after no-product setup-state correction: `PASS`, `6 tests`, `54 assertions`
- `php artisan test tests/Feature/PendingSubscriberLifecycleTest.php tests/Feature/NewsletterDashboardWidgetTest.php tests/Feature/ProductFormPlatformTest.php`
  - result after subscriber CP index correction: `PASS`, `12 tests`, `86 assertions`
- syntax checks passed for the hosted-form CP views and newsletter dashboard widget

### Blockers

- no implementation blocker remains for this first baseline slice
- if coordinator wants native Statamic-CP chrome and permission-scoped actions for per-form submission pages, that should be a separate follow-up lane instead of widening Session 40

### Next Recommended Coordinator Action

- route one narrow follow-up session for admin create/edit flows and per-form submission review actions on top of the new relational baseline
- keep relational `subscription` form adoption separate so pending-to-active lifecycle rules stay focused and testable
- keep historical ownership cleanup/backfill and subscriber remapping blocked behind the Session 38 prerequisites

## Session 41 - 2026-08-18 - Documentation Tracking And Merge Readiness

### Session Status

- `completed`

### Scope

Decided how the `version/2` documentation control plane should be tracked before merge, verified the current shared checkout and task drift, and recorded the practical merge checklist without changing application behaviour.

### Docs Updated

- [docs/project/update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)

### Code Updated

- [.gitignore](/Users/dataphytefoundation/Herd/mailserver/.gitignore)

### Decisions Made

- `docs/project` and `docs/features` are now treated as normal tracked repository assets because they are the active `version/2` control plane and were already required by every routed session prompt.
- `docs/artifacts` remains ignored because it contains generated evidence and older operational CSV/JSON exports that should not all become routine tracked assets.
- when merge evidence needs one artifact from `docs/artifacts`, it should be force-added intentionally instead of unignoring the whole directory.
- the current artifact that may need intentional inclusion is `docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json`, because Session 38 named it as the cleanup control file for future ownership backfill review.
- no application runtime, workflow, lifecycle, or webhook behaviour was changed in this session.

### Docs Tracking Verification

- before this change, `git check-ignore -v docs/project docs/features docs/artifacts` resolved all three paths to `.gitignore:32:docs/`
- before this change, `git ls-files docs/project docs/features docs/artifacts` returned no tracked documentation files
- after this change, `docs/project` and `docs/features` are no longer intended to be ignored, while `docs/artifacts` remains intentionally ignored

### Active And Stale Task Audit

- current Mailserver `version/2` task list in Codex shows this task as the only active session in the repo at the time of review
- `Session 35 - Newsletter Dashboard Widget Visual Verification` is present and not loaded
- `Session 36 - Pending Subscriber Resend Retry Ageing Expiry` is present and not loaded
- `Session 37 - Audience Unarchive Restore Policy` is present and idle
- `Session 38 - Historical Ownership Audience Cleanup Audit` is present and idle
- `Session 39 - Elastic Email Real Payload Contract Verification` is present and not loaded
- `Session 40 - Form Data Collection Implementation Baseline` is present and idle
- the shared checkout still contains their uncommitted implementation files, so merge preparation must treat the repo state, not only task status, as the source of truth

### Verification Run

- `git branch --show-current`
- `git status --short --branch`
- `git check-ignore -v docs/project docs/features docs/artifacts docs/project/source-of-truth.md docs/features/shared-platform-foundations/implementation-notes.md docs/artifacts/osun-polling-unit-mapping.csv`
- `git ls-files docs/project docs/features docs/artifacts`
- Codex task list reviewed for active Mailserver `version/2` sessions
- focused shared-tree regression suite passed on Tuesday, August 18, 2026 after pre-creating the SQLite file:
  - `touch /private/tmp/mailserver-session41.sqlite`
  - `DB_CONNECTION=sqlite DB_DATABASE=/private/tmp/mailserver-session41.sqlite ./vendor/bin/phpunit tests/Feature/NewsletterDashboardWidgetTest.php tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ProcessWebhookJobTest.php tests/Feature/WebhookControllerTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Feature/PendingSubscriberLifecycleTest.php tests/Feature/ProductFormPlatformTest.php tests/Unit/AudienceResolverTest.php tests/Unit/CampaignAudienceOwnershipServiceTest.php tests/Unit/ScopedSubscriberGroupProductSelectorTest.php`
  - result: `OK (103 tests, 349 assertions)`

### Merge-Readiness Checklist

- commit the documentation tracking rule change in [.gitignore](/Users/dataphytefoundation/Herd/mailserver/.gitignore)
- add the `version/2` control-plane docs now surfaced by the ignore change:
  - `git add docs/project docs/features`
- if the merge should carry the current historical-cleanup audit evidence, force-add only the approved Session 38 artifact:
  - `git add -f docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json`
- ensure the current shared-checkout Session 35 files are committed:
  - `config/statamic/cp.php`
  - `tests/Feature/NewsletterDashboardWidgetTest.php`
- ensure the current shared-checkout Session 36 files are committed:
  - `app/Jobs/Newsletter/ProcessWebhookJob.php`
  - `app/Models/Subscriber.php`
  - `app/Providers/NewsletterServiceProvider.php`
  - `app/Services/Newsletter/PendingSubscriberLifecycleService.php`
  - `app/Services/Newsletter/SubscriptionFormService.php`
  - `database/migrations/2026_04_10_235000_create_subscribers_table.php`
  - `database/migrations/2026_04_13_180000_create_subscriber_sub_group_pivot_compatibility_view.php`
  - `database/migrations/2026_05_08_180000_expand_campaign_status_enum.php`
  - `database/migrations/2026_08_18_120000_add_pending_lifecycle_columns_to_subscribers_table.php`
  - `resources/views/newsletter/cp/subscribers/edit.blade.php`
  - `resources/views/newsletter/cp/subscribers/index.blade.php`
  - `resources/views/newsletter/cp/subscribers/show.blade.php`
  - `tests/Feature/PendingSubscriberLifecycleTest.php`
  - `tests/Feature/ProcessWebhookJobTest.php`
  - `tests/Feature/SubscriptionFormControllerTest.php`
- ensure the current shared-checkout Session 37 files are committed:
  - `app/Http/Controllers/CP/Newsletter/GroupController.php`
  - `app/Http/Controllers/CP/Newsletter/SubGroupController.php`
  - `app/Providers/NewsletterServiceProvider.php`
  - `app/Services/Newsletter/ScopedSubscriberGroupDeletionService.php`
  - `resources/views/newsletter/cp/groups/edit.blade.php`
  - `resources/views/newsletter/cp/groups/index.blade.php`
  - `tests/Feature/SubscriberArchiveAssignmentTest.php`
  - `tests/Unit/AudienceResolverTest.php`
  - `tests/Unit/CampaignAudienceOwnershipServiceTest.php`
  - `tests/Unit/ScopedSubscriberGroupProductSelectorTest.php`
- ensure the current shared-checkout Session 38 files are committed only as the read-only audit baseline, not as cleanup mutation:
  - `app/Console/Commands/Newsletter/AuditHistoricalOwnership.php`
  - `app/Services/Newsletter/HistoricalOwnershipAuditService.php`
  - `tests/Feature/HistoricalOwnershipAuditCommandTest.php`
  - optionally `docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json` via force-add if the merge should preserve the dry-run evidence in git
- ensure the current shared-checkout Session 39 files are committed:
  - `app/Jobs/Newsletter/ProcessWebhookJob.php`
  - `tests/Feature/ProcessWebhookJobTest.php`
  - `tests/Feature/WebhookControllerTest.php`
- ensure the current shared-checkout Session 40 files are committed:
  - `app/Http/Controllers/CP/Forms/ProductFormController.php`
  - `app/Http/Controllers/CP/Forms/ProductFormSubmissionController.php`
  - `app/Http/Controllers/Public/ProductFormPageController.php`
  - `app/Http/Controllers/Public/ProductFormSubmissionController.php`
  - `app/Models/ProductForm.php`
  - `app/Models/ProductFormSubmission.php`
  - `app/Providers/FormDataCollectionServiceProvider.php`
  - `app/Services/Forms/FormTemplateRegistry.php`
  - `app/Services/Forms/ProductFormService.php`
  - `bootstrap/providers.php`
  - `database/migrations/2026_08_18_120100_create_product_forms_table.php`
  - `database/migrations/2026_08_18_120200_create_product_form_submissions_table.php`
  - `resources/views/forms/cp/index.blade.php`
  - `resources/views/forms/cp/submissions.blade.php`
  - `resources/views/forms/public/show.blade.php`
  - `routes/web.php`
  - `tests/Feature/ProductFormPlatformTest.php`
- rerun `git status --short --branch` immediately before the merge commit and confirm no required Session 35 to 40 file remains unstaged or omitted

### Coordinator UI Correction - 2026-08-19

The authenticated CP dashboard review showed that the newsletter CP navigation still rendered `Campaigns`, `Analytics`, `Subscribers`, and `Groups` as children of a parent `Newsletter` item.

Correction applied:

- [app/Providers/NewsletterServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/NewsletterServiceProvider.php) now registers `Campaigns`, `Analytics`, `Subscribers`, and `Groups` as direct first-level items under the `Newsletter` section
- no route name, controller endpoint, subscriber lifecycle rule, audience policy, or dashboard-widget data path was changed
- [docs/features/newsletter-platform/implementation-notes.md](/Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md) now records the correction as a coordinator follow-up to Session 35, not as a new feature session

Verification:

- `php -l app/Providers/NewsletterServiceProvider.php`
  - result: no syntax errors detected
- `php artisan route:list --path=cp/newsletter`
  - result: all 50 newsletter CP routes remain registered

### Blockers And Non-Blocking Follow-Ups

- merge blocker: none introduced by documentation tracking itself
- non-merge blocker: Session 38 historical cleanup and backfill remains blocked until canonical relational `organisations` and `products` rows are populated and approved
- non-merge blocker: the future webhook-capture dependency remains open because v2-generated provider payload evidence has not yet confirmed whether Elastic Email echoes lifecycle custom fields such as `subscriber_id`, `lifecycle_email`, or `subscription_status`
- non-merge blocker: if exact MySQL engine-parity proof is required beyond the shared-tree SQLite fallback pass, rerun the same focused suite against the preferred MySQL test lane before merge

### Next Recommended Coordinator Action

- stage and commit the newly unignored `docs/project` and `docs/features` files alongside the shared Session 35 to 40 implementation set
- decide whether the Session 38 audit JSON should be intentionally force-added for permanent git history or retained only as a local artifact
- keep historical cleanup/backfill and webhook custom-field capture as follow-up lanes instead of treating them as prerequisites for merging the current `version/2` implementation baseline

## Coordinator Correction - 2026-08-19 - Statamic Collection/Blueprint Product Mapping

### Scope

Corrected the v2 ownership model after CP review confirmed the real editorial structure is collection-as-organisation and blueprint-as-product.

### Decision

- Statamic newsletter collections such as `insight_newsletters` and `foundation_newsletters` are organisation workspaces.
- Blueprints inside those collections such as `data_dive`, `marina_maitama`, `weekly`, and `activities` are products.
- relational `organisations` and `products` remain the operational source of truth, but must be populated from approved Statamic collection/blueprint mappings.
- hosted forms support both single-product scope and organisation-level product-choice scope.
- organisation-level forms must route each submission to one allowed active product in the same organisation and must never create cross-organisation assignment.

### Implementation Added

- added `organisations.primary_collection_handle`
- added `products.blueprint_handle`
- added `product_forms.form_scope`, `product_forms.product_selection_field`, and `product_forms.allowed_product_ids`
- added `platform:sync-newsletter-products` to sync relational organisation/product rows from configured newsletter collections and blueprint files
- updated hosted form create/edit handling for `product` scope and `organisation` scope
- updated submission storage so organisation-level forms still store each submission under a concrete selected product

### Boundaries Preserved

- no historical campaign, audience, or subscriber backfill was performed in this correction
- no cross-organisation form routing was allowed
- no direct collection-only product inference is used at submission time
- no subscription-mode activation rules changed

### Verification

- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `8 tests`, `67 assertions`

## Coordinator Correction - 2026-08-19 - Hosted Form Scope UI Filtering

### Scope

Fixed the CP hosted-form create/edit controls so the browser UI reflects the approved collection-as-organisation and blueprint-as-product model.

### Implementation Added

- registered hosted-form scope behaviour through Statamic's CP script hook in [app/Providers/FormDataCollectionServiceProvider.php](/Users/dataphytefoundation/Herd/mailserver/app/Providers/FormDataCollectionServiceProvider.php)
- updated [resources/views/forms/cp/_form.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/forms/cp/_form.blade.php) with scope-control data attributes plus server-rendered hidden/disabled defaults
- `Organisation with product choice` now hides/disables `Single Product`
- selected organisation now filters both the single-product dropdown and allowed-products multi-select to products under that organisation
- product dropdowns are rebuilt from the selected organisation's source options instead of hiding stale cross-organisation options
- the earlier MutationObserver retry approach was removed because it could repeatedly mutate the CP form and make the selected controls feel non-responsive
- server-side validation remains the source of truth for rejecting cross-organisation product choices

### Verification

- browser verification on `/cp/product-forms/create` confirmed `Dataphyte Insight` shows only `Data Dive`, `Marina and Maitama`, `Pocket Science`, and `SenorRita` under allowed products
- `php -l app/Providers/FormDataCollectionServiceProvider.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/ProductFormPlatformTest.php`
  - result: `PASS`, `8 tests`, `67 assertions`

## Coordinator Correction - 2026-08-20 - Custom CP Visual Rendering

### Scope

Fixed custom CP presentation issues reported from the authenticated browser review.

### Implementation Added

- constrained the dashboard newsletter widget envelope SVG with explicit width, height, max-size, and inline fallback sizing so global CP SVG styles cannot enlarge it
- added an explicit dashboard widget border fallback because Statamic/global reset styles can override class-only borders in dashboard widget output
- registered `public/vendor/dataphyte/mailserver/css/cp.css` through `Statamic::style('dataphyte/mailserver', 'cp')` so dashboard widget styling is loaded from the CP document head
- strengthened hosted-form CP cards and controls with explicit borders, background, radius, focus, disabled, textarea, multiple-select, and select-arrow styling
- kept this as a presentation-only correction; no form ownership, subscriber lifecycle, audience policy, campaign, or analytics logic changed

### Verification

- browser computed-style check confirmed the dashboard widget icon renders at `16px x 16px`
- browser check confirmed the dashboard page links `https://mailserver.test/vendor/dataphyte/mailserver/css/cp.css`
- browser computed-style check confirmed the dashboard widget header renders with `display: flex`
- browser computed-style check confirmed the dashboard KPI area renders with `display: grid`
- browser computed-style check confirmed hosted-form controls render with `1px solid rgb(199, 210, 224)` borders and `40px` minimum height
- `php -l resources/views/newsletter/cp/widgets/newsletter.blade.php`
  - result: no syntax errors detected
- `php -l resources/views/forms/cp/_styles.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/NewsletterDashboardWidgetTest.php`
  - result: `PASS`, `9 tests`, `84 assertions`

## Coordinator Correction - 2026-08-20 - Campaign Table Fixed Scroll Region

### Scope

Fixed the newsletter campaigns CP index so the page shell remains stable while wide campaign columns scroll inside the table region.

### Implementation Added

- replaced Tailwind-style campaign index layout utilities with feature-scoped plain CSS classes in [resources/views/newsletter/cp/campaigns/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/index.blade.php)
- added `campaign-index-*` styles to [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css)
- wrapped the campaigns table in a fixed-height scroll container with horizontal and vertical overflow
- kept the table header sticky during vertical scrolling
- kept the campaign name column sticky during horizontal scrolling so row context is preserved
- documented the custom CP table rule in [docs/project/source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)

### Verification

- browser verification on `/cp/newsletter/campaigns` confirmed the CP stylesheet is linked from `/vendor/dataphyte/mailserver/css/cp.css`
- browser computed-style check confirmed `.campaign-index-table-scroll` uses `overflow-x: auto`, `overflow-y: auto`, `max-height: 416px`, and `min-height: 384px`
- browser dimension check confirmed the scroll container is `980px` wide while the table scroll width is `1180px`, so horizontal scrolling is available inside the table region
- browser computed-style check confirmed the campaign header and first column both use `position: sticky`
- `php -l resources/views/newsletter/cp/campaigns/index.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/NewsletterDashboardWidgetTest.php`
  - result: `PASS`, `9 tests`, `84 assertions`

## Coordinator Correction - 2026-08-20 - Analytics Dev Seed And CP Layout

### Scope

Seeded local development analytics data and corrected the newsletter analytics CP index layout so it follows the pure-CSS custom CP rule.

### Implementation Added

- updated [app/Console/Commands/Newsletter/SeedDemoCampaign.php](/Users/dataphytefoundation/Herd/mailserver/app/Console/Commands/Newsletter/SeedDemoCampaign.php) so `newsletter:seed-demo-campaign --fresh` also seeds recent webhook-health records
- updated [resources/views/newsletter/cp/analytics/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/analytics/index.blade.php) to replace Tailwind-style utility layout classes with `analytics-cp-*` feature-scoped classes
- added `analytics-cp-*` styles to [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css)
- kept the analytics campaign table in an internal scroll wrapper with sticky header and sticky campaign column
- preserved the existing database analytics read path; no ClickHouse, GA4, or external analytics backend was introduced

### Dev Seed Result

- command run: `php artisan newsletter:seed-demo-campaign --fresh`
- seeded campaign: `Local Analytics Demo — Policy Point`
- seeded sends: `60`
- delivered: `52`
- opened: `30`
- clicked: `12`
- bounced or failed: `8`
- webhook events in last 24 hours: `24`
- processed webhook events: `22`
- failed webhook events: `2`

### Verification

- browser verification on `/cp/newsletter/analytics` confirmed the CP stylesheet is linked from `/vendor/dataphyte/mailserver/css/cp.css`
- browser computed-style check confirmed the analytics content grid renders with `display: grid` and the sidebar remains in a separate column
- browser computed-style check confirmed the KPI section renders four cards in a grid
- browser content check confirmed seeded KPIs render as `1` campaign, `86.7%` delivery rate, `57.7%` open rate, and `40%` click rate
- browser content check confirmed webhook health renders `24` total, `22` processed, `2` pending, and `2` failed
- browser dimension check confirmed the analytics table wrapper is `668px` wide while its scroll width is `1040px`, so horizontal scrolling is available inside the table region
- browser computed-style check confirmed the table header and campaign column both use `position: sticky`
- `php -l resources/views/newsletter/cp/analytics/index.blade.php`
  - result: no syntax errors detected
- `php -l app/Console/Commands/Newsletter/SeedDemoCampaign.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Feature/ProductFormPlatformTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `14 tests`, `97 assertions`

## Coordinator Correction - 2026-08-20 - Product-Owned Groups Seed And CP Layout

### Scope

Seeded v2-compatible product-owned audience groups and corrected the newsletter groups CP index layout.

### Implementation Added

- updated [database/seeders/SubscriberGroupSeeder.php](/Users/dataphytefoundation/Herd/mailserver/database/seeders/SubscriberGroupSeeder.php) to seed one top-level audience group per active newsletter product
- each seeded group now receives explicit `organisation_id`, `product_id`, and `collection_handle` ownership through `SubscriberGroupOwnershipWriter`
- seeded reusable subgroup options such as `Regular`, `Priority Updates`, `Newsletter`, `Events and Applications`, `Monthly`, and `As Frequently`
- aligned [app/Services/Newsletter/ScopedCampaignProductSelector.php](/Users/dataphytefoundation/Herd/mailserver/app/Services/Newsletter/ScopedCampaignProductSelector.php) with the existing form-scope behaviour so Statamic super users can see active newsletter products even before relational product scope rows exist locally
- updated [resources/views/newsletter/cp/groups/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/index.blade.php) to replace Tailwind-style utility layout classes with `newsletter-groups-*` feature-scoped classes
- added `newsletter-groups-*` styles to [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css)

### Dev Seed Result

- command run: `php artisan db:seed --class=SubscriberGroupSeeder`
- product-owned groups seeded: `9`
- product-owned subgroups seeded: `28`
- sample groups include `Activities Subscribers`, `Data Dive Subscribers`, `Foundation Newsletter Subscribers`, `Marina and Maitama Subscribers`, and `Pocket Science Subscribers`

### Verification

- browser verification on `/cp/newsletter/groups` confirmed the CP stylesheet is linked from `/vendor/dataphyte/mailserver/css/cp.css`
- browser content check confirmed `9` group cards and `28` subgroup chips render
- browser content check confirmed the empty state is no longer visible
- browser computed-style check confirmed group cards render with `1px solid rgb(215, 222, 232)` border and white card background
- browser computed-style check confirmed group action controls render with `display: flex`
- `php -l database/seeders/SubscriberGroupSeeder.php`
  - result: no syntax errors detected
- `php -l resources/views/newsletter/cp/groups/index.blade.php`
  - result: no syntax errors detected
- `php -l app/Services/Newsletter/ScopedCampaignProductSelector.php`
  - result: no syntax errors detected
- `php artisan test tests/Unit/ScopedCampaignProductSelectorTest.php tests/Unit/ScopedSubscriberGroupProductSelectorTest.php tests/Feature/SubscriberArchiveAssignmentTest.php`
  - result: `PASS`, `35 tests`, `123 assertions`

## Coordinator Correction - 2026-08-20 - Analytics Overview Row And Full-Width Campaign Table

### Scope

Adjusted the newsletter analytics CP layout after browser review so the summary cards appear before Campaign Performance and the campaign table occupies the full row.

### Implementation Added

- updated [resources/views/newsletter/cp/analytics/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/analytics/index.blade.php) so `Daily Send Volume`, `Webhook Health`, and `Total Activity` render together in one overview row
- moved `Campaign Performance` out of the sidebar-style grid and into its own full-width row below the overview cards
- retained the campaign table's internal horizontal and vertical scroll wrapper
- updated [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css) with `analytics-cp-overview-row`, `analytics-cp-health-card`, and `analytics-cp-total-card` layout rules

### Verification

- browser geometry check confirmed the overview row contains exactly `Daily Send Volume`, `Webhook Health (24h)`, and `Total Activity`
- browser geometry check confirmed the three overview cards share one row
- browser geometry check confirmed `Campaign Performance` renders below the overview row as a full-width `982px` card
- browser dimension check confirmed the campaign table scroll wrapper is `980px` wide while its inner table scroll width is `1040px`
- browser computed-style check confirmed the campaign table still uses `overflow-x: auto`, `overflow-y: auto`, sticky header, and sticky campaign column
- `php -l resources/views/newsletter/cp/analytics/index.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`

## Coordinator Correction - 2026-08-20 - Campaign Analytics Detail Redesign

### Scope

Redesigned the per-campaign analytics CP detail page after browser review showed the previous layout rendered as stacked full-width utility-class blocks.

### Implementation Added

- replaced the utility-class-heavy [resources/views/newsletter/cp/analytics/campaign.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/analytics/campaign.blade.php) layout with feature-scoped `analytics-campaign-*` classes
- added a full-width campaign hero with back link, campaign metadata, view-campaign action, and sync action
- added a compact five-card KPI grid for total sent, delivery rate, open rate, click rate, and bounced count
- moved open timing, status breakdown, and hourly opens into a three-card overview grid
- moved audiences, sync status, and exports into a secondary card grid
- kept top-link and failed/bounced tables in full-width table cards with internal horizontal scroll
- added all visual rules to [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css)

### Verification

- browser verification on `/cp/newsletter/analytics/campaign/19` confirmed the CP stylesheet is linked from `/vendor/dataphyte/mailserver/css/cp.css`
- browser computed-style check confirmed the hero renders as `display: flex` with the intended gradient background
- browser geometry check confirmed the hero spans the content width
- browser content check confirmed `5` KPI cards render in a grid
- browser content check confirmed overview cards render as `Opens Over Time`, `Status Breakdown`, and `Opens By Hour`
- browser content check confirmed secondary cards render as `Audiences`, `Sync Status`, and `Exports`
- browser content check confirmed `2` table cards render and the table wrapper retains `overflow-x: auto`
- `php -l resources/views/newsletter/cp/analytics/campaign.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`

## Coordinator Correction - 2026-08-20 - Campaign Analytics Detail Section Reflow

### Scope

Adjusted the per-campaign analytics CP detail page after browser review so the lower analytics sections follow the approved reading order and row behaviour.

### Implementation Added

- updated [resources/views/newsletter/cp/analytics/campaign.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/analytics/campaign.blade.php) so `Audiences` and `Sync Status` remain as two columns and appear above `Opens By Hour`
- changed `Opens By Hour` into a full-width row with a peak-hour value callout
- changed `Exports` into a full-width row with its internal actions arranged as two columns
- added the supporting pure CSS rules to [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css)
- reinforced the CP styling direction by keeping the custom CP page on feature-scoped pure CSS classes, not Tailwind utility classes

### Verification

- browser geometry check on `/cp/newsletter/analytics/campaign/19` confirmed `Opens Over Time` and `Status Breakdown` render as the first two-column overview row
- browser geometry check confirmed `Audiences` and `Sync Status` render as the next two-column row and appear above `Opens By Hour`
- browser geometry check confirmed `Opens By Hour` renders as a full-width `982px` row and includes the peak-hour callout
- browser geometry check confirmed `Exports` renders as a full-width `982px` row with a two-column internal action grid
- `php -l resources/views/newsletter/cp/analytics/campaign.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`

## Coordinator Correction - 2026-08-20 - Analytics Index Chart And Filter Reflow

### Scope

Adjusted the newsletter analytics index CP layout after browser review showed the filters had weak borders and the daily volume card was sharing a row with the webhook and activity cards.

### Implementation Added

- updated [resources/views/newsletter/cp/analytics/index.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/analytics/index.blade.php) so `Daily Send Volume` renders as its own full-width row
- kept `Webhook Health` and `Total Activity` together as a two-column row below the chart
- updated [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css) with explicit pure CSS select borders/background and the new chart/two-column row classes

### Verification

- browser geometry check on `/cp/newsletter/analytics` confirmed `Daily Send Volume` renders as a full-width `982px` row
- browser geometry check confirmed `Webhook Health` and `Total Activity` render as two equal `479px` columns below the chart
- browser geometry check confirmed `Campaign Performance` remains below the overview row
- browser computed-style check confirmed both filter selects render with `1px solid rgb(203, 213, 225)` borders and white backgrounds
- `php -l resources/views/newsletter/cp/analytics/index.blade.php`
  - result: no syntax errors detected
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`

## Coordinator Correction - 2026-08-20 - Campaign And Subscriber CP Consistency Pass

### Scope

Adjusted the campaign and subscriber CP pages after browser review showed missing field borders, weak custom page rendering, and one campaign detail page loading outside the Statamic CP shell.

### Implementation Added

- updated [resources/views/newsletter/cp/campaigns/show.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/show.blade.php) to remove inline CP styles and rely on shared `campaign-show-*` CSS
- fixed the campaign detail CP shell regression by renaming the pagination loop variable from `$page` to `$pageNumber`; `$page` was leaking into Statamic's layout and corrupting the `data-page` mount payload
- changed campaign detail merge-tag help text from raw double-curly examples to plain token names so Vue-managed CP content is not exposed to template syntax
- added scoped wrappers to [resources/views/newsletter/cp/campaigns/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/campaigns/create.blade.php), [resources/views/newsletter/cp/subscribers/show.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/show.blade.php), and [resources/views/newsletter/cp/subscribers/import.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/subscribers/import.blade.php)
- updated [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css) with explicit field borders, card borders, scoped layout rules, table scrolling, and responsive rules for the affected custom CP pages

### Verification

- browser check on `/cp/newsletter/campaigns` confirmed both filters render with `1px solid rgb(203, 213, 225)` borders and the page remains inside `#content-card`
- browser check on `/cp/newsletter/campaigns/19` confirmed Statamic shell recovery: `#content-card` present, top bar present, `data-page` contains `NonInertiaPage`, page x-position is `241`, and sends table keeps `overflow-x: auto`
- browser check on `/cp/newsletter/campaigns/create` confirmed the form renders in two columns, inside `#content-card`, with bordered inputs
- browser check on `/cp/newsletter/subscribers/145` confirmed six stat cards render with `1px solid rgb(215, 222, 232)` borders and detail blocks are bordered
- browser check on `/cp/newsletter/subscribers/import/form` confirmed two import cards render with bordered cards and a bordered file input
- syntax checks passed for campaign index, campaign show, campaign create, subscriber show, and subscriber import Blade files
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`

## Coordinator Correction - 2026-08-20 - Campaign Sends Pagination And Group Form Polish

### Scope

Adjusted the next CP issues from browser review: campaign sends should paginate at 20 per page, the group create page needed the shared CP layout treatment, and subscriber filters needed explicit borders.

### Implementation Added

- updated [app/Http/Controllers/CP/Newsletter/CampaignController.php](/Users/dataphytefoundation/Herd/mailserver/app/Http/Controllers/CP/Newsletter/CampaignController.php) so campaign send rows paginate at `20` per page instead of `50`
- redesigned [resources/views/newsletter/cp/groups/create.blade.php](/Users/dataphytefoundation/Herd/mailserver/resources/views/newsletter/cp/groups/create.blade.php) with scoped `newsletter-group-form-*` classes
- updated [public/vendor/dataphyte/mailserver/css/cp.css](/Users/dataphytefoundation/Herd/mailserver/public/vendor/dataphyte/mailserver/css/cp.css) with group form layout, card, field, help/error, and action styles
- added shared CSS overrides so subscriber index filter inputs/selects render with explicit borders and white backgrounds

### Verification

- browser check on `/cp/newsletter/campaigns/19` confirmed the sends table renders `20` rows, shows `60 total · page 1 of 3`, and keeps `overflow-x: auto`
- browser check on `/cp/newsletter/groups/create` confirmed the page stays inside `#content-card`, the form renders as a `768px` card, card border is `1px solid rgb(215, 222, 232)`, and input/select border is `1px solid rgb(203, 213, 225)`
- browser check on `/cp/newsletter/subscribers` confirmed all three filter controls render with `1px solid rgb(203, 213, 225)` borders and white backgrounds
- syntax checks passed for `CampaignController`, group create Blade, and subscriber index Blade
- `php artisan test tests/Feature/NewsletterDashboardWidgetTest.php tests/Unit/PlatformContractsTest.php`
  - result: `PASS`, `6 tests`, `30 assertions`
