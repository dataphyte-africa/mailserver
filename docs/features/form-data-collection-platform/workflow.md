# Form And Data Collection Platform Workflow

## Purpose

Define the canonical workflow for forms and structured data collection in Mailserver `version/2`.

This workflow must remain aligned with:

- subscriber lifecycle decisions from Session 4
- domain resolution rules from Session 3
- shared workflow and access rules from Session 2

## Workflow Families

The feature supports three workflow families:

- subscription
- application
- data_collection

## Shared Workflow Stages

All workflow families should pass through some subset of these common stages:

1. form definition prepared
2. public surface resolved
3. user submits data
4. input validated and normalized
5. submission stored
6. mode-specific pipeline executed
7. workflow status updated
8. operator review or follow-up occurs if required
9. exports and reporting remain available from platform truth

## Subscription Workflow

### Target Use

- newsletter signup
- preference capture
- subscriber enrichment

### Canonical Flow

1. operator configures a product-bound form
2. public form is served on the resolved domain chain
3. external frontend may fetch schema and render the UI
4. user submits identity and preference data
5. platform validates and normalizes input
6. submission is stored as an audit record
7. subscriber record is created or updated
8. preference selections are mapped to product audience subgroup memberships
9. signup email lifecycle is triggered
10. subscriber activation follows the shared lifecycle rule after delivery
11. operator and reporting surfaces can inspect the resulting outcome

### Notes

- the form submission remains auditable even when the subscriber already exists
- external frontend rendering must not bypass platform validation or stored submission history

## Application Workflow

### Target Use

- applications
- call-for-participants workflows
- reviewable operational intake

### Canonical Flow

1. operator configures a product-bound application form
2. any hardcoded eligibility or routing rules are attached to the form implementation
3. user submits structured application data
4. platform validates and normalizes input
5. submission is stored
6. initial submission state becomes `submitted`
7. operator or reviewer moves the record into `under_review`
8. workflow may move through `changes_requested`, `shortlisted`, `approved`, or `rejected`
9. approved or rejected outcomes are retained as platform truth
10. exports, audit history, and reporting remain available

### Notes

- optional subscriber linkage is allowed only when the product flow requires it
- do not assume every application produces or updates a newsletter subscriber

## Data Collection Workflow

### Target Use

- research intake
- internal operational collection
- structured partner or field data capture

### Canonical Flow

1. operator configures a product-bound data collection form
2. user submits structured inputs
3. platform validates and normalizes input
4. submission is stored
5. form policy determines whether review is required
6. if review is required, submission moves through the shared submission workflow
7. if review is not required, submission may be considered operationally complete after storage and any configured follow-up
8. exports and reporting remain available from platform storage

## Review Workflow

Review-capable forms should use the shared submission state vocabulary:

- `submitted`
- `under_review`
- `changes_requested`
- `shortlisted`
- `approved`
- `rejected`
- `withdrawn`
- `closed`
- `archived`

## Public Surface Workflow

### Hosted Form Page

- use resolved product-preferred domain chain
- use shared domain services

### External Schema Render

- external website fetches form schema
- external website renders presentation
- submission still posts to platform-managed endpoint

### API-Style Submission

- validation, normalization, submission storage, and mode-specific workflows remain platform responsibilities

## Audit Workflow

Every workflow family should preserve enough platform-side history to answer:

- what was submitted
- when it was submitted
- what workflow status it reached
- what operator or automation changed it
- what exports or downstream actions were taken

## Workflow Dependencies

This workflow still depends on coordinator-confirmed answers for:

- exact permission slugs
- exact persistence model
- exact product and group scoping implementation
- any external platform exception rules beyond the accepted baseline
