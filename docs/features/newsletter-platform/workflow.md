# Newsletter Platform Workflow

## Purpose

This workflow defines how a newsletter campaign moves from editorial preparation to delivery, reporting, and closure inside the `version/2` platform.

It inherits the subscriber and preference baseline from Session 4 rather than redefining subscriber truth locally.

## Workflow Overview

Primary path:

`draft -> in_review -> approved -> scheduled/sending -> sent`

Return path:

`in_review -> changes_requested -> draft`

Exceptional paths:

- `approved -> cancelled`
- `sending -> partial`
- `sending -> failed`
- `sent -> archived`
- `partial -> archived`
- `failed -> archived`

## Step 1. Product And Content Preparation

1. choose the owning organisation and product context
2. create or update the editorial entry inside the product-owned collection
3. confirm the entry uses the expected product template and content structure
4. confirm the content is intended for newsletter delivery rather than only web publishing

Rule:

- campaign operations must never bypass product ownership
- campaign content must come from the owning product editorial context or another explicitly approved source

## Step 2. Campaign Drafting

1. create a campaign in `draft`
2. link or resolve the content entry
3. choose audience scope:
   - full product audience
   - selected subgroups
   - future dynamic segment when that capability is approved
4. resolve sender identity defaults
5. generate preview and readiness metadata

Drafting is the working state for:

- subject checks
- audience checks
- sender checks
- preview checks
- editorial readiness

## Step 3. Review

1. move the campaign to `in_review`
2. reviewer checks:
   - content readiness
   - product ownership correctness
   - audience correctness
   - public-surface and domain correctness
   - compliance and footer requirements
3. outcome:
   - `changes_requested`
   - or review accepted for approval

Rule:

- review should happen before scheduling or send
- review findings should not be hidden in comments only; the state transition should reflect them

## Step 4. Approval

1. approver confirms campaign readiness
2. if accepted, move to `approved`
3. if not accepted, return to `changes_requested`

Approval confirms:

- content is acceptable
- target audience is acceptable
- sender identity is acceptable
- public links and resolved product surfaces are acceptable

## Step 5. Scheduling Or Immediate Send

From `approved`, the sender or other authorised actor may:

- move the campaign to `scheduled`
- or begin `sending`

### Scheduled Path

1. set the intended schedule
2. record scheduling metadata
3. campaign remains awaiting operational dispatch until the schedule is due

### Immediate Send Path

1. confirm final send intent
2. begin dispatch workflow
3. move campaign to `sending`

Rule:

- only approved campaigns may be scheduled or sent

## Step 6. Dispatch And Delivery

While `sending`:

1. resolve the final audience
2. create send records or their equivalent operational units
3. dispatch delivery work
4. capture provider transaction references and delivery-state updates

During this phase, the platform must track:

- send attempt progress
- delivery progress
- transient failures
- final unresolved failures

## Step 7. Retry And Recovery

Retry handling must be explicit.

### Retryable Cases

Retryable cases may include:

- temporary provider failures
- transient delivery throttling
- recoverable queue interruptions

### Non-Retryable Cases

Non-retryable cases may include:

- hard bounce conditions
- invalid recipient state
- invalid campaign preconditions

### Recovery Outcomes

After retry handling:

- move to `sent` if no unresolved failures remain
- move to `partial` if some sends remain unresolved
- move to `failed` if the campaign cannot complete safely

## Step 8. Finalisation

Campaign finalisation should:

- close active send processing
- record aggregate outcome state
- mark timestamps needed for reporting
- preserve auditability

Finalisation outcomes:

- `sent`
- `partial`
- `failed`

## Step 9. Analytics And Reporting

After or alongside finalisation, the platform should expose:

- total targeted
- total attempted
- delivered
- opened
- clicked
- bounced
- complained
- unsubscribed
- unresolved failures

Rule:

- operational analytics come from the platform reporting layer
- GA4 may supplement only public behavioural analysis

## Step 10. Archival

Once a campaign is operationally complete and no longer active:

1. archive the campaign
2. retain history and reporting
3. prevent unsafe edits to historical records

## Subscriber And Membership Dependencies

The campaign workflow depends on the accepted shared lifecycle baseline:

- only `active` subscribers are send-eligible
- `pending`, `unsubscribed`, `bounced`, and `complained` subscribers are not send-eligible
- membership remains product-owned
- groups or subgroups previously used for campaign targeting are archive-only, not hard-deletable
- groups or subgroups that have never been used may be hard-deleted only after all subscribers have been reassigned or had membership explicitly removed

This workflow does not redefine those lifecycle rules.

## Domain-Aware Workflow Requirements

At drafting, review, approval, and final send readiness, the workflow must confirm that:

- browser-view routes use the resolved product-preferred domain chain
- unsubscribe and preferences links use the resolved product-preferred domain chain
- product-facing public links do not silently fall back incorrectly

## Role Handoffs

- editor -> reviewer
- reviewer -> approver
- approver -> sender
- sender -> analytics or operations for post-send review where needed

## Dependency Notes

This workflow still does not settle:

- exact persistence schema
- exact permission slugs
- exact operator override flow for complaint reactivation
- final reporting-event persistence model
