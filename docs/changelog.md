# Changelog

---

## Session 10 — 2026-05-20 (Subscriber rating phase 1)

### Documentation updates

- Updated [docs/subscriber-rating.md](/Users/dataphytefoundation/Herd/mailserver/docs/subscriber-rating.md) to reflect the implemented first phase:
  - persisted engagement fields
  - recompute command
  - current thresholds
  - CP and export visibility
- Updated [docs/subscriber-management.md](/Users/dataphytefoundation/Herd/mailserver/docs/subscriber-management.md) to document:
  - subscriber list `Rating` column
  - subscriber detail rating/score fields
  - subscriber export engagement fields

### Implementation completed

- Added persisted subscriber engagement fields:
  - `engagement_score`
  - `engagement_rating`
  - `last_engaged_at`
- Added `SubscriberEngagementService` to compute first-pass engagement state from the last `10` sends.
- Added recompute command:
  - `php artisan newsletter:recompute-subscriber-engagement`
- Wired automatic engagement recompute into:
  - subscriber create/update
  - CSV import
  - webhook / sync send-state updates
- Added `Rating` to the subscriber list and made it sortable.
- Added engagement score/rating visibility to the subscriber detail page.
- Added engagement fields to:
  - subscriber CSV export
  - subscriber GDPR/detail export
- Updated subscriber CSV export to preserve current filters and sort while including engagement totals.
- Fixed PHP 8.5 `fputcsv()` deprecation warnings in CSV export controllers by passing the escape parameter explicitly.

## Session 8 — 2026-05-19 (Subscriber rating feature review)

### Documentation updates

- Added [docs/subscriber-rating.md](/Users/dataphytefoundation/Herd/mailserver/docs/subscriber-rating.md) as a review-ready feature document covering:
  - why subscriber rating is needed
  - recommended scoring model
  - rating buckets
  - suggested weights and recency rules
  - phased rollout guidance
  - CP and export implications

### Architectural direction

- Subscriber rating should be built on top of existing `campaign_sends` history.
- The first release should use:
  - an internal `engagement_score`
  - a visible `engagement_rating`
  - a `last_engaged_at` timestamp
- Suppression states (`unsubscribed`, `bounced`, `complained`) should override rating and resolve to `suppressed`.

## Session 9 — 2026-05-20 (Subscriber CP engagement views)

### Documentation updates

- Updated [docs/subscriber-management.md](/Users/dataphytefoundation/Herd/mailserver/docs/subscriber-management.md) to document:
  - subscriber list historical engagement columns
  - sticky first-column + horizontal-scroll behavior
  - subscriber detail engagement sections
  - campaign history pagination
  - current rating placement decision

### Implementation completed

- Added lifetime engagement columns to the subscriber list:
  - campaigns
  - delivered
  - failed
  - opened
  - clicked
- Added sortable subscriber-list metric headers.
- Added sticky first-column behavior to the subscriber list.
- Expanded subscriber detail to include:
  - lifetime engagement totals
  - links clicked total
  - last engaged timestamp
  - paginated campaign history
  - recent clicked links
- Improved subscriber detail card/table styling for better section separation.

## Session 7 — 2026-05-18/19 (Campaign exports, analytics exports, sync cleanup)

### Documentation updates

- Updated [docs/analytics.md](/Users/dataphytefoundation/Herd/mailserver/docs/analytics.md) to reflect the current production operator model:
  - manual-refresh analytics sync workflow
  - analytics export options
  - `Opens Over Time` and `Opens by Hour of Day` semantics
  - top-links caveat and future reliability requirements
  - stale failure-field cleanup on successful reconciliation
  - Elastic Email `/view` fallback behavior

### Implementation completed

- Added campaign sends CSV export from the campaign detail page, preserving current sort order.
- Added analytics CSV exports:
  - summary
  - top links
  - open timing
  - failed/bounced recipients
- Added a local demo campaign seeder command for export and analytics verification:
  - `php artisan newsletter:seed-demo-campaign --fresh`
- Fixed stale `bounce_reason` / `failed_at` / `bounced_at` values lingering on successful sends.
- Restricted synthetic sync `BounceError` payloads to real failure states only.
- Hardened stats backfill so Elastic Email `/emails/{msgid}/view` failures now fall back to the events API instead of aborting send reconciliation.
- Performed a one-off production cleanup to remove stale failure metadata from already-successful sends.

## Session 6 — 2026-05-17 (Live analytics sync and chunked reconciliation)

### Documentation updates

- Updated [docs/analytics.md](/Users/dataphytefoundation/Herd/mailserver/docs/analytics.md) to document:
  - manual per-campaign stats reconciliation
  - chunked queue processing
  - campaign-level sync state fields
  - the live analytics polling endpoint

### Implementation completed

- Added campaign sync tracking fields:
  - `last_stats_sync_requested_at`
  - `last_stats_sync_completed_at`
  - `last_stats_sync_status`
  - `last_stats_sync_total`
  - `last_stats_sync_processed`
  - `last_stats_sync_error`
- Added a reusable `CampaignStatsSyncService` to centralize Elastic Email fallback reconciliation.
- Reworked manual stats sync to use a dispatcher + chunk jobs instead of a single long-running job.
- Reduced manual sync chunk size to `100` sends per job for lower production resource usage.
- Added a campaign analytics JSON polling endpoint:
  - `GET /cp/newsletter/analytics/campaign/{campaign}/status`
- Added live in-page polling on the campaign analytics view so:
  - sync status updates without a full page reload
  - progress bar and percentage update while the sync runs
  - KPI cards and status breakdown refresh in place
- Refined the analytics page sync UX so:
  - background polling remains the source of truth
  - visible progress animates in `100`-send windows
  - metric cards and charts repaint at animation checkpoints
  - catch-up mode skips stale visual steps when polling confirms a newer checkpoint
- Hardened fallback event-date serialization so native `DateTime` values no longer break sync jobs.
- Improved analytics page presentation with clearer card borders, visible action buttons, and stronger UI boundaries.

## Session 4 — 2026-05-14 (Subscription form architecture)

### Documentation updates

- Updated subscriber documentation to support public, collection-linked subscribe flows in addition to CSV import.
- Updated collection onboarding docs to include subscription forms and form-derived sub-groups.
- Updated content architecture docs to reflect forms as part of each newsletter operation.
- Updated schema docs to clarify that sub-groups may be provisioned from form preference definitions.
- Added `docs/subscription-forms.md` to define the new declarative form model, remote-styled rendering pattern, and subscriber write pipeline.

### Architectural direction

- Public subscribe forms belong to exactly one newsletter collection.
- Destination websites control form styling; this project supplies schema, validation, and submit endpoints.
- Preference definitions on a form become subscriber sub-groups for that collection's parent group.
- New collections should rely more on conventions and less on scattered hardcoded maps.

### Implementation completed

- Added `policy_point_newsletters` as a first-class newsletter operation, including sender config, scaffold support, seed data, and a base email template.
- Added public subscribe endpoints:
  - `GET /subscribe/{slug}/schema`
  - `POST /subscribe/{slug}`
- Added form-linked subscription processing:
  - create/update subscriber
  - sync selected sub-groups
  - return `subscribed`, `already_subscribed`, `subscription_updated`, or `resubscribed`
- Added subscriber confirmation / resubscribe email flow with form-level controls:
  - `Send Confirmation Email`
  - `Send Update Email`
  - custom subject/body copy
- Moved subscription email branding ownership to the form:
  - `logo_url`
  - `brand_color`
- Added explicit collection linkage on subscriber groups via `collection_handle`.
- Changed forms to select a subscriber group, with the collection derived from that group.
- Added subscribe endpoint browser support:
  - CSRF exclusion for `/subscribe/*`
  - CORS config for `/subscribe/*`
- Fixed select option serialization so Statamic `key/value` select definitions now become correct schema values and labels.
- Added flexible first/last name mapping so both `first_name` / `last_name` and `firstname` / `lastname` forms are accepted.
- Added submission audit metadata written back to saved Statamic form submissions:
  - `subscription_status`
  - `email_sent`
  - `subscriber_id`
  - `subscriber_group_id`
- Added `docs/template-development-flow.md` as a focused handoff guide for future newsletter template development sessions.

---

## Session 5 — 2026-05-15 (Policy Point RSS curation flow)

### Documentation updates

- Updated RSS integration documentation to describe the editor-side curation flow for feed-driven newsletters.
- Updated template handoff documentation to explain how `policy_point` now uses Bard intro content plus a curated RSS story list with a lead article.

### Implementation completed

- Added entry-save RSS curation support so feed items can be stored on the newsletter entry itself.
- Added `refresh_rss_items` and `rss_items` blueprint support to the newsletter scaffold for feed-driven templates.
- Added curated story preparation logic that exposes:
  - `rssItems`
  - `rssLeadItem`
  - `rssSecondaryItems`
- Updated the `policy_point` template to render:
  - Bard intro content first
  - a dedicated lead story block
  - remaining stories in saved order
- Added test coverage for stored curated story ordering and lead selection behavior.

---

## Session 3 — 2026-04-12 (Active testing, bug fixes)

### Critical Bugs Fixed

#### UUID auto-generation on entry insert
- **Problem:** `SQLSTATE HY000: Field 'id' doesn't have a default value` when saving any Statamic entry via CP
- **Root cause:** Statamic Eloquent driver's `entries` table uses `char(36)` UUID primary key with no DEFAULT. `Entry::make()` creates entries without an ID; the vendor `makeModelFromContract()` only sets `id` if `$source->id()` is non-null.
- **Fix:** `app/Models/EntryModel.php` — extends `Statamic\Eloquent\Entries\EntryModel` with Laravel's `HasUuids` trait. Config `statamic/eloquent-driver.php` `entries.model` points to the custom model.
- **Commit:** `64f2b2e`

#### CP controller redirects using wrong route namespace
- **Problem:** `Route [newsletter.campaigns.show] not defined`
- **Root cause:** `Statamic::pushCpRoutes()` registers routes under the `statamic.cp.*` namespace. `redirect()->route('newsletter.campaigns.show')` skips the prefix.
- **Fix:** All CP controller redirects now use `cp_route('newsletter.campaigns.show', $campaign)` which resolves to `statamic.cp.newsletter.campaigns.show`.
- **Files:** `CampaignController.php`, `GdprController.php`
- **Commit:** `15671b0`

#### Campaign `from_email` NOT NULL violation
- **Problem:** `SQLSTATE 23000: Column 'from_email' cannot be null` when sender override fields left blank
- **Fix:** Migration `2026_04_12_000001` makes `from_name` and `from_email` nullable. `blankToNull()` helper in controller converts empty form strings to NULL.
- **Commit:** `7021c5a`

#### Undefined constant "first_name" in Blade
- **Problem:** `{{first_name}}` merge tag written literally in `show.blade.php` hint text → Blade compiled it as `echo e(first_name)` → PHP "Undefined constant"
- **Fix:** Use `@{{first_name}}` in Blade files to output literal braces without evaluation. The merge tags in Bard content are safe (passed as a PHP string to `{!! $content !!}`).
- **Commit:** `22f2f06`

#### `$sentDate` undefined in email templates
- **Problem:** Stale Blade view cache could serve compiled view before `$sentDate` was in the mailable's `with` array
- **Fix:** All 9 email templates now use `{{ $sentDate ?? now()->format('F j, Y') }}` defensive null coalescing. View cache cleared.
- **Commit:** `f1f3a4c`

#### `$preferencesUrl` undefined on direct URL preview
- **Problem:** Statamic CP entry preview renders the blueprint's template directly (without the mailable) → `$preferencesUrl` undefined
- **Fix:** `emails/layout.blade.php` footer now uses `$preferencesUrl ?? '#'` and `$unsubscribeUrl ?? '#'`
- **Commit:** `101a548`

### Features Added

#### Browser email preview
- `GET /cp/newsletter/campaigns/{campaign}/preview` — renders full email HTML in browser with blue banner showing template name. Links disabled (`href="#"`).
- Controller: `CampaignController::preview()`
- Route name: `newsletter.campaigns.preview`

#### Subscriber personalisation
- **Merge tags in Bard body:** `{{first_name}}` `{{last_name}}` `{{full_name}}` `{{email}}` — replaced via `applyMergeTags()` in `NewsletterMailable`
- **Blade template variables:** `$subscriberFirstName`, `$subscriberLastName`, `$subscriberFullName`, `$subscriberEmail`

#### Reset stuck campaign to draft
- `POST /cp/newsletter/campaigns/{campaign}/reset` → `resetToDraft()`
- Available when campaign status is `sending` or `failed`
- Shown as yellow "Stuck?" card on campaign show page

#### Mailtrap dev mail
- `config/mail.php`: new `mailtrap` SMTP mailer reading `MAILTRAP_USERNAME`/`MAILTRAP_PASSWORD` (falls back to `MAIL_USERNAME`/`MAIL_PASSWORD`)
- `QUEUE_CONNECTION=sync` set in `.env` for dev — jobs run immediately, no worker needed

#### Blueprint name in campaign entry picker
- `allEntries()` in `CampaignController` now includes `blueprint` title
- Entry dropdown shows: `[Breaking News] Apr 11, 2026 — Breaking: Testing`

### Alpine.js → Vanilla JS Replacements

Statamic's CP owns the Alpine.js instance — custom `x-data` components inside it don't fire reliably.

| Component | Was | Now |
|---|---|---|
| Campaign create/edit entry selector | `x-for` + `filteredEntries` computed | `populateEntries()` vanilla JS |
| Campaign show test-send dropdown | `x-data="{ open: false }"` + `@click` | `onclick` toggle + document click listener |

Alpine is still used for audience group show/hide and schedule radio (those interact with Statamic's own Alpine scope cleanly).

---

## Session 2 — 2026-04-11/12 (Full system build)

### Architecture Decisions

- **Blueprint = Template selector** — each Statamic blueprint has a hidden `template` field auto-filled on first save. No manual template field needed.
- **Logos:** Collection logos editor-controlled via `newsletter_settings` GlobalSet (cached 1hr). Product nameplates hardcoded per Blade template.
- **Audience:** Selected at campaign level only — entry is pure content.
- **UUID fix:** `App\Models\EntryModel` + `HasUuids` trait.

### Files Created
- `app/Models/EntryModel.php`
- `app/Services/Newsletter/TemplateResolver.php`
- `app/Mail/NewsletterMailable.php` (full)
- `app/Console/Commands/Newsletter/ScaffoldCollections.php` (full rewrite)
- `resources/views/emails/layout.blade.php`
- `resources/views/emails/insight/` (6 templates)
- `resources/views/emails/foundation/` (3 templates)
- `resources/views/landing/index.blade.php`
- All CP campaign views (create, edit, show, index)
- 69 tests across 5 test files — all passing

---

## Session 1 — Initial scaffold

Base Laravel + Statamic installation, DB migrations, models, basic routing.
