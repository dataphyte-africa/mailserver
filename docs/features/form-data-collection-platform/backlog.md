# Form And Data Collection Platform Backlog

## Purpose

Track the form and data collection platform work in dependency order so implementation does not outrun unresolved shared-platform rules.

## Dependency Rule

No backlog item should assume unresolved cross-feature behaviour is already safe to implement.

## Backlog

### FDC-1. Lock Form Taxonomy

Goal:

- confirm the canonical distinction between `subscription`, `application`, and `data_collection`

Depends on:

- accepted shared platform baseline

Status:

- documentation defined
- implementation not started

### FDC-2. Define Reusable Template Families

Goal:

- define the standard template families and their allowed configuration surfaces

Depends on:

- FDC-1
- coordinator acceptance of current feature spec

Status:

- partially documented

### FDC-3. Define Hardcoded Extension Policy

Goal:

- define when hardcoded product-specific customisation is allowed and how it should be isolated

Depends on:

- FDC-2
- coordinator confirmation that unresolved persistence and scope rules remain external constraints

Status:

- partially documented

### FDC-4. Define Public Integration And Allowed Platform Policy

Goal:

- define approved embed, schema-fetch, and API submission patterns

Depends on:

- Session 3 domain baseline
- coordinator decision on any external platform exceptions

Status:

- documentation required before implementation

### FDC-5. Define Submission Review And Audit Model

Goal:

- define operator review expectations, audit requirements, and export requirements

Depends on:

- Session 2 workflow baseline
- coordinator confirmation of role and scope boundaries

Status:

- documentation required before implementation

### FDC-6. Define Export Shapes And Retention Expectations

Goal:

- define what exports must exist for subscription, application, and data collection forms

Depends on:

- FDC-5
- analytics and reporting contract alignment from Session 7

Status:

- blocked by future analytics contract details

### FDC-7. Implementation Sequencing

Goal:

- map the feature to a non-breaking build order

Depends on:

- Integration Checkpoint B
- Session 8 implementation guardrails

Status:

- not ready yet
