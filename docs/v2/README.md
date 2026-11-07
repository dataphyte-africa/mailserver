# Mailserver v2: current implementation

Snapshot: 6 November 2026, `version/2` checkout at `c33b0cf` plus existing uncommitted work. This is a **codebase inventory**, not a production-data or live-worker audit. An item is "implemented" here only when there is a concrete route/service and, where noted, a focused test. Deployment, DNS, provider delivery, and historical-data parity require separate verification.

## Read by area

| Area | Current guide | Status |
| --- | --- | --- |
| Organisation, product, access, domains | [Platform](platform/README.md) | Implemented in application code; live grants and DNS are environment-specific |
| Editorial, campaigns, audiences, delivery | [Newsletter](newsletter/README.md) | Implemented with legacy compatibility and operational caveats |
| Signup and other forms | [Forms](forms/README.md) | Hosted and legacy paths coexist; application forms remain separate |
| Reporting and remaining analytics | [Analytics](analytics/README.md) | CP reports exist; common reader/writer and rollup layer remain incomplete |
| Deployment and verification | [Operations](operations/README.md) | Repository-supported checks, not proof of production configuration |

## Current operating model

- A relational `Organisation` owns `Product` records. A product points to an editorial collection/blueprint and, for public newsletter subscriptions, a product-owned audience. A separate organisation-private audience path exists.
- Statamic provides editorial entries, users, and the Control Panel. Relational scope records and `CpOperatorScope` constrain the custom CP surfaces; non-super operators work in one delegated organisation context at a time.
- A valid subscription is **active immediately**. The selected public audience receives a `New / Unrated` engagement rating when the subscriber has no campaign history. Welcome-email delivery is not an activation gate. Unsubscribed and suppressed ratings are not send targets; the backend checks eligibility again when resolving recipients.
- Campaigns link editorial content, an email template, and an audience. Delivery jobs, provider webhooks, status sync, public browser view, and sent-issue archives are separate surfaces.
- Hosted subscription forms use `/forms/{slug}` plus a distinct `/forms/{slug}/submit` integration endpoint. Legacy `/subscribe/{form}` integrations remain available. Application/data-collection submissions are not newsletter signups.
- Reporting currently reads operational database tables directly in CP controllers and services. The configured `AnalyticsReaderInterface` and `AnalyticsWriterInterface` database implementations still throw `LogicException`; they must not be described as a working shared analytics backend.

## Source and precedence

This folder records the implementation state observed in this checkout. Older specifications and backlog notes have been moved to a local, ignored archive; they are not part of the versioned v2 contract or proof of current runtime behavior. For production readiness, verify the deployed commit, migrations, environment, workers, provider credentials, and representative data on the target host. Do not run `migrate:fresh` on this project.
