# Shared Platform Foundations Workflow

## Purpose

This workflow defines how the platform should handle ownership, permissions, and state transitions across features.

It is the cross-feature workflow baseline that later newsletter and form workflows must inherit from rather than override.

## Access Workflow

### 1. User Provisioning

1. create or invite the user
2. assign one or more roles
3. assign one or more groups
4. validate organisation and product scope
5. grant access only after both capability and scope rules are satisfied

### Scope Resolution

When evaluating scope:

1. load relational organisation scope for the user
2. load relational product scope for the user
3. optionally load mapped Statamic group context
4. evaluate whether the requested action falls within the persisted scope records
5. only then continue to workflow-state checks

### 2. Authorization Evaluation

Every sensitive action should be evaluated in this order:

1. is the action allowed by role
2. is the action allowed in the current organisation scope
3. is the action allowed in the current product scope
4. is the item in a workflow state that allows the action
5. if yes, allow
6. else, deny and log the attempt where appropriate

### Permission Evaluation Inputs

Authorization must not depend on role names alone.

It should evaluate:

- permission slug
- organisation scope
- product scope
- workflow state
- ownership of the target record where relevant

## Campaign Workflow

Primary state path:

`draft -> in_review -> approved -> scheduled/sending -> sent`

Return path:

`in_review -> changes_requested -> draft`

Exceptional paths:

- `sending -> partial`
- `sending -> failed`
- `approved -> cancelled`
- `sent -> archived`
- `partial -> archived`
- `failed -> archived`

## Submission Workflow

Primary state path:

`submitted -> under_review -> approved -> closed -> archived`

Alternative evaluation path:

`submitted -> under_review -> shortlisted -> approved`

Return path:

`under_review -> changes_requested -> under_review`

Exceptional paths:

- `submitted -> withdrawn`
- `under_review -> rejected`
- `approved -> closed`
- `rejected -> archived`
- `withdrawn -> archived`

## Handoff Rules Between Roles

- editor hands off to reviewer
- reviewer hands off to approver when approval is required
- approver hands off to sender for campaigns
- reviewer or approver hands off to operations for submission closure where needed
- analyst does not own workflow progression

## Cross-Feature Rule

Newsletter and form/data workflows must not define contradictory state names or bypass this shared access model without coordinator approval.
