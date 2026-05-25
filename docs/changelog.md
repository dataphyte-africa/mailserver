# Changelog

---

## Session 17 - 2026-05-24 (Data Dive documentation and implemented-product spec)

### Documentation updates

- Added [docs/families/insight/products/data-dive.md](/Users/dataphytefoundation/Herd/mailserver/docs/families/insight/products/data-dive.md) to document:
  - the Data Dive editorial philosophy
  - the active blueprint structure
  - the role of `content`
  - the repeatable `data_points` findings block
  - the `accountability_question` field
  - the repeatable `table_of_contents_items` field
  - the uniqueness of the standalone Data Dive template and render behavior

### Implementation context captured

- Recorded that Data Dive currently has:
  - a standalone email template
  - the collection header band suppressed in favor of the uploaded hero image
  - a stat-first analytical section hierarchy
  - a structured `Key Findings` block
  - an explicit `The Accountability Question` hook before the CTA
  - a CTA followed by article title and author
  - stored RSS 2 / RSS 3 supporting rails
  - a body-content flow designed for click-through rather than full in-email report delivery

## Session 16 - 2026-05-23 (SenorRita documentation and implemented-product spec)

### Documentation updates

- Added [docs/families/insight/products/senorrita.md](/Users/dataphytefoundation/Herd/mailserver/docs/families/insight/products/senorrita.md) to document:
  - the SenorRita editorial philosophy
  - the active blueprint structure
  - the role of `content`
  - the repeatable `table_of_contents_items` field
  - the evidence-first `highlight_stat` / `highlight_stat_label` block
  - the current `insight_block_items` repeater behavior
  - the uniqueness of the standalone SenorRita template and render behavior

### Documentation corrections

- Aligned the SenorRita product record with the current implementation by documenting that:
  - `insight_block_title` is no longer part of the blueprint
  - the Insight Block now consists only of repeatable items with:
    - `title`
    - `description`

## Session 15 - 2026-05-23 (SenorRita blueprint refinement and sample entry seeding)

### Implementation completed

- Updated `collections.insight_newsletters.senorrita` to add the product-specific editorial structure:
  - `title`
  - `highlight_stat`
  - `highlight_stat_label`
  - `insight_block_items`
  - `table_of_contents_items`
- Kept the existing RSS 1 / RSS 2 / RSS 3 structure intact.
- Imported the updated SenorRita blueprint into the local DB with the file-specific YAML import command.
- Seeded two local SenorRita entries for template planning and preview work:
  - `fertility-in-the-40s-the-trend-and-the-threat`
  - `nigerian-women-bear-the-climate-change-burden`

## Session 14 - 2026-05-23 (Pocket Science documentation and implemented-product spec)

### Documentation updates

- Added [docs/families/insight/products/pocket-science.md](/Users/dataphytefoundation/Herd/mailserver/docs/families/insight/products/pocket-science.md) to document:
  - the Pocket Science editorial philosophy
  - the active blueprint structure
  - the editorial role of `content`
  - the repeatable `table_of_contents_items` field
  - the `Pocket Intelligence` field group and its current implementation
  - the uniqueness of the standalone Pocket Science template and render behavior

### Implementation context captured

- Recorded that Pocket Science currently has:
  - a standalone email template
  - the collection header band suppressed in favor of the uploaded hero image
  - a lead-story-driven CTA/title/author structure
  - a dedicated Pocket Intelligence recommendation block
  - stored RSS 2 / RSS 3 story lists
  - a body-content flow designed for click-through rather than full in-email article delivery

## Session 13 - 2026-05-22 (Marina & Maitama template section hierarchy and parser fix)

### Documentation updates

- Updated [docs/families/insight/products/marina-maitama.md](/Users/dataphytefoundation/Herd/mailserver/docs/families/insight/products/marina-maitama.md) to reflect the implemented presentation hierarchy:
  - `What the Data Says` for the highlight-stat section
  - `The Dual Business & Policy Sneak Peek` for the combined Marina/Maitama section
  - distinct editorial treatment for the Marina and Maitama sub-sections

### Implementation completed

- Fixed `NewsletterMailable::extractDualPerspectiveSections()` so headings that start with `Marina` or `Maitama` are recognized as section boundaries, instead of requiring the heading text to be exactly those single words.
- Updated `emails.insight.marina-maitama` so:
  - the stat block now has the section label `What the Data Says`
  - the dual-lens block now has the parent section label `The Dual Business & Policy Sneak Peek`
  - the Marina and Maitama treatments use stronger editorial styling with distinct tinted panels
  - vertical spacing between the stat block and dual-lens block is more clearly separated

## Session 12 - 2026-05-21 (Marina & Maitama blueprint definition and editorial handoff)

### Documentation updates

- Added [docs/families/insight/products/marina-maitama.md](/Users/dataphytefoundation/Herd/mailserver/docs/families/insight/products/marina-maitama.md) to define:
  - the `Marina & Maitama` editorial philosophy
  - the streamlined blueprint field structure
  - the intended use of the `content` field with `h5` markers for `Marina` and `Maitama`
  - highlight-stat usage
  - the seeded sample editorial pattern for template work

### Implementation completed

- Streamlined the `marina_maitama` blueprint to rely on:
  - `content`
  - `highlight_stat`
  - `highlight_stat_label`
  instead of separate structured fields for `marina_heading`, `marina_summary`, `maitama_heading`, and `maitama_summary`.
- Enabled `h5` in the `marina_maitama` Bard toolbar and documented its intended use for the two editorial lenses.
- Added the derived read-only `Belongs To` field across all four Insight product blueprints.
- Seeded the local `sample-marina-maitama` entry with:
  - compact issue intro content
  - `Marina` and `Maitama` `h5` sections
  - `highlight_stat = 85%`
  - a matching explanatory stat label

## Session 11 - 2026-05-20 (Insight collection conversion to product-based newsletter model)

### Documentation updates

- Recorded the Dataphyte Insight collection conversion from the older generic blueprint set to the new product-based model:
  - `Pocket Science`
  - `SenorRita`
  - `Marina and Maitama`
  - `Data Dive`

### Implementation completed

- Added `InsightProvisioner` to create/update:
  - the `insight_newsletters` collection
  - four collection blueprints
  - the `Insight Subscribers` parent group
  - the `insight_subscribe` form blueprint
  - the `insight_subscribe` form configuration
- Added `newsletter:provision-insight`.
- Replaced the old scaffolded `insight_newsletters` blueprint map in `ScaffoldCollections` with the four requested product blueprints.
- Added the `insight_subscribe` form preference options:
  - `Pocket Science`
  - `SenorRita`
  - `Marina and Maitama`
  - `Data Dive`
- Updated seeded Insight sub-groups to match the same four products.
- Extended `NewsletterMailable` so Insight entries can supply:
  - primary curated RSS content via `rss_feed_url` / `rss_items`
  - related feed content via `related_rss_feed_url`
  - recommended feed content via `recommended_rss_feed_url`
- Added four Insight template entrypoints:
  - `emails.insight.pocket-science`
  - `emails.insight.senorrita`
  - `emails.insight.marina-maitama`
  - `emails.insight.data-dive`
- Removed the old generic Insight blueprint/template model from runtime code:
  - `feature_lead`
  - `single_story`
  - `digest`
  - `roundup`
  - `breaking_news`
  - `data_story`
- Exported the current DB-backed Insight blueprints and form definitions to YAML replication artifacts:
  - `resources/blueprints/collections/insight_newsletters/*.yaml`
  - `resources/blueprints/forms/insight_subscribe.yaml`
  - `resources/forms/insight_subscribe.yaml`
- Added the missing fourth local Insight sample entry so there is now one entry per new blueprint handle.
- Added `newsletter:import-yaml-files` so specific blueprint/form YAML files can be imported into the DB without running the generic global Statamic import commands.
- Switched Insight RSS story lists from Grid to Replicator so story cards can collapse in the CP while still supporting reordering.
- Extended Insight feed syncing so RSS 2 and RSS 3 also populate stored story lists in the entry edit form instead of only loading at email render time.
- Applied the same collapsible Replicator story-card pattern to the Policy Point collection blueprint and to the generic scaffolded RSS story list definition so future collections inherit the same behavior.

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
