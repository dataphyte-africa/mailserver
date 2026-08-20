# Analytics And Reporting Contract

## Objective

Define the internal analytics model and reporting contract for Mailserver `version/2` without pretending unresolved domain-management authority, recovery policy, or physical reporting-schema details are already settled implementation facts.

## Current Rule

- production reporting read path: `database`
- `ClickHouse` is out of scope for this session and remains a separate future track only
- `GA4` is complementary only for behavioural and acquisition analytics

## Contract Boundaries

The analytics contract is responsible for:

- canonical operational analytics events
- reporting read models
- dashboard and export query contracts
- newsletter, subscriber, and submission metrics

The analytics contract is not responsible for:

- permission truth
- workflow authorization
- domain-management authority
- organisation or product ownership enforcement

Those remain governed by shared platform foundations and application policies.

## Core Interfaces

### AnalyticsReaderInterface

Purpose:

- provide stable read methods for dashboards, exports, and operational reporting

Recommended responsibilities:

- campaign performance summaries
- delivery, open, click, bounce, complaint, and unsubscribe aggregates
- subscriber lifecycle and growth summaries
- audience size and subgroup summaries
- form and submission workflow summaries
- daily, weekly, or monthly rollups by product

Recommended query families:

- campaign summary by campaign id
- campaign performance by product and date range
- subscriber summary by product and status
- audience segment summary by product
- submission summary by form, mode, status, and date range
- product overview dashboard summary

### AnalyticsWriterInterface

Purpose:

- write canonical analytics events or project operational changes into reporting models

Recommended responsibilities:

- project newsletter delivery events into reporting state
- project subscriber lifecycle events into reporting state
- project audience membership changes into reporting state
- project submission lifecycle changes into reporting state
- update summary tables or equivalent read models

Writer rule:

- writers may consume canonical operational events or application state changes
- writers must not redefine business semantics differently from the source-of-truth docs

### AnalyticsEventStoreInterface

Purpose:

- persist canonical analytics events when an explicit event store is needed

Status for `version/2`:

- optional
- not required to begin implementation if reporting-table projection from application events is sufficient

Use this interface only if event persistence becomes necessary for:

- replay
- projection rebuilds
- audit-grade analytics event history

## Canonical Event Families

The analytics contract should recognise these event families.

### Newsletter Events

- campaign created
- campaign moved to review
- campaign approved
- campaign scheduled
- campaign send started
- campaign send completed
- campaign send partial
- recipient queued
- recipient sent
- recipient delivered
- recipient opened
- recipient clicked
- recipient bounced
- recipient complained
- recipient unsubscribed-from-send context

### Subscriber Events

- subscriber created
- subscriber moved to pending
- subscriber activated
- subscriber unsubscribed
- subscriber bounced
- subscriber complained
- subscriber reactivated
- subgroup attached
- subgroup removed

### Submission Events

- submission received
- submission moved to under_review
- submission moved to changes_requested
- submission shortlisted
- submission approved
- submission rejected
- submission withdrawn
- submission closed

## Canonical Metric Families

### Newsletter Metrics

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

### Subscriber And Audience Metrics

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

### Form And Submission Metrics

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

## Reporting Read Models

The analytics contract should support read-optimised reporting tables or equivalent read models for:

- `campaign_performance_daily`
- `campaign_recipient_status_summary`
- `subscriber_lifecycle_summary`
- `audience_membership_summary`
- `submission_workflow_summary`
- `product_analytics_daily`

These names are conceptual and may change during implementation.

Rule:

- keep conceptual reporting-model responsibilities stable
- defer final table naming and exact schema details until persistence-sensitive implementation is approved

## Dashboard And Export Expectations

The reader contract should support, at minimum:

- coordinator or admin operational overview
- product performance overview
- campaign detail reporting
- audience growth and suppression reporting
- submission queue and decision reporting
- export-safe aggregate reporting by date range and product

## GA4 Boundary

Use `GA4` only for:

- public landing-page traffic
- signup funnel performance
- form-page traffic and conversion
- website acquisition attribution
- campaign traffic to website destinations

Do not use `GA4` for:

- subscriber truth
- campaign send reconciliation
- submission workflow truth
- queue and webhook operational truth
- audit-grade export or compliance reporting

## Non-Settled Dependencies

This session does not settle:

- the exact domain-management authority model
- the exact complained-subscriber recovery policy
- the exact physical schema for reporting tables

These dependencies must be preserved as unresolved unless the coordinator settles them elsewhere.

## Implementation Readiness

This contract is documentation-ready for:

- implementation planning
- non-breaking interface scaffolding
- reporting-table design preparation

It is not yet fully implementation-ready for:

- final persistence schema decisions
- permission-sensitive analytics exposure
- scope-sensitive multi-organisation query enforcement

Those remain dependent on coordinator-approved feature persistence work and the still-open domain-authority and recovery-policy decisions.
