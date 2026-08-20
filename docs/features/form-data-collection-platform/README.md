# Form And Data Collection Platform

## Objective

Standardise Mailserver as a configurable form and data collection platform in addition to its newsletter role.

## Scope

- reusable form templates
- subscription forms
- application forms
- structured data collection forms
- product or organisation domain usage for public form surfaces
- embed/API usage for approved external platforms
- review and workflow status for collected submissions
- custom hardcoded extensions where a reusable template is not enough
- submission exports and auditability
- operator-facing intake and review surfaces

## Product Position

This feature area should support two models at the same time:

- configurable reusable form patterns
- deliberate hardcoded customisations for organisation-specific operational requirements

That means the platform should not aim for unlimited form-builder flexibility. It should aim for controlled reuse with explicit extension points.

## Coordinator Constraints

This feature spec must remain aligned with the accepted baseline from Integration Checkpoint A.

It must not assume these unresolved items are already settled implementation facts:

- the exact persistence model for organisations and products
- the exact mapping of Statamic groups to organisation scope and product scope
- exact permission slug definitions
- who can manage domain configuration and verification state
- whether hybrid product behaviour is optional or required
- whether sender profiles are independent objects or structured overrides
- whether compliance settings are organisation-primary or product-mandatory

## Form Modes

- `subscription`
- `application`
- `data_collection`

## Mode Responsibilities

### Subscription

Purpose:

- collect subscriber identity and preference inputs
- create or update subscriber records
- attach subscriber to the correct product audience path

Expected outcomes:

- submission stored
- subscriber pipeline executed
- preference mapping resolved
- subscriber activation follows the shared lifecycle rule after signup email delivery

### Application

Purpose:

- collect structured candidate or participant intake data for a defined process
- support screening, review, shortlisting, approval, rejection, and follow-up

Expected outcomes:

- submission stored as reviewable operational data
- optional subscriber linkage only when the product flow requires it
- workflow status visible to operators
- downstream communication or review actions can be audited

### Data Collection

Purpose:

- collect structured non-subscriber operational or research data
- support configurable field sets and controlled exports

Expected outcomes:

- submission stored
- review requirement is configurable per form template
- exports and reporting align to operational needs without turning every form into a subscriber flow

## Target Product Behaviour

- each organisation can create forms tied to a product or operational purpose
- each form can collect preferences and map them into the right subscriber subgroup when relevant
- each public form can use the product domain when available, otherwise the organisation or platform fallback domain
- external sites can fetch schema and submit through approved integration patterns
- specialised flows such as observer applications can use reusable templates plus hardcoded business logic where necessary
- operators can review, classify, export, and act on submissions from the admin side

Audience-structure safeguards for form-linked products:

- forms must not continue assigning into archived groups or archived subgroups
- if a preference-backed subgroup is being retired, subscribers should be reassigned before the subgroup is deleted
- deleting an unused group or subgroup must not delete subscriber identity records
- any form configuration that references a deleted or archived audience structure must fail closed until remapped

## Core Design Principles

### 1. One Form Belongs To One Product

Every form belongs to exactly one product.

The form may support different operational purposes, but it must not span multiple products implicitly.

### 2. Templates Are Reusable, Not Unlimited

The platform should provide reusable templates for recurring patterns, but it should not become an unrestricted no-code form builder.

The intended model is:

- reusable base template
- product-level configuration
- explicit extension points
- hardcoded customisation only where business rules are genuinely specific

### 3. Intake Truth Lives In Platform Storage

The platform database and submission storage are the source of truth for:

- received inputs
- workflow status
- review outcome
- export data
- audit history

External websites and GA4 may provide traffic and attribution signals, but not submission truth.

### 4. Domain Logic Is Shared

Forms must use the shared domain-resolution rules.

This feature must not invent a separate domain-selection path outside the accepted shared domain architecture.

The shared foundation design for this lives in:

- [Domain Resolution Architecture](/Users/dataphytefoundation/Herd/mailserver/docs/features/shared-platform-foundations/domain-resolution-architecture.md)

## Analytics Boundary

Use `GA4` here only for:

- public form page traffic
- form-step or page conversion monitoring
- acquisition attribution for form submissions

Do not use GA4 as the source of truth for submission status, reviewer workflow, eligibility outcomes, or stored submission data.

## Current-State Reference

Useful current-state implementation docs:

- [Subscription Forms](/Users/dataphytefoundation/Herd/mailserver/docs/operations/subscription-forms.md)
- [How To Use Subscribe Endpoint](/Users/dataphytefoundation/Herd/mailserver/docs/guides/how-to-use-subscribe-endpoint.md)
- [Tracking And Webhooks](/Users/dataphytefoundation/Herd/mailserver/docs/integrations/tracking-webhooks.md)

## Target State

### Form Template Families

The platform should support at least these reusable template families:

- `subscription_basic`
  - standard newsletter signup
  - identity fields plus one preference field
- `subscription_segmented`
  - newsletter signup with multiple segment or frequency choices
  - optional consent and profile enrichment fields
- `application_basic`
  - structured application intake with review workflow
- `application_screened`
  - application intake with hardcoded eligibility or routing rules
- `data_collection_basic`
  - structured data capture without subscriber intent
- `data_collection_reviewed`
  - structured data capture that requires operator review or internal validation

Template families define:

- base field patterns
- validation expectations
- review expectations
- allowed customisation points
- expected export shape

### Customisation Boundary

Allowed configurable customisation:

- display copy
- field labels and help text
- optional fields
- preference options
- brand colour and logo
- confirmation content
- privacy and consent copy
- success behaviour
- review requirement flags

Allowed hardcoded customisation:

- eligibility gates
- conditional routing rules
- custom normalization logic
- specialised confirmation behaviour
- organisation-specific post-submit workflows
- externally imposed operational rules

Hardcoded customisation should be used only when the reusable template model cannot express the required behaviour safely.

### Public Integration Model

Approved public integration patterns should be:

- hosted form page on the resolved platform domain chain
- schema fetch plus externally rendered UI on an approved external platform
- direct API-style submission to the platform endpoint from approved frontends
- iframe or hosted embed only when explicitly needed and compatible with product/domain policy

### External Platform Rule

External sites may render and submit forms, but they must not become the source of truth for:

- workflow state
- review outcome
- stored submission history
- subscriber audience assignment outcome

## Open Dependencies And Blockers

### Dependencies On Other Sessions

- Session 4:
  - subscriber lifecycle and preference state definitions
- Session 5:
  - newsletter-facing preference and product-audience interaction rules
- Session 2:
  - exact permission and review-role enforcement model
- shared foundations:
  - exact persistence model and group-scoping implementation
- domain architecture:
  - confirmation of any approved external platform exceptions for form surfaces

### Current Blockers

- exact permission slug definitions remain unresolved
- exact organisation/product persistence implementation remains unresolved
- exact group-to-scope implementation remains unresolved
- exact domain-management authority remains unresolved

## Implementation Readiness

This area is ready for downstream implementation planning at the feature-spec level.

It is not fully implementation-ready for persistence-sensitive, permission-sensitive, or domain-management-sensitive code until the coordinator confirms the unresolved dependencies above.

## Target-State Topics To Document Next

- allowed-platform rules and API/embed governance
- domain resolution for hosted forms, API responses, and preference flows
- reusable form template catalogue
- submission review workflow
- configurable versus hardcoded customisation rules
- data export and audit requirements
