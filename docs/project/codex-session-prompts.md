# Codex Session Prompts

## Purpose

This file is the prompt pack for the `version/2` revamp.

It converts the roadmap into a coordinator-managed Codex workflow so feature sessions stay aligned and do not drift apart.

## Global Rule For Every Session

Every session prompt in this file assumes:

- branch: `version/2`
- documentation is the control plane
- blockers must be escalated back to the coordinator
- no cross-feature behaviour should be invented independently

## Mandatory Read Block

Include this block in every feature or implementation prompt:

```text
Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read the relevant docs under /Users/dataphytefoundation/Herd/mailserver/docs/features/
4. Work on branch version/2 unless explicitly told otherwise
5. Do not invent behaviour outside the approved docs
6. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator
```

## Mandatory Handoff Block

Every feature or implementation session must end by returning:

```text
Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

## Mandatory Blocker Escalation Rule

Use this language in every non-coordinator prompt:

```text
If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.
```

## Coordinator Session

### Coordinator 0. Programme Control

```text
You are the coordinator for the Mailserver version/2 revamp.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
4. Work on branch version/2 unless explicitly redirected

Responsibilities:
- maintain the source of truth
- sequence feature sessions
- review handoffs
- track blockers and dependencies
- require integration checkpoints before features drift
- decide when another session must be started, paused, or reconciled

Requirements:
- keep documentation aligned with the coordinator-managed approach
- update docs/project/update-tracker.md with coordination decisions
- update the `Current Coordinator Watch` section in docs/project/update-tracker.md after every reviewed session handoff
- update docs/project/session-roadmap.md if sequencing or governance changes
- do not implement feature code unless a very small coordination-supporting change is required
- after every completed session handoff, choose the next safe routed session immediately unless the handoff contains a real blocker, approval need, or unresolved cross-feature decision
- do not wait for a manual user follow-up merely because a session has completed
- map `session status: completed | blocked | needs_approval` into the live watch status before writing the next coordinator handoff
- keep `Current Approval Question` and `Current Blocker` explicit in the live watch; set them to `none` when they do not apply
- keep `Last Reviewed Handoff` and `Next Coordinator Action` explicit in the live watch so the current monitoring state is actionable at a glance
- keep the live watch field order stable: status, last coordinator check, approval question, blocker, routed session, last reviewed handoff, next coordinator action, why this is the current step, current expected outcome, current pause condition, monitoring note

Return:
- current priority order
- accepted handoffs
- coordinator status: routing_next | waiting_for_approval | blocked
- unresolved blockers
- which session should run next
- whether an integration checkpoint is now required
```

## Managed Feature Sessions

### Session 1. Shared Platform Foundations Lock

```text
You are Session 1 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md
4. Work on branch version/2 unless explicitly told otherwise
5. Do not invent behaviour outside the approved docs
6. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Strengthen and complete the shared platform foundations documentation so future feature work cannot drift.

Requirements:
- define the organisation model
- define the product model
- define collection ownership rules
- define domain fallback ownership boundaries
- define the analytics driver contract at documentation level only
- define internal module boundaries at documentation level only
- record unresolved dependencies clearly
- update docs in place
- do not implement application code yet
- update docs/project/update-tracker.md with decisions and blockers

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 2. Roles, Permissions, And Workflow States

```text
You are Session 2 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md
4. Work on branch version/2 unless explicitly told otherwise
5. Do not invent behaviour outside the approved docs
6. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Define the role model, permission model, and workflow states for the platform.

Requirements:
- use Statamic users, roles, and groups as the admin foundation
- define organisation admin, product manager, editor, reviewer, approver, sender, analyst, and super admin roles
- define campaign workflow states
- define submission workflow states
- define ownership and approval boundaries
- update docs in place
- do not implement code yet

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 3. Domain Resolution Architecture

```text
You are Session 3 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Define the exact domain resolution architecture for the platform.

Requirements:
- product domain if verified and enabled
- else organisation default domain
- else platform domain
- define domain use for forms, preferences, unsubscribe, browser-view, landing pages, and campaign links
- define intended architecture such as DomainResolver or ProductUrlGenerator
- update docs only
- do not implement code

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Integration Checkpoint A

```text
You are an integration checkpoint session under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
4. Read all relevant feature docs touched by Sessions 1 to 3
5. Work on branch version/2 unless explicitly told otherwise

Task:
Reconcile the outputs of Sessions 1 to 3 before downstream feature sessions continue.

Requirements:
- check model consistency
- check terminology consistency
- check role/domain/foundation assumptions for drift
- identify unresolved blockers
- update docs/project/update-tracker.md with accepted baseline or required corrections
- documentation only

Return:
- accepted shared baseline
- conflicts found
- blockers found
- whether Sessions 4 to 6 may proceed safely
- what the coordinator must resolve next
```

### Session 4. Subscriber Lifecycle And Preference Architecture

```text
You are Session 4 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read any accepted integration notes from Checkpoint A
5. Work on branch version/2 unless explicitly told otherwise
6. Do not invent behaviour outside the approved docs
7. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Define the stable subscriber lifecycle and preference architecture.

Requirements:
- subscriber becomes active after signup email delivery
- define pending, active, unsubscribed, bounced, complained, and resubscribed behaviour
- define group and subgroup membership rules
- define suppression and reactivation rules
- define what belongs to profile, membership, and event history
- update docs only

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 5. Newsletter Platform Spec

```text
You are Session 5 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read accepted outputs from Sessions 1 to 4 and Checkpoint A
5. Work on branch version/2 unless explicitly told otherwise
6. Do not invent behaviour outside the approved docs
7. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Build the full newsletter platform documentation spec.

Requirements:
- define campaign drafting, review, approval, scheduling, sending, retry, finalisation, and analytics workflow
- define product-to-collection behaviour
- define template ownership and reuse
- define audience targeting rules
- define domain-aware newsletter public surfaces
- create or update README.md, workflow.md, backlog.md, and implementation-notes.md under docs/features/newsletter-platform/
- do not implement code yet

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 6. Form And Data Collection Platform Spec

```text
You are Session 6 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md
4. Read accepted outputs from Sessions 1 to 4 and Checkpoint A
5. Work on branch version/2 unless explicitly told otherwise
6. Do not invent behaviour outside the approved docs
7. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Build the full form and data collection platform documentation spec.

Requirements:
- support subscription, application, and data_collection modes
- define reusable form template strategy
- define hardcoded customisation boundaries
- define approved external embed/API usage
- define platform and domain behaviour for form surfaces
- define submission lifecycle, review flow, export requirements, and audit needs
- create or update README.md, workflow.md, backlog.md, and implementation-notes.md under docs/features/form-data-collection-platform/
- do not implement code yet

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Integration Checkpoint B

```text
You are an integration checkpoint session under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
4. Read all relevant feature docs touched by Sessions 4 to 6
5. Work on branch version/2 unless explicitly told otherwise

Task:
Reconcile subscriber, newsletter, and form platform outputs before analytics and build-order planning continue.

Requirements:
- check lifecycle consistency
- check workflow consistency
- check domain and surface consistency
- check that no feature has broken another in documentation
- update docs/project/update-tracker.md with accepted baseline or required corrections
- documentation only

Return:
- accepted shared baseline
- conflicts found
- blockers found
- whether Sessions 7 to 8 may proceed safely
- what the coordinator must resolve next
```

### Session 7. Analytics Contract And Reporting Model

```text
You are Session 7 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/README.md
4. Read accepted outputs from prior integration checkpoints
5. Work on branch version/2 unless explicitly told otherwise
6. Do not invent behaviour outside the approved docs
7. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Define the internal analytics model and reporting contract.

Requirements:
- database is the only production read path for now
- define AnalyticsReaderInterface, AnalyticsWriterInterface, and optional AnalyticsEventStoreInterface at documentation level
- define canonical metrics and reporting tables needed for newsletters, subscribers, and submissions
- define GA4 boundary only for behavioural and acquisition analytics
- do not begin ClickHouse work or planning beyond preserving the separate-future-track boundary
- update docs only

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 8. Implementation Guardrails And Build Order

```text
You are Session 8 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/session-roadmap.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
4. Read accepted feature docs and integration checkpoint outputs
5. Work on branch version/2 unless explicitly told otherwise
6. Do not invent behaviour outside the approved docs
7. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Define the non-breaking implementation order for a stable build.

Requirements:
- identify which contracts must be implemented before feature code starts
- define migration order
- define test expectations by feature
- define rollback and verification expectations
- define documentation update rules for every future implementation session
- update docs/project/session-roadmap.md and docs/project/update-tracker.md
- documentation only

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether implementation can begin safely
- what the coordinator or another session must do next
```

## Captured Implementation Sessions 9-26

These sessions were already represented in `docs/project/update-tracker.md` and `docs/features/shared-platform-foundations/implementation-notes.md`, but were missing from this prompt pack. They are captured here as the source prompt record for continuity and auditability.

Common rules for Sessions 9-26:
- Read `/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md`
- Read `/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md`
- Read `/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md`
- Read the relevant feature README or workflow before editing feature code
- Work only on branch `version/2` unless the coordinator explicitly changes the lane
- Do not touch main-branch observer-violation work from this v2 prompt pack
- Keep every change additive, scoped, documented, and covered by targeted verification
- Escalate cross-feature uncertainty to the coordinator before inventing behaviour
- Return decisions, files changed, verification run, blockers, and next-session handoff

### Session 9. Foundation Code Scaffolding

```text
You are Session 9 under coordinator management for Mailserver version/2.

Task:
Create the first safe code scaffolding for the shared platform foundation without changing live behaviour.

Scope:
- introduce minimal namespaces, service placeholders, contracts, and tests needed by later product ownership, domain, forms, and analytics sessions
- preserve existing newsletter behaviour
- update implementation notes and tracker with the scaffold boundaries

Do not:
- wire new production paths before their owning sessions
- introduce destructive migrations or data movement
- implement observer-violation work from main
```

### Session 10. Persistence And Scope Model Resolution

```text
You are Session 10 under coordinator management for Mailserver version/2.

Task:
Resolve the persistence model and scope boundaries for products, organisations, groups, subscribers, forms, campaigns, domains, and analytics.

Scope:
- document the canonical ownership chain and database read path
- identify required additive migrations and ordering
- define unresolved questions as coordinator blockers

Do not:
- implement migrations without a documented ownership decision
- create cross-product sharing rules without approval
- change production read paths
```

### Session 11. Persistence Foundation Scaffolding

```text
You are Session 11 under coordinator management for Mailserver version/2.

Task:
Add the additive persistence foundation needed for product-scoped platform behaviour.

Scope:
- scaffold migrations and model relationships needed by later ownership-aware work
- keep existing records compatible
- add targeted tests for schema presence or model boundaries where practical

Do not:
- backfill historical ownership unless explicitly approved
- remove existing columns, routes, or Statamic data assumptions
- activate ClickHouse or external analytics storage
```

### Session 12. Authorization Scope Scaffolding

```text
You are Session 12 under coordinator management for Mailserver version/2.

Task:
Create the authorization scope scaffolding for Statamic user roles, product ownership, and organisation-aware access.

Scope:
- define reusable authorization helpers or policies
- ensure CP access can later be narrowed by assigned product or organisation
- document any places where current reads remain broader than the final target

Do not:
- rely on UI hiding as the only authorization layer
- assign implicit global access to product-scoped records
- break existing super-admin workflows
```

### Session 13. Domain Scaffolding

```text
You are Session 13 under coordinator management for Mailserver version/2.

Task:
Scaffold product-domain resolution so each product can use its own verified domain when available and fall back to the platform domain.

Scope:
- add domain resolver boundaries and persistence placeholders
- preserve deterministic fallback to platform domain and product chain URLs
- document verification, DNS, and routing assumptions

Do not:
- require every product to have a custom domain
- make unverified domains active
- create public routes that bypass ownership or form status checks
```

### Session 14. Hosted Form URL Domain Wiring

```text
You are Session 14 under coordinator management for Mailserver version/2.

Task:
Wire hosted form URL generation to the accepted domain resolver boundary.

Scope:
- ensure form URLs prefer verified product domains and fall back safely
- keep embed URLs stable for allowed platforms
- update documentation with generated URL expectations

Do not:
- change form submission semantics
- weaken embed allow-list checks
- introduce external DNS automation
```

### Session 15. Product Ownership Column Scaffolding

```text
You are Session 15 under coordinator management for Mailserver version/2.

Task:
Add product ownership columns and model hooks needed for product-scoped records.

Scope:
- add additive ownership columns where approved
- keep existing records readable during transition
- document which tables remain unowned until later sessions

Do not:
- perform unsafe historical ownership guesses
- enforce reads before write paths are stable
- delete or remap existing subscriber data
```

### Session 16. Ownership-Aware Read Boundary

```text
You are Session 16 under coordinator management for Mailserver version/2.

Task:
Introduce ownership-aware read boundaries for product-scoped CP and service reads.

Scope:
- scope reads by product or organisation where ownership data is available
- preserve fail-closed behaviour where ownership is ambiguous
- update tests and notes for any intentionally deferred read boundaries

Do not:
- broaden access to global records
- hide data only in templates while leaving service queries unscoped
- change write ownership rules assigned to later sessions
```

### Session 17. Subscriber Group Ownership Write Boundary

```text
You are Session 17 under coordinator management for Mailserver version/2.

Task:
Apply product ownership consistently when subscriber groups or subgroup memberships are created or updated.

Scope:
- ensure group writes receive the correct product scope
- prevent cross-product membership assignment
- keep subscriber identity records intact

Do not:
- delete subscribers when group relationships change
- silently move subscribers between products
- implement campaign ownership rules outside this session's dependency boundary
```

### Session 18. Campaign Ownership Write Boundary

```text
You are Session 18 under coordinator management for Mailserver version/2.

Task:
Apply product ownership consistently to campaign creation, update, and targeting writes.

Scope:
- enforce campaign product assignment at write time
- validate selected audience structures against the campaign product
- document any historical campaign records that remain transition-only

Do not:
- backfill historical campaigns without coordinator approval
- allow cross-product campaign targeting
- change delivery infrastructure behaviour
```

### Session 19. Demo Audience Ownership Consistency

```text
You are Session 19 under coordinator management for Mailserver version/2.

Task:
Make demo and seed audience data respect the same ownership rules as production data.

Scope:
- align demo groups, subscribers, and campaign fixtures with product ownership
- preserve repeatable demo setup
- document fixture assumptions

Do not:
- use demo-only shortcuts in production code
- create globally shared demo audiences unless explicitly marked and approved
- change public form submission behaviour
```

### Session 20. CP Campaign Product Selection

```text
You are Session 20 under coordinator management for Mailserver version/2.

Task:
Add controlled product selection to campaign creation in the Statamic control panel.

Scope:
- expose only products available to the current user
- persist the selected product through the approved ownership write path
- validate product selection server-side

Do not:
- rely only on client-side filtering
- allow users to select products outside their authorization scope
- change campaign delivery execution
```

### Session 21. Statamic Relational User Identity Bridge

```text
You are Session 21 under coordinator management for Mailserver version/2.

Task:
Bridge Statamic user identity to relational product and organisation ownership checks.

Scope:
- provide a stable way to resolve the current Statamic user into platform ownership context
- support roles, groups, and product assignments without duplicating identity logic in every feature
- document unresolved edge cases around super-admins or missing relational records

Do not:
- replace Statamic user management
- hardcode user IDs or role names outside documented configuration
- bypass existing Statamic authentication
```

### Session 22. Resume CP Campaign Product Selection

```text
You are Session 22 under coordinator management for Mailserver version/2.

Task:
Finish the scoped CP campaign product selection work left by Session 20.

Scope:
- complete remaining validation, UI, persistence, and tests for campaign product selection
- keep the implementation aligned with the Session 21 identity bridge
- update tracker handoff with remaining gaps or completion proof

Do not:
- reopen unrelated campaign behaviour
- implement audience lifecycle deletion
- add external analytics or reporting storage
```

### Session 23. Campaign Audience Ownership Consistency

```text
You are Session 23 under coordinator management for Mailserver version/2.

Task:
Ensure campaign audience selection and targeting are consistent with product ownership.

Scope:
- filter available audience groups and subgroups by campaign product
- reject mismatched campaign audience submissions server-side
- preserve historical campaign safety where ownership cannot be proven

Do not:
- silently retarget existing campaigns
- share audiences across products without approved rules
- weaken campaign write validation added by earlier sessions
```

### Session 24. CP Campaign Edit Update Ownership Parity

```text
You are Session 24 under coordinator management for Mailserver version/2.

Task:
Bring campaign edit and update flows to parity with campaign create ownership rules.

Scope:
- preserve product ownership during campaign edits
- validate audience changes against the campaign product
- prevent unauthorized product changes on existing campaigns

Do not:
- allow product reassignment by editing hidden fields
- break existing campaign drafts
- introduce delivery-side changes
```

### Session 25. CP Audience Group Product Selection

```text
You are Session 25 under coordinator management for Mailserver version/2.

Task:
Add controlled product selection to audience group creation and editing in the Statamic control panel.

Scope:
- expose only authorized products to the current user
- persist product ownership for top-level groups and subgroups
- validate group hierarchy ownership consistency

Do not:
- permit subgroups under a different product from their parent
- delete or orphan subscribers
- implement lifecycle archive/delete enforcement beyond necessary validation
```

### Session 26. Group Index Visibility And Delete Enforcement

```text
You are Session 26 under coordinator management for Mailserver version/2.

Task:
Scope audience group index visibility and enforce safe group delete rules.

Scope:
- show only groups visible to the current user and product context
- block deletion when ownership, campaign usage, or subscriber membership makes it unsafe
- document the remaining lifecycle policy that Session 27 must complete

Do not:
- hard-delete subscribers through group deletion
- bypass archive-first handling for previously used groups
- claim lifecycle enforcement is complete if reassignment or archive flows remain pending
```

## Current Controlled Implementation Prompt

### Session 27. Audience Structure Lifecycle Enforcement

```text
You are Session 27 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Work on branch version/2 unless explicitly told otherwise
8. Do not invent behaviour outside the approved docs
9. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Implement the approved audience-structure lifecycle policy for top-level groups and subgroups within the narrowest safe CP and service boundary.

Requirements:
- preserve the accepted rule that previously used groups or subgroups are archive-only, not hard-deletable
- preserve the accepted rule that unused groups or subgroups with subscribers require reassignment or explicit membership removal before delete
- preserve the accepted rule that deleting a group or subgroup must not delete subscriber identity records
- add only the minimum additive persistence, validation, and service behaviour needed for this lifecycle boundary
- keep database-backed subscriber identity as the only production truth path
- fail closed when historical campaign targeting, ownership mismatch, or unresolved audience state makes deletion unsafe
- update docs/project/update-tracker.md and the relevant feature implementation notes alongside code

Do not:
- broaden audience reads beyond the already accepted CP boundaries
- perform historical ownership backfill or destructive cleanup
- invent cross-product audience sharing
- silently remap form, campaign, or subscriber data across products
- auto-delete subscriber identity records during group or subgroup cleanup

Verification expectations:
- targeted tests for historical-usage delete denial or archive gating
- targeted tests for subscriber reassignment or membership-removal gating before delete
- targeted tests proving subscriber identity records survive group or subgroup lifecycle changes
- direct verification notes for any MySQL-only or CP-only behaviour that cannot be fully proven in the current execution environment

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 28. Audience Archive State And CP Archive Action

```text
You are Session 28 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Work on branch version/2 unless explicitly told otherwise
8. Do not invent behaviour outside the approved docs
9. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Implement additive archived-state persistence and a scoped CP archive action for audience groups and subgroups that cannot be hard-deleted because they have campaign history.

Requirements:
- consume the Session 27 delete-safety enforcement rather than redesigning it
- add only additive archived-state fields needed for groups and subgroups
- add scoped archive behaviour that reuses the accepted product-scope and subgroup-parent checks
- ensure archived groups and subgroups are excluded from new campaign targeting and form/preference assignment where this session touches those paths
- preserve historical campaign, analytics, and audit readability
- keep subscriber identity records untouched
- update docs/project/update-tracker.md and the relevant feature implementation notes alongside code

Do not:
- perform historical ownership backfill or campaign-audience cleanup
- broaden audience reads beyond the already accepted CP boundaries
- silently remap subscribers or form configuration across products
- make destructive schema changes
- auto-delete subscriber identity records

Verification expectations:
- targeted tests for archive action scope enforcement
- targeted tests proving archived structures are excluded from newly selectable targeting or assignment paths touched in this session
- targeted tests proving historical campaign audience rows remain readable after archive
- direct verification notes for any MySQL-only or CP-only behaviour that cannot be fully proven in the current execution environment

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 29. Archive-Aware Subscriber Management And Import Assignment

```text
You are Session 29 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. If the docs are unclear on a cross-feature issue, update the docs first or escalate back to the coordinator

Task:
Complete archive-aware filtering for CP subscriber create/edit and subscriber import assignment surfaces.

Requirements:
- consume the Session 28 archived-state model and archive actions
- exclude archived subgroups from CP subscriber create/edit selectable subgroup lists
- reject archived subgroup ids during CP subscriber store/update
- exclude archived subgroups from import default-subgroup options
- reject archived subgroup ids during import assignment
- preserve existing subscriber identity records and active membership history
- update docs/project/update-tracker.md and the relevant feature implementation notes alongside code

Do not:
- add unarchive or restore behaviour
- perform historical cleanup or ownership backfill
- broaden audience reads outside the targeted subscriber-management/import paths
- silently remap subscribers from archived subgroups to active subgroups
- auto-delete subscriber identity records

Verification expectations:
- targeted tests for CP subscriber assignment validation where feasible
- targeted tests for import assignment validation where feasible
- direct verification notes for any MySQL-only or CP-only behaviour that cannot be fully proven in the current execution environment

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 30. Audience Lifecycle CP Runtime Verification And Hardening

```text
You are Session 30 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. If verification exposes a cross-feature policy gap, record it and escalate to the coordinator instead of choosing a new product rule

Task:
Verify the audience lifecycle behaviour implemented in Sessions 26 to 29 through migrated MySQL-backed CP/runtime paths, then make only narrow hardening fixes that are directly required by the verification.

Requirements:
- verify archive columns and ownership columns exist after migrations in the configured test/runtime database
- verify scoped group and subgroup delete guards still block historically used or actively subscribed audience structures
- verify scoped archive actions remain available only for historically used structures
- verify CP subscriber create/edit option lists and direct-post validation exclude archived subgroups
- verify subscriber import default options, default subgroup validation, and CSV slug mapping exclude archived subgroups
- preserve subscriber identity records and historical campaign audience rows
- update docs/project/update-tracker.md and the relevant feature implementation notes alongside any verification or hardening change

Do not:
- add unarchive or restore behaviour
- perform historical cleanup, ownership repair, or campaign-audience orphan repair
- broaden audience reads outside the Session 26 to 29 lifecycle paths
- silently remap subscribers from archived subgroups to active subgroups
- auto-delete subscriber identity records
- run broad formatting rewrites of legacy controllers/services unless the coordinator explicitly approves it

Verification expectations:
- run the focused Session 26 to 29 regression tests
- run any direct migration/schema check needed to prove the runtime database shape
- use CP/browser verification where available; if unavailable, record the exact limitation and the strongest executed substitute
- document rollback notes for any hardening change

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 31. Subscriber Signup Pending-To-Active Lifecycle

```text
You are Session 31 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. If the existing mail/webhook flow cannot reliably prove signup email delivery, record that as a blocker or dependency before choosing activation semantics

Task:
Align public newsletter signup and resubscribe behaviour with the accepted subscriber lifecycle rule that new signups become `pending` first and only become `active` after signup email delivery is confirmed.

Requirements:
- inspect the current public subscription form service, confirmation mail, provider delivery webhook handling, and subscriber status transitions
- make public signup create or keep subscribers as `pending` before delivery-confirmed activation where this can be implemented safely
- preserve subscriber identity records and existing membership rows
- preserve product-owned subgroup assignment boundaries from Sessions 26 to 30
- prevent resubscribe flows from bypassing the pending state unless a reliable delivery-confirmed activation signal exists
- add targeted tests documenting pending creation, delivery-confirmed activation when available, and no unsafe activation when delivery is not confirmed
- update docs/project/update-tracker.md and relevant feature implementation notes alongside code

Do not:
- treat queued mail as delivered unless the existing provider flow explicitly supports that as the accepted delivery-confirmed signal
- invent a fake delivery event or product rule to force activation
- delete subscriber identity records
- silently remap subscribers across subgroups, groups, products, or organisations
- change CP manual subscriber creation/import status semantics unless explicitly required by the public signup lifecycle
- broaden audience reads, add unarchive behaviour, or perform historical cleanup/backfill

Verification expectations:
- targeted tests for public signup pending behavior
- targeted tests for delivery-confirmed activation if a reliable delivery signal exists
- targeted tests proving subscriber identity and memberships are preserved
- direct notes explaining any delivery-signal limitation that blocks full activation implementation

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 32. Pending Subscriber CP Visibility And Reporting Hardening

```text
You are Session 32 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. Treat Session 31's activation semantics as fixed input unless the coordinator explicitly reopens them

Task:
Make the new `pending` subscriber state visible and safe across operator-facing subscriber management, exports, widgets, and analytics summaries.

Requirements:
- inspect CP subscriber index filters, status badges, create/edit forms, show page, CSV export, widgets, analytics summaries, and send eligibility paths
- ensure pending subscribers can be filtered and exported correctly
- ensure pending subscribers are not counted as active subscribers or send-eligible subscribers
- preserve active-only campaign send eligibility
- keep CP manual subscriber creation and import semantics unchanged unless a visibility bug requires an explicit pending option
- add targeted tests for any changed filter/export/widget/analytics behavior
- update docs/project/update-tracker.md and relevant feature implementation notes alongside code

Do not:
- change Session 31 delivery-confirmed activation semantics
- treat queued mail as delivered
- add resend, expiry, or ageing policy
- delete subscriber identity records
- silently remap subscribers across subgroups, groups, products, or organisations
- broaden audience reads, add unarchive behaviour, or perform historical cleanup/backfill
- run broad formatting rewrites of legacy controllers/services unless the coordinator explicitly approves it

Verification expectations:
- targeted tests for CP/filter/export/reporting paths changed
- focused regression tests for public signup pending lifecycle and webhook activation
- direct notes for any CP/browser-only behavior that cannot be verified in the current execution environment

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 33. Subscriber Lifecycle CP Browser And Runtime Verification

```text
You are Session 33 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Do not invent behaviour outside the approved docs
8. Treat Sessions 31 and 32 activation and visibility semantics as fixed input unless the coordinator explicitly reopens them

Task:
Verify the pending subscriber lifecycle through actual public routes, provider-webhook-shaped payloads, and CP/browser-accessible subscriber surfaces where available.

Requirements:
- verify public signup route payload creates or preserves pending subscribers before delivery-confirmed activation
- verify provider-webhook-shaped payloads activate only correlated pending subscription confirmation lifecycle emails
- verify CP subscriber index/filter/edit/export surfaces expose pending correctly
- verify newsletter widget data or rendered widget separates pending from active
- verify campaign audience resolution remains active-only
- update docs/project/update-tracker.md and relevant feature implementation notes with verification evidence and any narrow hardening change

Do not:
- change Session 31 delivery-confirmed activation semantics
- treat queued mail as delivered
- add resend, expiry, or ageing policy
- delete subscriber identity records
- silently remap subscribers across subgroups, groups, products, or organisations
- broaden audience reads, add unarchive behaviour, or perform historical cleanup/backfill
- run broad formatting rewrites of legacy controllers/services unless the coordinator explicitly approves it

Verification expectations:
- use browser/CP verification when available
- if browser-authenticated CP access is unavailable, record the exact limitation and the strongest executed substitute
- run focused public signup, webhook lifecycle, CP pending visibility, widget, export, and active-only send eligibility tests
- avoid parallel PHPUnit runs against the shared MySQL test database because RefreshDatabase migration races have already been observed

If you discover a dependency on another session or a missing cross-project rule, do not invent the missing behaviour. Record it in docs/project/update-tracker.md and return it as a blocker or dependency for coordinator review.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether this area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

### Session 34. Subscriber Lifecycle Authenticated CP Visual Verification

```text
You are Session 34 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Work on branch version/2 unless explicitly told otherwise
8. Treat Sessions 31, 32, and 33 pending lifecycle rules as fixed input

Task:
Perform a narrow authenticated CP visual/runtime verification pass for pending subscriber surfaces.

Requirements:
- verify the subscriber index can display pending subscribers in the actual CP/browser surface when authenticated access is available
- verify the pending status filter works in the CP/browser surface when authenticated access is available
- verify the subscriber edit screen preserves pending status in the CP/browser surface when authenticated access is available
- verify the newsletter widget visually separates pending from active when authenticated access is available
- verify the export trigger/response path is reachable from the CP/browser surface when authenticated access is available
- if authenticated CP access is unavailable, record the exact limitation and the strongest substitute evidence already executed or newly executed
- update docs/project/update-tracker.md and relevant implementation notes with visual/runtime evidence or the explicit access limitation

Do not:
- change pending activation semantics
- treat queued mail as delivered
- add resend, expiry, ageing, or reminder policy
- add unarchive behaviour
- delete subscriber identities or silently remap subscribers
- broaden audience reads or perform historical cleanup/backfill
- perform broad formatting rewrites
- invent credentials, bypass authentication, or weaken CP authorization to make the check pass

Verification expectations:
- use browser/CP verification if authenticated access exists
- capture exact route/url names, status codes, rendered labels, and any screenshot/browser observations where available
- if browser verification cannot run, rerun only the smallest focused tests needed to keep substitute evidence current
- avoid parallel PHPUnit runs against the shared test database

If you discover that CP access requires a real operator credential, record it as a verification limitation, not an application blocker.

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- dependencies discovered
- blockers discovered
- whether the lifecycle area is safe for implementation now or still blocked
- what the coordinator or another session must do next
```

## Planned Next Controlled Implementation Prompts

These prompts are intentionally narrow. Do not run multiple lifecycle-changing sessions in parallel.

### Session 35. Newsletter Dashboard Widget Mount And Visual Verification

```text
You are Session 35 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Work on branch version/2 unless explicitly told otherwise
7. Treat Sessions 31 to 34 pending lifecycle rules as fixed input

Task:
Close the remaining authenticated CP visual verification gap for the newsletter dashboard widget.

Requirements:
- determine why the newsletter widget is not mounted for the authenticated CP dashboard
- if safe and configuration-scoped, mount the existing newsletter widget for a testable authenticated dashboard surface
- visually verify that the widget separates pending, active, unsubscribed, bounced, and complained counts
- preserve existing widget data semantics and active-only send eligibility
- update docs/project/update-tracker.md and newsletter implementation notes with exact verification evidence

Do not:
- change subscriber lifecycle semantics
- treat queued mail as delivered
- add resend, expiry, ageing, reminder, unarchive, cleanup, or backfill behaviour
- weaken Statamic CP authentication or authorization
- perform broad dashboard redesign or legacy formatting rewrites

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- verification run
- blockers discovered
- next recommended coordinator action
```

### Session 36. Pending Subscriber Resend Retry Ageing And Expiry Policy

```text
You are Session 36 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Inspect the current working tree before editing; Session 35 and Session 39 changes may still be uncommitted in the shared checkout
8. Work on branch version/2 unless explicitly told otherwise
9. Treat Sessions 31 to 35 pending lifecycle and widget rules as fixed input
10. Treat Session 39 generic Elastic payload hardening as fixed input

Task:
Implement the approved pending subscriber resend, retry, ageing, and expiry baseline with focused tests.

Rules:
- preserve delivery-confirmed activation as the only pending-to-active path
- pending subscribers must not become active from queued mail, resend action, preference edit, CP edit, import, or ageing alone
- add the narrowest safe persistence needed to track resend count, last resend time, expiry time, and lifecycle audit state
- add a CP/operator resend action only for eligible pending subscribers
- enforce resend limits and cooldowns server-side, not only in the UI
- expose pending ageing and expired-pending state clearly in CP/operator surfaces where touched
- ensure expired pending subscribers remain excluded from campaign audiences
- update docs/project/update-tracker.md and relevant implementation notes with decisions, files changed, and verification evidence

Policy baseline to implement unless a blocker is discovered:
- pending subscribers may receive up to 3 confirmation resends
- resend cooldown is 15 minutes between operator-triggered sends
- pending subscribers expire after 7 days without delivery-confirmed activation
- expired pending subscribers remain subscriber records but are not active and not send-eligible
- operator copy should explain that activation still requires delivery/open/click confirmation from the signup lifecycle email

Do not:
- change the accepted delivery-confirmed activation semantics
- activate pending subscribers from resend, expiry, or manual CP save
- delete subscriber identity records
- introduce unarchive, historical cleanup, ownership backfill, broad read cutover, or real-provider semantic changes
- touch Session 35 dashboard widget files or Session 39 webhook hardening files unless a test bootstrap issue requires it, and record the reason if so
- run broad formatting rewrites of legacy files

Verification expectations:
- create a pending subscriber and confirm it remains pending until a correlated subscription confirmation webhook activates it
- test resend eligibility, resend count, cooldown, and resend limit
- test expired pending state and confirm it does not become active without webhook confirmation
- test active-only campaign audience resolution still excludes pending and expired pending subscribers
- run focused lifecycle, webhook, subscriber CP, and audience eligibility tests affected by this session
- avoid parallel PHPUnit runs against the shared MySQL test database

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- verification run
- blockers discovered
- whether pending resend/expiry is safe for implementation now or still blocked
- next recommended coordinator action
```

### Session 37. Audience Unarchive And Restore Policy

```text
You are Session 37 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Inspect the current working tree before editing; Sessions 35, 36, and 39 changes may still be uncommitted in the shared checkout
8. Work on branch version/2 unless explicitly told otherwise
9. Treat Sessions 27 to 30 archive/delete rules as fixed input
10. Treat Sessions 31 to 36 pending lifecycle rules as fixed input

Task:
Implement the approved audience unarchive/restore policy for archived groups and subgroups with focused tests.

Rules:
- restore is allowed only for audience groups or subgroups that are currently archived
- restore must require the same product ownership and operator scope checks as archive/delete
- restoring a top-level group makes the group assignable again only when ownership is valid and the group remains otherwise eligible
- restoring a subgroup makes it assignable again only when its parent group is active, unarchived, and product ownership matches
- restoring a parent group must not automatically restore archived subgroups
- restoring a subgroup must not implicitly restore its parent group
- restored structures may become selectable again for new campaign targeting, CP subscriber assignment, imports, public preference assignment, and allowed form assignment only through existing scoped active/assignable query paths
- historical campaign audience rows must remain intact and must not be rewritten
- preserve subscriber identity and do not silently remap subscribers
- update docs/project/update-tracker.md and relevant implementation notes with decisions, files changed, and verification evidence

Policy baseline to implement unless a blocker is discovered:
- authorised product-scoped operators may restore archived audience groups and subgroups
- restore clears archive state; if existing schema lacks separate restore audit fields, do not add broad audit persistence unless needed for safe implementation
- restored groups and subgroups become assignable again through the existing active/assignable filters
- restore must fail closed for unowned records, product mismatch, missing parent, archived parent, unauthorized operator, or already-active structures

Do not:
- delete subscriber identity records
- remap subscribers between groups, subgroups, products, or organisations
- repair historical campaign audience rows
- perform historical cleanup, ownership backfill, broad audience read cutover, pending lifecycle changes, or provider webhook semantic changes
- touch Session 35 widget, Session 36 pending lifecycle, or Session 39 webhook files unless this restore implementation directly requires shared helpers, and record the reason if so
- run broad formatting rewrites of legacy files

Verification expectations:
- archive a used group and confirm it is not assignable before restore
- restore the group and confirm ownership checks and assignable query paths make it safely selectable again
- archive a subgroup and confirm it is not assignable before restore
- restore the subgroup only when its parent group is active and unarchived
- confirm restore fails for archived parent, product mismatch, unauthorized scope, and already-active structures
- confirm subscribers are not deleted or remapped during restore
- confirm campaign targeting and subscriber assignment use restored structures only through scoped active/assignable query paths
- run focused lifecycle/audience tests affected by this session
- avoid parallel PHPUnit runs against the shared MySQL test database

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated, if any
- verification run
- blockers discovered
- whether audience restore is safe for implementation now or still blocked
- next recommended coordinator action
```

### Session 38. Historical Ownership And Audience Cleanup Plan

```text
You are Session 38 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Inspect the current working tree before editing; Sessions 35, 36, 37, and 39 changes may still be uncommitted in the shared checkout
8. Work on branch version/2 unless explicitly told otherwise
9. Treat Sessions 15 to 26 product ownership rules as fixed input
10. Treat Sessions 27 to 30 archive/delete rules and Session 37 restore rules as fixed input

Task:
Plan historical cleanup and backfill for old unowned, unsafe, or orphan-prone audience and ownership rows.

Rules:
- planning, audit, and dry-run reporting only unless separately approved
- inventory affected tables, mapping sources, rollback path, audit logging, dry-run output, and acceptance criteria
- provide a dry-run command or report path that reads current data and reports affected rows without mutation
- identify unowned or unsafe records across audience groups, subgroups, subscribers, campaign audiences, campaigns, product-owned newsletter records, and any relevant form mappings
- propose mapping sources for product and organisation ownership, including when mapping is impossible or ambiguous
- propose a rollback-safe execution plan for a future approved mutation session
- document exactly which rows cannot be safely auto-backfilled
- do not mutate historical data, backfill ownership, delete records, restore records, archive records, or remap subscribers in this session
- escalate if no reliable product or organisation mapping source exists
- update docs/project/update-tracker.md and relevant implementation notes with the audit plan, dry-run evidence, and unresolved approval questions

Implementation boundary:
- allowed: read-only audit command, read-only service, report view/file, tests proving no mutation
- not allowed: UPDATE, DELETE, INSERT backfill operations against existing historical records outside isolated test fixtures
- not allowed: hidden repair of campaign audience rows, subscriber memberships, product ownership, or archive state

Verification expectations:
- run the dry-run command/report only
- verify affected row counts and proposed mappings are reported
- verify ambiguous/unmappable records are reported as blockers, not guessed
- verify database state is unchanged after dry-run execution
- add focused tests for the dry-run report if code is introduced
- avoid parallel PHPUnit runs against the shared MySQL test database

Return session status as needs_approval for any data mutation proposal.
```

### Session 39. Elastic Email Real Payload Contract Verification

```text
You are Session 39 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/newsletter-platform/implementation-notes.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
6. Inspect the current working tree before editing; Session 35 widget changes may still be uncommitted in the shared checkout
7. Work on branch version/2 unless explicitly told otherwise
8. Treat Sessions 31 to 35 pending lifecycle and widget rules as fixed input

Task:
Verify real Elastic Email webhook payloads against the accepted pending-to-active contract.

Rules:
- compare real or exported provider payloads with current webhook parsing and correlation logic
- add fixtures/tests only when payload evidence supports them
- do not change activation semantics without coordinator approval
- record any provider mismatch as evidence with exact payload fields, redacting sensitive values
- do not touch dashboard widget files from Session 35 unless a test bootstrap issue requires it, and record the reason if so
- do not add resend, expiry, ageing, unarchive, cleanup, backfill, or broad read-cutover behaviour
- if no real payload examples are present locally, search only project files and test fixtures, then return the missing-payload requirement as a blocker or dependency rather than inventing provider payload shape
- if payload evidence shows the current contract is wrong, return `needs_approval` before changing lifecycle semantics

Verification expectations:
- run focused webhook tests that already cover subscription confirmation delivery/open/click, bounce, unsubscribe, complaint, and non-confirmation lifecycle mail cases
- add or update fixture-based tests only from evidence-backed payload fields
- avoid parallel PHPUnit runs against the shared MySQL test database

Return:
- session status: completed | blocked | needs_approval
- whether the current contract is confirmed, needs hardening, or requires approval-sensitive semantic change
- payload evidence reviewed
- docs updated
- code updated, if any
- verification run
- blockers discovered
- next recommended coordinator action
```

### Session 40. Form And Data Collection Implementation Baseline

```text
You are Session 40 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/README.md
4. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/workflow.md
5. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/implementation-notes.md
6. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/implementation-notes.md
7. Inspect the current working tree before editing; Sessions 35, 36, 37, 38, and 39 changes may still be uncommitted in the shared checkout
8. Work on branch version/2 unless explicitly told otherwise
9. Treat product/organisation ownership, domain fallback, archive-aware audience assignment, and pending lifecycle rules as fixed input
10. Treat Session 38 historical cleanup blockers as cleanup/backfill blockers only; do not mutate or depend on historical rows

Task:
Start the form and data collection implementation baseline beyond newsletter lifecycle foundations.

Rules:
- begin with the smallest safe persistence and service boundary for newly created product-owned forms
- use explicit product/organisation fixtures or existing approved product records; do not infer ownership from historical unowned rows
- preserve allowed-platform embed rules, product domain fallback, archive-aware audience assignment, and pending lifecycle semantics
- support reusable templates while allowing explicitly documented hardcoded customisations
- choose one first baseline path: configurable data-collection/application form storage with hosted render, allowed-origin submit, CP listing, and export
- subscription-mode integration may be touched only to confirm it still follows the accepted pending-to-active lifecycle; do not redesign subscriber activation
- reject disallowed embed origins server-side
- public hosted form URLs must use product domain when verified/available and platform fallback otherwise
- submissions must be stored as operational form submissions, not as subscriber truth unless the form mode explicitly requires subscription behaviour
- update docs/project/update-tracker.md and relevant implementation notes with decisions, files changed, verification evidence, and deferred form modes

Implementation boundary:
- allowed: additive migrations/models/services/controllers/routes/views/tests for product-owned forms and submissions
- allowed: reusable template registry or enum for the first supported templates
- allowed: export endpoint for stored submissions
- not allowed: historical ownership cleanup/backfill, subscriber remapping, broad domain-management redesign, GA4/ClickHouse analytics truth, or unrestricted no-code form builder scope
- not allowed: hardcoded custom form beyond a documented extension point unless the coordinator explicitly approves that specific flow

Verification expectations:
- create a product-owned form in tests or through the implemented CP/service path
- render hosted form on platform fallback domain logic and, where possible, product-domain URL generation logic
- submit from an allowed embed origin and store the submission
- reject a disallowed embed origin
- verify CP listing or service listing of submissions
- verify export returns stored submissions
- confirm archived audience structures cannot be assigned by form configuration
- confirm subscription-mode forms still follow pending-to-active rules if touched
- run focused form, domain, archive assignment, and subscription lifecycle tests affected by this session
- avoid parallel PHPUnit runs against the shared MySQL test database

Return:
- session status: completed | blocked | needs_approval
- selected first implementation slice
- decisions made
- docs updated
- code updated, if any
- verification run
- blockers discovered
- deferred form modes/features
- next recommended coordinator action
```

### Session 41. Documentation Tracking And Merge Readiness

```text
You are Session 41 under coordinator management for Mailserver version/2.

Before doing anything:
1. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md
4. Read relevant feature implementation notes for Sessions 35 to 40
5. Inspect the current working tree before editing; Sessions 35 to 40 changes may still be uncommitted in the shared checkout
6. Work on branch version/2 unless explicitly told otherwise
7. Do not change application behaviour

Task:
Decide and implement the documentation tracking strategy before v2 is merged to main.

Rules:
- inspect .gitignore and current docs tracking state
- decide whether docs/project, docs/features, and docs/artifacts should become tracked assets or be intentionally force-added
- if already clearly required for v2 coordination, adjust tracking with the narrowest safe `.gitignore` change
- if tracking docs remains approval-sensitive, do not change `.gitignore`; record exact force-add commands and merge checklist instead
- do not change application behaviour
- confirm hidden worktree/session drift by listing relevant Codex tasks and checking the local repo status
- document what must be committed before v2 merges to main
- include current uncommitted Session 35 to 40 files in the merge-readiness checklist
- include the Session 38 historical-cleanup blocker and the future v2 webhook-capture dependency as known non-merge blockers or blockers, based on evidence

Verification expectations:
- run `git status --short --branch`
- run `git check-ignore -v` for docs/project, docs/features, and docs/artifacts paths
- run `git ls-files` for currently documented project docs
- inspect current Codex task list for active Mailserver v2 feature tasks
- run the focused test suite covering Sessions 35, 36, 37, 39, and 40 if practical; if not practical, document the exact reason and last known passing commands

Return:
- session status: completed | blocked | needs_approval
- docs tracking decision
- git ignore changes, if any
- files that must be committed before merge
- active/stale task audit
- verification run
- merge-readiness checklist
- blockers and non-blocking follow-ups
```

### Future Follow-Up. V2 Subscription Confirmation Webhook Capture

```text
Do not start this task until v2 can send a real subscription confirmation email through Elastic Email and receive the resulting webhook.

Purpose:
Capture and verify a v2-generated subscription confirmation webhook payload.

Rules:
- use v2 as the implementation source of truth
- submit a real v2 newsletter signup that creates a pending subscriber
- allow Elastic Email to deliver, open, or click the subscription confirmation lifecycle email
- inspect the stored webhook payload for `subscriber_id`, `lifecycle_email`, and `subscription_status`
- confirm the pending subscriber activates only when those lifecycle fields are present and valid
- redact email addresses, IP addresses, account identifiers, message IDs, and other sensitive values in docs
- do not change pending-to-active semantics unless the captured v2 payload disproves the current contract and coordinator approval is obtained

Return:
- session status: completed | blocked | needs_approval
- captured redacted payload shape
- activation result
- docs updated
- code updated, if any
- verification run
- next coordinator action
```
