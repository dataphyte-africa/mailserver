# Observer Violation Submission Platform Brief

## Purpose

Build a focused submission platform for documenting observer reports of voter intimidation or harassment in Osun State.

The first version should prioritise clean observer identification, accurate incident location, and reliable polling-unit selection from the Osun polling-unit mapping.

## Scope For First Review

- collect election observer details
- collect exact incident location
- restrict violation category to voter intimidation or harassment
- use Osun State polling-unit mapping for LGA, ward, and polling-unit selection
- keep the report workflow simple enough for field use

## Election Observer Profile

Each report should capture enough observer detail to verify source credibility while protecting identity.

- full name
- phone number
- email address
- observer organisation
- observer ID or deployment code
- assigned state
- assigned LGA
- assigned ward
- assigned polling unit
- observer role
- verification status

## Incident Location

Each incident should be tied to a precise election location.

- state
- LGA
- ward
- polling unit code
- polling unit name
- address or landmark
- GPS coordinates where available
- incident date
- incident time observed
- whether the incident is still ongoing

## Suggested Violation Category

For this first version, use only one category:

- voter intimidation or harassment

## Evidence

Evidence should be attached with consent and security controls.

- photos
- videos
- audio recordings
- documents
- witness statements
- links or external references
- evidence description
- evidence consent confirmation
- optional file metadata where available

Media upload should be implemented behind a storage abstraction so the platform can start with local/private storage and later switch to Cloudflare-backed media storage without changing the report workflow.

Storage requirements:

- evidence records should store storage disk, object path, original filename, MIME type, file size, checksum where available, and upload status
- application code should not hardcode local filesystem paths into report records
- public URLs should not be generated directly for sensitive evidence
- evidence downloads or previews should go through an authorised controller or signed temporary URL flow
- the first implementation may use a private local disk
- the storage boundary should remain compatible with Cloudflare R2 or another S3-compatible object store later
- environment configuration should decide the active evidence storage disk
- failed uploads should not create a completed report attachment without a recoverable status

## Osun Polling Unit Mapping

The platform should use a structured Osun polling-unit mapping so observers do not manually type LGA, ward, and polling-unit details.

Recommended selection flow:

1. Select state: fixed to `Osun`.
2. Select LGA.
3. Select ward filtered by the selected LGA.
4. Select polling unit filtered by the selected ward.
5. Save both polling unit code and polling unit name with the report.

The mapping should include:

- state
- LGA
- ward
- polling unit code
- polling unit name

Source for initial mapping:

- [Election Dataphyte Find My Polling Unit - Osun](https://election.dataphyte.com/find-my-polling-unit?state=31)

Local extracted CSV:

- `docs/artifacts/osun-polling-unit-mapping.csv`

## Minimum Submission Workflow

- observer opens the submission form
- observer confirms or enters profile details
- observer selects LGA, ward, and polling unit from the Osun mapping
- observer records incident date, time, and whether the incident is ongoing
- observer selects `voter intimidation or harassment`
- observer describes what happened
- observer attaches available evidence and confirms evidence consent
- observer submits the report
- system stores the report as `submitted`

## Suggested Report Statuses

- `submitted`
- `under_review`
- `verified`
- `rejected`
- `escalated`
- `archived`

## Review Notes For Team

- decide whether observer profiles will be preloaded or entered on each submission
- decide whether reports are limited to deployed observers only
- decide whether GPS capture should be mandatory or optional
- decide which evidence types are mandatory, optional, or deferred
- decide the first-release evidence storage disk, file-size limits, and allowed MIME types
- decide when to switch from private local storage to Cloudflare R2-compatible media storage
- decide who can view observer identity in the admin dashboard

## Assigned Development Session After Ideation Approval

Do not start this development session until the team approves this ideation brief.

### Session OVS-01. Osun Observer Violation Intake Foundation

```text
You are Session OVS-01 for the observer violation submission platform.

Before doing anything:
1. Confirm the active branch and git status.
2. Read /Users/dataphytefoundation/Herd/mailserver/docs/features/form-data-collection-platform/observer-violation-submission-platform.md
3. Read /Users/dataphytefoundation/Herd/mailserver/docs/artifacts/osun-polling-unit-mapping.csv
4. Do not broaden the violation categories beyond voter intimidation or harassment.
5. Do not implement unapproved workflow, public dashboard, analytics, or escalation features.

Task:
Build the first implementation slice for Osun observer violation intake after ideation approval.

Requirements:
- create the persistence model for observer violation reports
- support observer profile fields from the approved brief
- support incident location fields from the approved brief
- support only the violation category `voter intimidation or harassment`
- support evidence metadata fields and consent confirmation
- implement evidence storage through a configurable private storage disk boundary
- do not hardcode local paths so Cloudflare R2-compatible media storage can be added later
- import or expose the Osun polling-unit mapping for LGA, ward, and polling-unit selection
- save polling unit code and polling unit name with each report
- store new reports with status `submitted`
- add validation tests for required observer, location, violation, and evidence-consent fields
- keep implementation mobile-form friendly

Do not:
- start from newsletter campaign logic
- add multiple violation categories
- make observer identity public
- implement public publishing of verified reports
- expose sensitive evidence through public URLs
- add escalation rules unless separately approved
- weaken authentication or authorization to make testing easier

Return:
- session status: completed | blocked | needs_approval
- decisions made
- docs updated
- code updated
- dependencies discovered
- blockers discovered
- tests run
- what the coordinator or next session must do
```
