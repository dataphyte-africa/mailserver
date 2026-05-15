# Subscriber Management

---

## How Subscribers Enter the System

Subscribers can now enter the system through two paths:

1. **Public subscription forms** linked to a subscriber group
2. **Admin import/manual entry** in the Statamic CP

Public forms are the preferred intake path for active newsletter operations. Each form belongs to exactly one subscriber group, and that group belongs to exactly one newsletter collection.

| Source | Group | Sub-groups |
|---|---|---|
| Remote website subscription form | Selected explicitly on the form | Derived from the form's preference definitions |
| CSV import / manual admin entry | Selected by admin | Selected by admin |

Groups remain explicit DB records. Sub-groups may be created manually or provisioned from a form's preference definitions.

---

## Features

### Subscriber-Facing (in emails)
- Unsubscribe via signed URL (one-click, removes from all lists)
- Manage preferences via signed URL (toggle sub-group memberships)
- View in browser link

### Subscriber-Facing (at signup)
- Collection-linked subscribe endpoint
- Form schema can be defined in Statamic
- Destination website controls the presentation/styling
- Preference options on the form define the subscriber's sub-group membership
- Consent metadata stored on the subscriber record

### Admin (Statamic CP)
- Subscriber list with filters (group, sub-group, status)
- Add/edit individual subscribers manually
- **CSV import** (fallback / migration workflow)
- CSV export
- View subscriber send history
- Manage groups and sub-groups
- Create and manage collection-linked subscription forms

---

## Public Subscription Flow

1. Admin creates a subscriber group and links it to a newsletter collection
2. The form stores:
   - the target subscriber group
   - the derived collection
   - endpoint slug / public handle
   - input fields
   - preference field definitions
   - optional confirmation email branding/copy
3. Preference definitions are synced to `subscriber_sub_groups` for that selected parent group
4. A remote website fetches the form schema and renders it using its own CSS/markup
5. The website submits to this project's subscribe endpoint
6. The system:
   - validates the payload
   - creates or updates the `Subscriber`
   - assigns the selected parent subscriber group through one or more sub-groups
   - stores consent metadata (`ip_address`, `user_agent`, timestamps, selected preferences)
   - records per-submission outcome metadata on the saved Statamic submission

### Styling model

The destination website owns the form styling.

This project supplies:
- the form schema / field definitions
- validation rules
- audience mapping
- submit endpoint

### Group mapping rule

Each public form belongs to exactly one subscriber group. The collection is derived from that group.

Example:

| Form | Collection | Parent group | Derived sub-groups |
|---|---|---|---|
| `policy-point-subscribe` | `policy_point_newsletters` | `policy-point-subscribers` | `regular`, `monthly` |

---

## Import Flow

1. Admin downloads export from source website
2. Maps CSV columns to system fields (email, first_name, last_name, sub_groups)
3. Uploads CSV in CP
4. System validates rows, skips duplicates
5. Imports with `status = active` (consent already collected at source)
6. Assigns to correct sub-groups based on CSV data
7. Import report: imported count, skipped, errors

### Import CSV Format
```
email,first_name,last_name,sub_groups
john@example.com,John,Doe,"topics,senorrita"
jane@example.com,Jane,Smith,"weekly"
```

`sub_groups` is a comma-separated list of sub-group slugs.

---

## Opt-Out Flow

### One-Click Unsubscribe (RFC 8058)
Add headers to every outgoing email:
```
List-Unsubscribe: <https://yourdomain.com/unsubscribe/{signed-token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

### Unsubscribe Page
- Signed URL prevents tampering
- Option 1: Unsubscribe from specific sub-group
- Option 2: Unsubscribe from all (global unsubscribe)
- Sets `subscriber.status = unsubscribed` and `subscriber.unsubscribed_at = now()`
- Updates pivot table `unsubscribed_at` for relevant sub-groups

---

## Preference Center

Public page where subscribers manage their subscriptions.

### Access
Linked from every email footer. Accessed via signed URL:
```
https://yourdomain.com/preferences/{signed-token}
```

### Features
- Toggle sub-group memberships on/off
- View which groups/sub-groups they're subscribed to
- Update name/email
- Global unsubscribe option

### Frontend
- Alpine.js for toggling sub-group checkboxes
- Tailwind CSS for styling
- CSRF-protected form submission

The preference center continues to operate on the same `subscriber_sub_groups` records used by public signup forms.

---

## Submission Audit Trail

Saved Statamic form submissions now act as the audit trail for newsletter intake processing.

Each saved submission may include:

- `subscription_status`
- `email_sent`
- `subscriber_id`
- `subscriber_group_id`

This data is written after the subscriber pipeline runs. It lets admins confirm whether a submission:

- created a new subscriber
- hit an already-subscribed address
- updated an existing subscriber
- resubscribed a previously unsubscribed user
- triggered a confirmation email

---

## CSV Import/Export

### Import
- Upload CSV with columns: email, first_name, last_name, sub_groups
- Validate each row, skip duplicates
- Queue the import job for large files
- Report: imported count, skipped count, error details

### Export
- Filter by group/sub-group/status
- Columns: email, first_name, last_name, status, groups, subscribed_at
- Queue for large datasets, provide download link when ready

### Package
```
composer require maatwebsite/excel
```

---

## Subscriber Statuses

| Status | Description | Can Receive Email? |
|---|---|---|
| pending | Awaiting confirmation | No |
| active | Confirmed and subscribed | Yes |
| unsubscribed | User opted out | No |
| bounced | Hard bounce detected | No |
| complained | Marked as spam | No |

Only `active` subscribers are included in campaign sends. Status transitions are one-way (bounced/complained cannot be reactivated without manual admin action).
