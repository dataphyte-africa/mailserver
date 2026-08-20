# Newsletter Platform Implementation Notes

## Purpose

These notes define the implementation direction and guardrails for the newsletter platform once coding begins.

## Implementation Direction

### Keep The Editorial And Delivery Models Separate

Treat:

- editorial entry
- campaign
- audience targeting
- delivery tracking

as separate responsibilities even when they are closely linked.

Do not collapse campaign operations into content-entry state alone.

### Keep Product Context Explicit

Newsletter operations should resolve product context explicitly through shared ownership rules, not through inferred collection naming alone.

### Use Shared Services For Cross-Cutting Concerns

When implementation starts, newsletter code should use shared or approved service boundaries for:

- domain resolution
- URL generation
- authorization
- analytics read and write
- delivery tracking

Do not embed long-term feature logic for these concerns directly inside controllers or views.

## Subscriber And Preference Guardrails

- do not implement activation as immediate-on-submit
- do not treat subscriber identity as identical to product membership
- do not let one product form assign a subscriber into another product's audience tree
- do not treat `pending`, `unsubscribed`, `bounced`, or `complained` subscribers as send-eligible
- do not silently reactivate `complained` subscribers

## Suggested Internal Boundaries

Within the modular monolith, newsletter implementation should prefer boundaries such as:

- campaign drafting and state handling
- template resolution and rendering
- audience resolution
- send orchestration
- send retry and finalisation
- newsletter reporting queries

Exact class names remain implementation details unless settled elsewhere.

## Reporting Rule

Production reads should continue to use the database-backed analytics path until the shared analytics session explicitly approves otherwise.

Do not add alternate read-backend assumptions in newsletter implementation.

## Domain Rule

Newsletter implementation must use shared domain-resolution services for:

- browser-view links
- unsubscribe links
- preferences links
- product-facing public newsletter surfaces

Do not hardcode domain logic locally in newsletter templates or controllers.

## Authorization Rule

Workflow progression should be enforced through shared authorization and policy rules, not only through UI visibility.

## Dependency Guardrails

- exact event-history persistence must stay aligned with the analytics contract session
- exact permission slugs must stay aligned with the roles and workflow session
- any operator override or exceptional recovery path must be documented first if it changes suppression rules

## Deferred Implementation Detail

This session deliberately does not settle:

- exact database schema for campaign review and approval metadata
- final permission slug naming
- final persistence model for template management
- final implementation mechanics for domain verification state
- final dynamic segmentation model

## Session 33 Verification Notes

Focused verification on July 31, 2026 confirmed the current pending lifecycle boundary behaves as documented when exercised through route-level feature tests and provider-webhook-shaped payloads.

Verified:

- public signup creates `pending` subscribers with `confirmed_at = null`
- pending preference updates do not activate subscribers before delivery-confirmed activation
- resubscribe returns unsubscribed subscribers to `pending` until a correlated lifecycle email is delivered
- CP subscriber status filtering, edit persistence, CSV export, and widget counts all expose `pending` separately from `active`
- campaign audience resolution remains `active`-only

Narrow hardening applied:

- `ProcessWebhookJob` now accepts subscription lifecycle state changes only from `subscription_confirmation` webhook payloads whose `subscription_status` is `subscribed` or `resubscribed`
- this prevents delivered profile-update lifecycle mails from activating a `pending` subscriber

Verification limitation:

- authenticated browser/CP access was not available in the delegated worktree session
- strongest executed substitute was focused Laravel coverage over the same CP-facing controller, export, widget, and audience-resolution paths

## Session 34 Authenticated CP Pending Surface Verification Notes

Focused authenticated CP verification on July 31, 2026 confirmed the current `version/2` subscriber surfaces expose and preserve `pending` correctly once the local schema is migrated to the branch head.

Verified in browser and authenticated HTTP:

- `statamic.cp.newsletter.subscribers.index` at `/cp/newsletter/subscribers` renders the pending subscriber row and a distinct `Pending` badge
- the same CP index exposes the explicit status filter options `Pending`, `Active`, `Unsubscribed`, `Bounced`, and `Complained`
- filtering to `/cp/newsletter/subscribers?status=pending` keeps `Pending` selected and narrows the table to the pending subscriber row
- `statamic.cp.newsletter.subscribers.edit` at `/cp/newsletter/subscribers/145/edit` loads with `Pending` selected, saves successfully, redirects to the subscriber detail page, and still shows `Status = Pending` after reload
- `statamic.cp.newsletter.subscribers.export` at `/cp/newsletter/subscribers/export/csv?status=pending` returns `200 text/csv` with the pending subscriber present in the CSV payload

Verification prerequisite discovered during the same pass:

- the local authenticated browser initially hit `500 SQLSTATE[42S22]` on CP subscriber routes because the branch-expected archive and pending-status migrations had not yet been run against the local `mailserver` database
- after `php artisan migrate --force`, the CP subscriber routes reflected the current `version/2` pending lifecycle shape and the authenticated verification succeeded

Current limitation:

- the newsletter widget could not be visually confirmed in the authenticated browser because the active dashboard surface did not mount it
- the dashboard currently showed only default Statamic starter cards and `config/statamic/cp.php` defines no default widgets
- treat widget visual verification as still pending for a later authenticated dashboard session, while prior focused tests remain the strongest evidence for widget pending-vs-active separation

## Session 35 Newsletter Dashboard Widget Mount And Visual Verification Notes

This session closes the remaining dashboard-widget verification gap with the narrowest safe change: mount the existing newsletter widget through Statamic CP configuration and verify it through the authenticated CP dashboard route.

Implemented boundary:

- `config/statamic/cp.php` now mounts the existing `newsletter` dashboard widget by default
- the change is configuration-scoped only; no widget data semantics or subscriber lifecycle rules changed
- the widget remains backed by the existing `NewsletterWidget` counts and active-only audience rules from Sessions 32 to 34

Verified:

- the Statamic dashboard falls back to `config('statamic.cp.widgets')` when the authenticated operator has no explicit widget preference
- the existing local `admin@mailserver.test` Statamic user has no stored widget preference overriding the config fallback
- an authenticated CP dashboard request now resolves one mounted widget on the `Dashboard` Inertia page
- the mounted widget HTML exposes the `Newsletter` header and separate `pending`, `active`, `unsubscribed`, `bounced`, and `complained` subscriber summaries

Verification:

- focused feature coverage added in [tests/Feature/NewsletterDashboardWidgetTest.php](/Users/dataphytefoundation/Herd/mailserver/tests/Feature/NewsletterDashboardWidgetTest.php)
- the test authenticates the existing local Statamic super user and verifies the real CP dashboard route mounts the widget and renders separate pending-state counts

Rule preserved:

- no resend, expiry, ageing, unarchive, cleanup, or backfill behaviour was introduced
- no CP authentication or authorization rule was weakened
- active-only send eligibility remains unchanged

Coordinator UI correction after authenticated dashboard review:

- the newsletter CP navigation now lists `Campaigns`, `Analytics`, `Subscribers`, and `Groups` directly under the `Newsletter` section
- the previous parent `Newsletter` item with nested children was removed because it made those primary work areas appear as a submenu instead of first-level Newsletter section items
- route names and controller endpoints remain unchanged; this is navigation presentation only

Coordinator CP styling correction after authenticated subscriber-list review:

- the subscriber CP index now uses newsletter-scoped CSS classes instead of raw Tailwind-style utility classes because those utilities were not reliably rendered inside the Statamic CP shell
- the subscriber table remains intentionally wider than the content area, but horizontal overflow is contained inside the table card through the local table wrapper
- the email column remains sticky so operators can horizontally scroll delivery, engagement, and lifecycle columns without losing row identity
- no subscriber lifecycle, status filtering, import, export, deletion, or active-only sending rule changed as part of this visual correction

## Session 36 Pending Resend, Ageing, And Expiry Notes

This session implements the approved pending subscriber resend and expiry baseline without changing the accepted delivery-confirmed activation contract.

Implemented boundary:

- pending subscriber lifecycle persistence now stores:
  - operator resend count
  - last operator resend timestamp
  - pending expiry timestamp
  - lifecycle audit state
- public signup and resubscribe still create `pending` subscribers, but now initialise a 7 day expiry window and an explicit lifecycle audit state
- operator resend is exposed only through the CP subscriber detail surface and runs only when the subscriber is:
  - still `pending`
  - not expired
  - below the 3 resend limit
  - outside the 15 minute cooldown
  - still tied to a resolvable newsletter form that allows confirmation email
- `ProcessWebhookJob` still treats delivery, open, or click of a correlated `subscription_confirmation` lifecycle email as the only pending-to-active path, but now refuses that promotion once the pending record has expired
- expired pending subscribers remain stored with `status = pending`, an `expired_pending` lifecycle audit state, and the existing active-only audience exclusion unchanged
- CP subscriber index, show, and edit surfaces now expose pending ageing, expiry, resend counters, resend availability, and the operator-facing note that activation still requires a delivered, opened, or clicked confirmation webhook from the signup lifecycle email
- CP edit no longer allows a pending subscriber to be manually saved as `active`

Rule preserved:

- queued mail, resend actions, preference edits, CP saves, CSV import, and ageing alone do not activate pending subscribers
- delivery-confirmed activation remains the only allowed pending-to-active path, and only before expiry
- subscriber identity records are not deleted
- expired pending subscribers are still excluded from campaign audiences because send eligibility remains active-only
- no unarchive, historical cleanup, ownership backfill, broad audience read cutover, or provider-semantic change was introduced
- Session 35 dashboard widget files were not modified by this lifecycle implementation

Verification:

- syntax checks passed for the new lifecycle service, subscriber CP controller, webhook job, subscription form service, provider route registration, and the focused lifecycle tests
- focused serial PHPUnit passed under a local SQLite fallback because sandboxed MySQL access was unavailable:
  - `DB_CONNECTION=sqlite DB_DATABASE=/private/tmp/mailserver-test.sqlite ./vendor/bin/phpunit tests/Feature/SubscriptionFormControllerTest.php tests/Feature/ProcessWebhookJobTest.php tests/Feature/WebhookControllerTest.php tests/Feature/SubscriberArchiveAssignmentTest.php tests/Feature/PendingSubscriberLifecycleTest.php tests/Unit/AudienceResolverTest.php`
  - result: `OK (66 tests, 183 assertions)`
- the focused coverage now explicitly proves:
  - pending signup initialises resend/expiry audit fields
  - resubscribe resets the pending lifecycle baseline without activating the subscriber
  - eligible operator resend queues the original confirmation mail and updates resend audit fields
  - cooldown and resend-limit enforcement block additional operator resends server-side
  - expired pending subscribers remain pending and do not activate on a late confirmation delivery webhook
  - CP update rejects pending-to-active manual promotion
  - expired pending subscribers remain excluded from group audience resolution

Verification limitation:

- the configured shared MySQL test database at `127.0.0.1:3306` was unreachable from this sandbox, so the executed proof uses a local SQLite database file instead
- no browser-authenticated CP pass was run for the touched resend and expiry surfaces in this session

## Session 38 Historical Cleanup Dry-Run Notes

This session does not implement cleanup or backfill. It adds a read-only audit path so newsletter cleanup can be planned against current data instead of assumptions.

Verified by dry-run on Tuesday, August 18, 2026:

- `subscriber_groups` has `3` rows and all `3` are still unowned
- `subscriber_sub_groups` has `9` rows and all `9` inherit from unowned parent groups
- `campaigns` has `16` rows and all `16` are still unowned
- `campaign_audiences` has `15` rows, including `4` historical rows pointing to missing subgroup target `id = 1`
- `subscribers` has `85` rows, including active subscriber `john@example.com` with no membership rows
- all current newsletter forms still resolve to live audience groups, but every form currently reports `missing_product_mapping_source` because there are no relational product rows yet
- the dry-run report at [docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json](/Users/dataphytefoundation/Herd/mailserver/docs/artifacts/session-38-historical-ownership-audit-2026-08-18.json) confirmed unchanged before/after database fingerprints

Rule preserved:

- no historical audience rows, subscriber memberships, product ownership values, or archive state were changed
- no orphaned campaign-audience row was deleted, recreated, or silently redirected
- no subscriber was remapped to a guessed audience

Blocked follow-up:

- future cleanup remains blocked until the canonical relational organisation/product ownership source is populated and approved
- orphaned audience rows and membershipless subscribers still need separate approved remediation rules after ownership backfill is available
