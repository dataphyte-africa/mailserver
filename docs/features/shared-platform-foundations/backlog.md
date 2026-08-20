# Shared Platform Foundations Backlog

## Session 2 Backlog

### Must Resolve Before Implementation

- define service-level authorization strategy that sits behind CP visibility
- define which workflow states are mandatory platform-wide and which are feature-specific extensions

### Should Resolve Soon After

- define audit log events for role changes and status transitions
- define override rules for organisation admins versus super admins
- define how delegated or temporary access should work
- define whether approval can be skipped for low-risk products and by what policy

### Downstream Dependencies

- newsletter platform spec depends on campaign workflow states
- form/data platform spec depends on submission workflow states
- implementation guardrails depend on exact permission and ownership boundaries

### Remaining Shared-Foundation Work

- define exact migration shape for organisation and product tables
- define exact migration shape for organisation and product scope tables
- define whether `organisation_id` is denormalised onto all product-owned records in the first implementation wave or introduced incrementally
