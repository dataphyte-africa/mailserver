# Newsletter Platform Backlog

## Purpose

This backlog lists the documentation and implementation-preparation items required before and during newsletter platform build work.

## Must Resolve Before Implementation

- confirm subscriber lifecycle rules from Session 4 and Integration Checkpoint B
- confirm analytics contract and canonical reporting metrics from Session 7
- confirm any coordinator-approved exceptions to one product -> one primary public collection
- confirm final permission slug and policy naming from shared foundations
- confirm domain-management responsibilities and verification workflow are documented well enough for implementation

## High-Priority Build Items

- product-owned campaign model and workflow enforcement
- content-entry to campaign linkage model
- campaign review and approval transitions
- audience targeting rules limited to product-owned audiences
- scheduling, send, retry, and finalisation behaviour
- browser-view, unsubscribe, and preferences public-surface integration
- platform reporting metrics for newsletter operations

## Medium-Priority Build Items

- template ownership and reuse support
- richer product-level preview flow
- clearer campaign readiness and compliance checks
- archive and browser-view management refinements
- future dynamic segmentation hooks

## Deferred Or Coordinator-Gated Items

- cross-product newsletter audience targeting
- multiple primary public collections per product
- global template-management UI decisions
- advanced behavioural segmentation
- any implementation that assumes unresolved ClickHouse, warehouse, or alternate analytics read-backends

## Cross-Feature Dependencies

- shared-platform-foundations for ownership, workflow, and authorization
- subscriber lifecycle architecture for activation and suppression
- form/data platform for signup-fed audience integrity
- analytics contract for campaign dashboards and reporting parity

## Non-Breaking Rule

No backlog item should assume unresolved cross-feature behaviour as settled fact.
