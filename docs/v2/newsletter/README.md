# Newsletter, subscribers, and campaigns

## Editorial and campaigns

- Create Newsletter selects a product and its blueprint in the owning collection. Product-specific templates and the collection entry supply the email content; product branding can fall back to organisation branding, with an entry-level image override.
- Campaigns link the editorial entry, template, sender configuration, and an audience. The code supports draft, review, changes requested, approval, scheduling, sending, sent/partial/failed, retry, cancellation, test send, and preview. A campaign is not considered sent merely because its entry was published.
- Sending fans out through `DispatchCampaignJob` and per-recipient `SendNewsletterEmailJob`. Scheduled dispatch, finalisation, and provider status sync are registered in `routes/console.php`. Worker and scheduler deployment are separate prerequisites.
- Public browser view is a recipient-facing render, distinct from the CP editor preview. Product archive listings expose campaigns with sent or partial status and a send time, not draft or merely published entries.

## Audience and consent

- Product-public groups belong to their products; organisation-private groups are a separate path. Preference membership and engagement rating are different subgroup purposes.
- A valid new signup becomes `active` immediately in each selected eligible product audience. With no campaign history, its rating is `New / Unrated`; subsequent engagement may move it to High, Moderate, Low, or Inactive. Each audience has one current rating for a subscriber.
- A subscriber can have memberships in more than one audience, but not multiple active rating buckets within one audience. An explicit resubscribe may restore an unsubscribed address; ordinary public enrollment rejects erased, bounced, or complained addresses and checks organisation-wide opt-out.
- Preferences remove or add selected public product memberships. Organisation-wide opt-out also blocks that organisation's private-audience mail; an unrelated organisation is unaffected. Unsubscribe and suppression rating changes are immediate, not dependent on the periodic recalculation.
- Editors cannot target `Unsubscribed` or `Suppressed` rating subgroups. `AudienceResolver` also excludes blocked ratings, non-active subscribers, and organisation opt-outs when resolving a whole group or specific subgroup. This backend check is the safety boundary, not the CP checkbox alone.
- The old subscriber-wide status persists, including `pending` for legacy records. It must not be used to describe new signup as waiting for email delivery.

## Engagement recalculation

`GroupEngagementService` scores recent sends and maintains seven rating subgroups: New / Unrated, High, Moderate, Low, Inactive, Unsubscribed, and Suppressed. The CP manual action dispatches `RecomputeOrganisationEngagementJob` for **one selected organisation**. The daily dispatcher queues organisations due for recalculation; the job processes each group in subscriber chunks of 250. The configured queue driver determines whether dispatch is asynchronous (`database`/`redis` with a worker) or request-bound (`sync`). The job currently uses the default queue, not the named newsletter send queues.

## Source and tests

Primary code: `app/Services/Newsletter/SubscriberEnrollmentService.php`, `GroupEngagementService.php`, `AudienceResolver.php`, `CampaignWorkflowService.php`, `NewsletterPublicUrlService.php`, `app/Jobs/Newsletter/`, and `app/Http/Controllers/CP/Newsletter/`. Focused tests include `ProductFormPlatformTest`, `GroupEngagementServiceTest`, `NewsletterEngagementRatingsTest`, `NewsletterCampaignLifecycleTest`, `PrivateNewsletterAudienceTest`, and `NewsletterArchiveTest` under `tests/Feature/`.
