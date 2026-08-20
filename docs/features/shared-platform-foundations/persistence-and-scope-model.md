# Persistence And Scope Model

## Purpose

Define the implementation-safe persistence and scope architecture for Mailserver `version/2`.

This document resolves the shared-foundation blockers that previously prevented downstream sessions from assuming a stable ownership and authorization model.

## Persistence Baseline

Use dedicated application tables for the shared platform ownership model.

Minimum entities that should exist as relational records:

- `organisations`
- `products`
- `organisation_user_scope`
- `product_user_scope`

Optional support entity:

- `statamic_group_scope_map`

## Ownership Records

### Organisations

Organisations are persisted because they own:

- products
- default domain and mail configuration
- compliance defaults
- operator scope boundaries

### Products

Products are persisted because they own:

- one primary public collection
- forms
- audience groups
- campaign ownership
- template ownership
- public domain overrides
- analytics attribution

## Scope Records

### Canonical Scope Layer

The canonical scope layer is relational, not naming-convention-based.

Use:

- `organisation_user_scope`
- `product_user_scope`

Recommended minimum fields:

`organisation_user_scope`

- `user_id`
- `organisation_id`
- `scope_role`
- `status`
- `granted_by`
- `created_at`
- `updated_at`

`product_user_scope`

- `user_id`
- `product_id`
- `scope_role`
- `status`
- `granted_by`
- `created_at`
- `updated_at`

### Statamic Groups

Statamic groups remain useful, but they are not the canonical persistence layer for authorization scope.

Use them for:

- team management
- CP grouping
- operator visibility
- optional mapping to a relational scope target

If mapped, use:

- `group_handle`
- `scope_type`
- `organisation_id`
- `product_id`

## Campaign Ownership Persistence

Campaigns are product-owned records.

Recommended architecture-level ownership keys:

- `product_id`
- `organisation_id`
- `created_by`

`organisation_id` may be denormalised for easier scoping and analytics queries.

## Template Ownership Persistence

Operator-managed templates are product-owned by default.

Recommended ownership keys:

- `product_id`
- `organisation_id`
- `created_by`

Shared code primitives may remain file-based or code-based, but any persisted reusable operator template should carry explicit ownership.

## Audience Ownership Persistence

Audience groups should be relationally tied to product ownership.

Recommended direction:

- top-level subscriber group carries `product_id`
- top-level subscriber group may also carry `organisation_id`
- subgroup inherits ownership from the parent group

## Permission Slug Model

Use stable snake_case slugs.

Recommended exact slugs:

- `platform_admin`
- `organisation_manage`
- `product_manage`
- `access_scope_manage`
- `newsletter_view`
- `newsletter_create`
- `newsletter_edit`
- `newsletter_review`
- `newsletter_approve`
- `newsletter_schedule`
- `newsletter_send`
- `newsletter_retry`
- `newsletter_archive`
- `form_view`
- `form_create`
- `form_edit`
- `form_publish`
- `submission_view`
- `submission_review`
- `submission_approve`
- `submission_export`
- `submission_close`
- `subscriber_view`
- `subscriber_manage`
- `audience_manage`
- `preference_manage`
- `analytics_view`
- `analytics_export`
- `domain_manage`
- `integration_manage`

## Implementation Rule

Permission slug grants do not replace scope checks.

Authorization requires:

1. permission slug
2. organisation scope
3. product scope where relevant
4. workflow state where relevant

## Still Deferred

This document does not settle:

- exact domain-verification authority workflow
- exact complained-subscriber recovery policy
- exact physical migration order for every dependent feature table

Those remain separate decisions, but they no longer block the shared persistence and scope baseline.
