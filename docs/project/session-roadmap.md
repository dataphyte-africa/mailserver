# Session Roadmap

## Purpose

This document defines the coordinator-managed session structure for the platform revamp.

The goal is to keep design, implementation, and integration in sync rather than letting separate sessions drift and reconcile too late.

## Session Model

Use:

- `1` coordinator session
- `8` managed feature or planning sessions before major build implementation
- recurring integration checkpoints between feature waves
- later implementation sessions that still report back to the coordinator

## Mandatory Session Rules

Every Codex session working on this revamp must:

1. read [source-of-truth.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/source-of-truth.md)
2. read [update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
3. read the relevant feature docs in `docs/features/`
4. stay on the `version/2` branch unless explicitly redirected
5. update docs before or alongside any decision that changes behaviour
6. escalate blockers or unresolved cross-feature assumptions back to the coordinator
7. end with an explicit handoff status of `completed`, `blocked`, or `needs_approval`

## Coordinator Session

### Coordinator 0. Programme Control

Focus:

- maintain project truth
- sequence work
- review handoffs
- resolve dependency conflicts
- decide when sessions must wait, proceed, or call for integration review

Primary outputs:

- current priorities
- accepted handoffs
- dependency decisions
- integration checkpoint decisions
- prompt updates when governance needs to change

### Coordinator Continuation Rule

The coordinator should continue the loop automatically after each completed session handoff.

That means:

- review the returned handoff
- update the `Current Coordinator Watch` section in [update-tracker.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md) so the live coordinator state stays current
- confirm whether the session ended with a real blocker, approval need, or unresolved cross-feature decision
- if no such blocker exists, route the next safe session immediately
- only pause and wait for user approval when a session explicitly returns a real decision gate, governance conflict, or approval-sensitive branch in behaviour

The coordinator should not wait for a manual user nudge merely because a session completed.

### Coordinator Status Mapping

When reviewing a session handoff, the coordinator should map the returned `session status` into the live watch like this:

- `completed` -> set `Current Coordinator Status` to `routing_next`, update `Current Routed Session`, and continue to the next safe session
- `needs_approval` -> set `Current Coordinator Status` to `waiting_for_approval` and record the exact approval question in the watch note
- `blocked` -> set `Current Coordinator Status` to `blocked` and record the exact blocker in the watch note

The live watch should always carry explicit `Current Approval Question` and `Current Blocker` fields:

- when status is `routing_next`, both fields should be `none`
- when status is `waiting_for_approval`, set the approval question explicitly and keep blocker as `none` unless a separate blocker also exists
- when status is `blocked`, set the blocker explicitly and keep approval question as `none` unless approval is also the reason progress cannot continue

The live watch should also keep:

- `Last Reviewed Handoff`
- `Next Coordinator Action`

Use this live watch field order:

1. `Current Coordinator Status`
2. `Last Coordinator Check`
3. `Current Approval Question`
4. `Current Blocker`
5. `Current Routed Session`
6. `Last Reviewed Handoff`
7. `Next Coordinator Action`
8. `Why This Is The Current Step`
9. `Current Expected Outcome`
10. `Current Pause Condition`
11. `Monitoring Note`

The coordinator should not infer a different live status unless the handoff explicitly justifies it.

## Session Structure

### Session 1. Shared Platform Foundations Lock

Focus:

- lock platform-wide terms and models
- define organisation and product ownership rules
- define collection ownership rules
- define analytics driver contract at documentation level
- identify unresolved foundation dependencies

Primary outputs:

- strengthened foundation docs
- explicit dependency list for later sessions
- coordinator handoff note

### Session 2. Roles, Permissions, And Workflow States

Focus:

- define Statamic role model
- define permission model
- define campaign workflow states
- define submission workflow states
- define operator responsibilities

Primary outputs:

- role matrix
- permission matrix
- workflow-state documentation
- coordinator handoff note

### Session 3. Domain Resolution Architecture

Focus:

- define product, organisation, and platform domain fallback behaviour
- define domain-aware public surfaces
- define intended URL generation architecture

Primary outputs:

- domain rules
- public surface mapping
- coordinator handoff note

### Integration Checkpoint A

Focus:

- reconcile Sessions 1 to 3
- check for model drift before lifecycle and product-feature sessions continue

Primary outputs:

- accepted shared model baseline
- blocker resolution
- implementation-safe foundation status

### Session 4. Subscriber Lifecycle And Preference Architecture

Focus:

- subscriber lifecycle
- group and subgroup rules
- preference mapping
- activation rule after delivered signup email
- suppression rules

Primary outputs:

- subscriber lifecycle spec
- audience and preference workflow
- coordinator handoff note

### Session 5. Newsletter Platform Spec

Focus:

- campaign lifecycle
- product collections
- template strategy
- preview/test/approval/send workflow
- analytics expectations

Primary outputs:

- newsletter platform feature spec
- campaign workflow spec
- coordinator handoff note

### Session 6. Form And Data Collection Platform Spec

Focus:

- form modes
- reusable form templates
- configuration model
- form schema and embed/API contract
- allowed-platform governance

Primary outputs:

- form platform feature spec
- embed/API governance rules
- coordinator handoff note

### Integration Checkpoint B

Focus:

- reconcile Sessions 4 to 6
- verify that subscriber, newsletter, and form logic remain aligned

Primary outputs:

- accepted feature interaction rules
- dependency and conflict resolution
- safe build baseline for later implementation

### Session 7. Analytics Contract And Reporting Model

Focus:

- define production analytics read path
- define reporting-table strategy
- define GA4 boundary
- define stable reader and writer contracts
- keep ClickHouse out of scope until explicitly opened later

Primary outputs:

- analytics contract
- reporting model
- coordinator handoff note

### Session 8. Implementation Guardrails And Build Order

Focus:

- define non-breaking implementation order
- define migration and verification expectations
- define feature-test responsibilities
- define rollback expectations

Primary outputs:

- implementation roadmap
- guardrail rules
- coordinator handoff note

## Non-Breaking Implementation Order

Implementation may begin only after the coordinator confirms that the required documentation baseline and relevant integration checkpoints have been accepted.

### Phase 0. Documentation Freeze For The First Build Wave

Required before code:

- accepted coordinator baseline
- accepted Integration Checkpoint A
- accepted Integration Checkpoint B
- accepted decisions for any foundation blocker directly touched by the planned code session

Do not begin feature code while relying on unresolved persistence, permission, domain-management, or scope-management assumptions.

### Phase 1. Shared Contracts And Config Skeletons

Implement first:

- service contracts and interfaces
- config objects and feature flags
- policy and authorization scaffolding
- shared URL/domain service interfaces
- analytics driver interfaces
- module or namespace boundaries

This phase should avoid behaviour changes where possible.

### Phase 2. Persistence Foundations

Implement only after the coordinator explicitly accepts the unresolved persistence model for the specific area being touched.

Typical work:

- additive database tables
- additive columns
- additive indexes
- additive enums or state support
- mapping tables for scope or ownership

Do not mix destructive cleanup with first-pass persistence rollout.

### Phase 3. Domain And Ownership Wiring

After persistence foundations are accepted:

- implement domain resolution services
- implement ownership-aware query boundaries
- implement non-breaking read paths for product and organisation scope

### Phase 4. Subscriber Lifecycle And Audience Behaviour

After shared ownership and domain boundaries exist:

- implement pending-to-active lifecycle changes
- implement subgroup and preference membership rules
- implement suppression and reactivation rules
- keep old behaviour behind controlled migration or compatibility handling where needed

### Phase 5. Newsletter Workflow Behaviour

Only after Phases 1 to 4 are stable:

- implement campaign workflow-state changes
- implement approval and send gating
- implement product-owned newsletter behaviour
- implement domain-aware public newsletter surfaces

### Phase 6. Form And Data Collection Workflow Behaviour

Only after shared lifecycle and ownership rules are stable:

- implement subscription, application, and data_collection workflow behaviour
- implement approved integration patterns
- implement review and export workflow changes
- implement domain-aware form surfaces

### Phase 7. Analytics And Reporting Behaviour

Only after the canonical platform workflows are stable enough to report correctly:

- implement reporting tables
- implement analytics reader and writer contracts
- keep `database` as the only production read path
- avoid introducing alternative analytics backends at this stage

### Phase 8. Hardening, Reconciliation, And Cleanup

Only after behaviour parity is verified:

- tighten policy enforcement
- remove transitional compatibility code if safe
- add performance improvements
- perform additive-to-final schema cleanup in isolated, reversible steps

## Migration Order Rules

Use this migration order unless the coordinator explicitly approves an exception:

1. additive schema only
2. backfill or projection command
3. compatibility read/write layer
4. guarded behaviour switch
5. verification period
6. cleanup migration in a later session

### Migration Safety Rules

- no destructive migration in the same session as a first behaviour switch
- no irreversible cleanup until real data verification passes
- no permission enforcement change before ownership and scope data are stable
- no domain-routing cutover before fallback behaviour is verified
- no analytics reporting cutover before canonical workflow data is stable

## Test Expectations By Feature

Every implementation session must define and run proportionate tests for the exact feature being changed.

### Shared Foundations

- unit tests for contracts and helpers
- policy and authorization tests
- configuration resolution tests
- ownership-scope tests

### Domain Resolution

- unit tests for resolution logic
- request or routing tests for public surface mapping
- fallback-path tests
- URL generation tests

### Subscriber Lifecycle And Audience

- feature tests for pending-to-active transitions
- suppression and reactivation tests
- subgroup membership tests
- send-eligibility tests

### Newsletter Workflow

- feature tests for workflow-state transitions
- approval and send-gating tests
- campaign audience and product-scope tests
- public-surface and link-generation tests where changed

### Form And Data Collection Workflow

- feature tests for each canonical mode
- submission-review workflow tests
- subscriber-linking tests where relevant
- approved integration endpoint tests

### Analytics And Reporting

- unit tests for reader and writer contracts
- reporting-table projection or aggregation tests
- regression tests for canonical metrics

### Cross-Feature Regression

Before merging any material implementation wave:

- run the existing automated suite relevant to the changed area
- add regression coverage for any bug or drift found during the session
- do not treat passing old tests alone as sufficient if new contracts were introduced

## Rollback And Verification Expectations

Every implementation session must define:

- what changed
- how to verify it
- how to revert it safely
- whether data backfill is required
- whether rollback is schema-only, code-only, or both

### Verification Rule

Verification must include:

- automated tests
- direct behaviour checks for changed workflow paths
- confirmation that adjacent features still behave correctly

### Rollback Rule

Rollback must prefer:

- feature-flag or config reversal first, where available
- additive rollback commands second
- destructive rollback only in isolated, explicit follow-up work if unavoidable

## Documentation Update Rules For Future Implementation Sessions

Every future implementation session must:

1. read the current source-of-truth and tracker first
2. update docs before or alongside any changed behaviour
3. record unresolved blockers instead of inventing missing rules
4. record what was implemented versus what remains planned
5. add verification and rollback notes to the tracker

### Drift Prevention Rule

If a code session discovers that the approved docs are no longer sufficient, it must stop treating the missing area as settled and escalate back to the coordinator before implementing cross-feature behaviour.

## Session Management Rule

Each session should update:

- [Project Update Tracker](/Users/dataphytefoundation/Herd/mailserver/docs/project/update-tracker.md)
- the relevant feature directory under `docs/features/`
- any affected current-state implementation docs

## Handoff Contract

Every feature session must finish with a handoff containing:

- decisions made
- docs updated
- dependencies discovered
- blockers discovered
- whether the feature is safe to implement now, or still blocked

## Prompt Pack

Use [codex-session-prompts.md](/Users/dataphytefoundation/Herd/mailserver/docs/project/codex-session-prompts.md) for the coordinator and managed feature session prompts.
