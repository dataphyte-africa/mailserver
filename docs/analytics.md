# Analytics & Metrics

---

## Cloudways Elastic Email Addon (SMTP Only — No Dashboard)

The Cloudways addon configures SMTP credentials only. There is no dashboard.
Elastic Email still tracks all emails sent through their SMTP server — you access that
tracking by setting up webhooks directly in your Elastic Email account (not Cloudways).

### One-time setup in elasticemail.com
Settings > Notifications > Add notification:
- URL: `https://yourdomain.com/webhooks/elastic-email`
- Events to enable: Sent, Delivered, Opened, Clicked, Bounced, Unsubscribed, Complained

All events are stored in the local `campaign_sends` table. Every metric displayed in
the CP is computed from that table — no external dashboard needed.

---

## Three Analytics Views to Build

### 1. Per-Campaign Summary

Shown on each campaign's detail page in the Statamic CP.

```
Campaign: April Insight Newsletter
Sent:         1,240   (all campaign_sends for this campaign)
Delivered:    1,198   (status: delivered | opened | clicked)
Failed:          42   (status: failed | bounced)
Opened:         542   (opened_at IS NOT NULL)
Unread:         656   (delivered but opened_at IS NULL)
```

**SQL:**
```sql
SELECT
    COUNT(*)                                                      AS total_sent,
    SUM(status IN ('delivered','opened','clicked'))               AS total_delivered,
    SUM(status IN ('failed','bounced'))                           AS total_failed,
    SUM(opened_at IS NOT NULL)                                    AS total_opened,
    SUM(status IN ('delivered','opened','clicked')
        AND opened_at IS NULL)                                    AS total_unread
FROM campaign_sends
WHERE campaign_id = :campaign_id;
```

---

### 2. Per-Subscriber History

Shown on each subscriber's detail page in the CP.
Answers: how engaged is this subscriber across all campaigns sent to them?

```
Subscriber: john@example.com
Total sent to them:   18
Total delivered:      17
Total failed:          1
Total opened:         12
```

**SQL:**
```sql
SELECT
    COUNT(*)                                         AS total_sent,
    SUM(status IN ('delivered','opened','clicked'))  AS total_delivered,
    SUM(status IN ('failed','bounced'))              AS total_failed,
    SUM(opened_at IS NOT NULL)                       AS total_opened
FROM campaign_sends
WHERE subscriber_id = :subscriber_id;
```

The subscriber detail page also lists every campaign they were sent, with individual
status per row (delivered / opened / failed).

---

### 3. Link Clicks → Google Analytics / Website Metrics

Newsletter links already point to Dataphyte Insight or Foundation websites where GA is
installed. GA receives the visit — but cannot tell it came from a newsletter unless
UTM parameters are appended to every link at send time.

**The system auto-appends UTM params when dispatching the campaign:**

```
Original link in newsletter content:
https://dataphyteinsight.com/story/headline-slug

What gets sent in the email:
https://dataphyteinsight.com/story/headline-slug
  ?utm_source=newsletter
  &utm_medium=email
  &utm_campaign=april-insight-edition
  &utm_content=topics
```

GA on the destination site picks this up under:
- Acquisition → Traffic Acquisition → Campaign
- Or Acquisition → Campaigns in GA4

**UTM values populated automatically from the campaign entry:**
| UTM param | Value |
|---|---|
| `utm_source` | `newsletter` |
| `utm_medium` | `email` |
| `utm_campaign` | campaign slug (from Statamic entry) |
| `utm_content` | audience sub-group slug |

No separate click tracking needed on the newsletter system side —
GA on the website handles it. The newsletter system tracks *whether* a link was clicked;
GA tracks *what the subscriber did after*.

---

## Webhook → Database Flow (How Metrics Arrive)

```
Elastic Email SMTP send
    ↓
Elastic Email servers detect: open / click / bounce / delivered
    ↓
POST to https://yourdomain.com/webhooks/elastic-email
    ↓
ProcessWebhookJob (queued, async)
    ↓
Updates campaign_sends row:
    - status
    - delivered_at / opened_at / clicked_at / bounced_at
    ↓
CP queries campaign_sends for display
```

---

## What Is NOT Trackable in Email

| Metric | Reason | Alternative |
|---|---|---|
| Scroll depth | Email clients strip JavaScript | Only on web version (view in browser) via Plausible/GA |
| Time spent reading | Not exposed by email clients | Litmus ($99+/month) |
| Accurate open rate | Apple MPP pre-fetches pixels since 2021 | Use click rate as primary engagement signal |

---

## Computed Rates (for CP display)

| Rate | Formula |
|---|---|
| Delivery rate | delivered / sent × 100 |
| Open rate | opened / delivered × 100 |
| Failure rate | failed / sent × 100 |
| Unread rate | unread / delivered × 100 |

Good benchmarks: delivery > 95%, open rate 20–40%, failure < 2%.

---

## Manual Stats Reconciliation In CP

The analytics campaign page now supports a manual fallback reconciliation flow for
campaigns whose webhook-derived analytics are incomplete or delayed.

### CP actions

On the per-campaign analytics page:
- `Sync Stats Now`
- `Re-queue Stats Sync`

These actions queue a campaign-scoped fallback sync against Elastic Email and do not
re-dispatch campaign emails.

### What gets rechecked

Manual reconciliation only targets sends that are still worth reconciling:
- `sent`
- `pending`
- `delivered`
- `opened`

It intentionally skips already-terminal or fully resolved sends such as:
- `clicked`
- `failed`
- `bounced`
- `complained`

That means a re-sync does **not** rescan the entire campaign blindly. It continues
checking only the sends that may still advance to:
- delivered
- opened
- clicked

### Chunked queue model

Large campaign syncs are split into small queue jobs.

Current production-safe chunk size:
- `100` campaign sends per chunk

Flow:
1. A dispatcher job finds eligible `campaign_sends` rows.
2. It stores the campaign sync totals on the `campaigns` table.
3. It dispatches chunk jobs in batches of `100`.
4. Each chunk queries Elastic Email and writes synthetic `WebhookLog` rows.
5. `ProcessWebhookJob` consumes those rows and updates `campaign_sends`.

This avoids a single long-running sync job timing out on large campaigns.

### Sync state stored on campaigns

The following fields track reconciliation progress:
- `last_stats_sync_requested_at`
- `last_stats_sync_completed_at`
- `last_stats_sync_status`
- `last_stats_sync_total`
- `last_stats_sync_processed`
- `last_stats_sync_error`

`last_stats_sync_status` values:
- `queued`
- `processing`
- `completed`
- `failed`

### Live polling endpoint

The analytics page polls a campaign-scoped JSON endpoint while sync is queued or running:

- `GET /cp/newsletter/analytics/campaign/{campaign}/status`

Returned payload includes:
- sync state
- progress numbers and percentage
- KPI metrics
- status breakdown
- open-distribution chart data

### Current CP behavior

The backend reconciliation remains chunked and campaign-scoped, but the current CP
operator workflow should be treated as refresh-based.

In practice:
- click `Sync Stats Now` or `Re-queue Stats Sync`
- wait for chunk jobs and webhook-processing jobs to run
- refresh the analytics page to see updated metrics and progress

The JSON endpoint remains available for future refinement, but the production-safe
expectation today is manual refresh rather than live in-page animation.

### Important behavior

The live percentage reflects the reconciliation scan progress:

```text
processed / total * 100
```

Metrics do **not** need to wait for `100%` completion before changing. As each chunk
finishes and its synthetic webhook jobs are processed, the analytics page can show
updated metrics on refresh or via polling.

### Re-sync cleanup behavior

Reconciliation now also cleans up stale failure metadata when a send is later confirmed
as successful:
- `bounce_reason` is cleared on `delivered`, `opened`, and `clicked`
- `failed_at` is cleared on `delivered`, `opened`, and `clicked`
- `bounced_at` is cleared on `delivered`, `opened`, and `clicked`

This prevents exported successful rows from continuing to show old provider errors in
the `Bounce Reason` column.

### Elastic Email `/view` fallback

The backfill resolver first attempts:
- `GET /emails/{msgid}/view`

Some Elastic Email message IDs return:
- `400 Bad Request`
- content unavailable / missing view record

That lookup is now treated as recoverable:
- the sync logs the `/view` failure
- then falls back to the events API for the same send
- the send reconciliation continues instead of failing the whole item

### Operational note

If queue workers are down or a chunk fails, the sync can stop in `queued`,
`processing`, or `failed`. In that case:
- inspect failed jobs
- inspect webhook queue health
- re-queue the sync from the campaign analytics page once the issue is resolved

---

## Analytics Exports

The analytics page supports focused CSV exports instead of one mixed file.

Available exports:
- `Export Summary CSV`
- `Export Top Links CSV`
- `Export Open Timing CSV`
- `Export Failed/Bounced CSV`

The campaign detail page separately supports:
- `Export CSV` for the sends table

### Summary export

One row per campaign including:
- campaign name
- collection
- subject
- sent at
- total sent
- delivered
- opened
- clicked
- unread
- bounced
- failed
- complained
- delivery rate
- open rate
- click rate
- click-to-delivery rate

### Top Links export

One row per clicked URL including:
- URL
- total clicks
- unique clicks
- first clicked at
- last clicked at

Important caveat:
- `Top Links` depends on stored `campaign_link_clicks` rows
- a campaign can show click totals while `Top Links` remains empty if no URL-level
  click rows were captured

Reliable future `Top Links` requires:
- Elastic Email click tracking enabled
- click webhook payloads that include the clicked URL (`target`, `link`, or equivalent)
- webhook ingestion storing those URLs in `campaign_link_clicks`

Historical URL-level reconstruction is not guaranteed from the current transaction
status sync endpoint because that endpoint does not reliably expose clicked-link URLs.

### Open Timing export

Contains two sections:
- opens over time
- opens by hour of day

This is useful for editorial timing analysis rather than recipient audit.

### Failed/Bounced export

Contains recipient-level failure rows with:
- subscriber name
- subscriber email
- status
- failure reason
- failure timestamp
- transaction ID

---

## Open Timing Metrics In The CP

### Opens Over Time (first 48 hours)

This metric answers:
- how long after send recipients opened the email

It is **not** grouped by clock time of day.

Current CP presentation:
- summary sentence highlighting the strongest bucket
- `5` grouped buckets:
  - `0–4h`
  - `4–8h`
  - `8–12h`
  - `12–24h`
  - `24–48h`
- shown as a simple two-column list:
  - hour range
  - open count

Use it to understand:
- whether most engagement happens immediately
- whether a campaign keeps attracting opens deep into the first two days
- when a campaign can be judged “mostly done” from an open perspective

### Opens by Hour of Day

This metric answers a different question:
- what clock hour recipients tend to open

It groups all recorded opens by the hour of day when they happened:
- `12am`
- `6am`
- `12pm`
- `6pm`
- etc.

Use it to understand:
- when during the day this audience tends to engage
- whether future send timing should be adjusted toward those open windows
