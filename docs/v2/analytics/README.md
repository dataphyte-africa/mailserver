# Analytics: current state and remaining work

Snapshot: 6 November 2026. This is a repository audit, not a reconciliation against production provider records. "Available" below means a code path exists; it does not certify every metric against a live dataset.

## Available today

| Surface | Current source | What it provides |
| --- | --- | --- |
| Newsletter CP overview | `AnalyticsController::index` | Period-filtered campaign totals, delivery/open/click rates, daily send volume, and recent campaigns; super admins also see webhook health |
| Campaign detail and exports | `AnalyticsController::campaign` and export actions | Recipient status breakdown, open timing, top links, failures, summary CSV, and a queued provider stats sync with progress/status endpoint |
| Dashboard widget | `NewsletterWidget` | Recent newsletter volume and delivery/engagement summary from operational tables |
| Audience growth | `AudienceGrowthService` | Per-group or organisation unique joined total, current eligible membership total, and 12 monthly new/total-joined points; current excludes opt-outs and non-active subscribers |
| Ratings | `GroupEngagementService` and Engagement Ratings CP | Per-audience seven-bucket breakdown, manual organisation job, and scheduled recalculation |
| Form submissions | `ProductFormSubmissionController` and form services | Scoped submission lists and CSV export; application status transitions are separate from subscriptions |
| Delivery evidence | `CampaignSend`, `CampaignLinkClick`, `WebhookLog`, provider sync jobs | Operational send/event records that current reports query directly |

The CP analytics overview is **collection-filtered**, not a complete product-attributed reporting API. The monthly audience-growth series is a *join* series; `current` is a separate present-time eligible count, not a historical monthly net total. A person in multiple groups is deduplicated for organisation totals. Do not rename joined totals as net growth or infer historical unsubscribe-adjusted balances from this series.

## Not yet complete

1. **Implement the shared analytics contracts.** `AnalyticsReaderInterface` and `AnalyticsWriterInterface` are bound to database classes, but every database method currently throws `LogicException`. CP controllers bypass these interfaces with direct SQL/Eloquent queries. Build real scoped read/write implementations or explicitly retire the unused abstraction; do not call the current bindings a finished analytics layer.
2. **Define and test metric truth.** Document the grain, denominator, date source, timezone, status precedence, and attribution for each campaign and audience metric. Reconcile queued/skipped/failed/sent/delivered/opened/clicked states, unique recipients versus events, and partial campaigns against raw send records and provider samples. In particular, current dashboard and campaign-detail queries use different aggregation paths; parity needs tests rather than an assumed match.
3. **Complete product- and organisation-level reporting.** Add scoped date-range reports for campaign performance, subscriber joins/current membership/opt-outs, audience rating history where retained, and form conversion/workflow. Today the CP overview's collection filter and direct queries are not a reusable per-product reporting contract. Keep private-audience attribution explicit.
4. **Build reproducible reporting reads.** The conceptual daily rollups in the older analytics contract (`campaign_performance_daily`, `subscriber_lifecycle_summary`, `audience_membership_summary`, `submission_workflow_summary`, `product_analytics_daily`) are not implemented as reporting tables. Decide whether direct indexed queries suffice at current volume, or introduce rollups with backfill, idempotent projection, replay/rebuild, and parity checks. Do not create tables only to match proposed names.
5. **Close form-funnel measurement.** Submission lists and exports exist, but hosted-page views, starts, successful submissions, consent selections, and conversion denominators are not one consolidated analytics model. Application workflow summaries also need explicit mode-specific definitions; subscription intake has no review status.
6. **Add verification and operating signals.** Cover multi-organisation isolation, filters, boundary dates, duplicate webhooks, late provider events, recipient deduplication, unsubscribed/suppressed exclusions, and export/onscreen parity. Define freshness, sync lag, backlog, failed-job, and reconciliation indicators before using the reports for operational decisions.

## Priority and acceptance

| Priority | Next deliverable | Acceptance evidence |
| --- | --- | --- |
| P0 | Metric dictionary and SQL/query parity audit | Representative campaigns and audiences reconcile to raw records, including partial and skipped sends; date/timezone rules written down |
| P0 | Scoped product/organisation report contract | Super/admin/operator results cannot cross delegated organisation or product boundaries; focused authorization tests pass |
| P1 | Database analytics reader/writer decision and implementation | Public methods return tested results instead of throwing, or unused contracts are removed deliberately |
| P1 | Form and audience trend reporting | Join/current/opt-out measures remain distinct; conversion denominators are observable and tested |
| P2 | Rollups or alternative read optimization | Measured query need, backfill/rebuild procedure, and parity tests before switching CP reads |
| Deferred | GA4 or alternate analytics store | GA4 may supplement acquisition/funnel behavior, never replace database operational truth; ClickHouse is not a current production requirement |

## Code and prior contract

Implementation: `app/Http/Controllers/CP/Newsletter/AnalyticsController.php`, `app/Widgets/NewsletterWidget.php`, `app/Services/Newsletter/AudienceGrowthService.php`, `app/Services/Newsletter/GroupEngagementService.php`, `app/Support/Platform/Analytics/`, and `app/Contracts/Analytics/`. Existing coverage includes `tests/Feature/AnalyticsCampaignPaginationTest.php`, `AudienceGrowthTest.php`, `NewsletterEngagementRatingsTest.php`, and `tests/Unit/PlatformContractsTest.php`. Older analytics design notes are archived locally and are not proof that their proposed read models exist; their pending-to-active conversion metric is superseded for new signup by immediate activation.
