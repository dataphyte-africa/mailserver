# Forms and subscription integration

## Subscription forms

Hosted organisation subscription forms expose:

| Method/path | Purpose |
| --- | --- |
| `GET /forms/{slug}` | Public hosted page |
| `GET /forms/{slug}/schema` | Public field and approved-preference contract for a website |
| `POST /forms/{slug}/submit` | Website integration submission endpoint |
| `POST /forms/{slug}/profile` | Session-bound optional reader-profile completion after hosted signup |
| `POST /forms/{slug}` | Compatibility submission path for existing clients |

The form's configured public product audiences determine selectable preferences. Submission revalidates the selection against organisation/product ownership and stores a form submission audit record. `SubscriberEnrollmentService` activates the subscriber and syncs audience rating during valid intake; signup or welcome mail is independent of activation. A new subscriber without send history starts New / Unrated. Subscription submissions do **not** enter an application review queue.

The hosted signup asks for email before newsletter preferences and consent. After browser submission, a one-time modal thanks the reader and offers the form's optional profile fields, with Save details and Skip for now. Saving updates the existing submission and subscriber profile; it does not resubscribe or send another welcome message. JSON submissions keep the single-request API contract and may still include profile fields. Name fields are optional for all subscription forms, including existing records whose stored definitions mark them required; configured required fields on custom forms still apply. Application forms keep their own requirements. Country options come from the shared `countries` table. The Foundation, Insight, Academy, and Policy Point hosted forms share the Dataphyte blue visual baseline until organisation-specific branding is configured; each retains its own copy, uploaded identity image, and visible public preferences. Do not assume every deployed form has identical optional fields or currently published preferences; read its schema.

Website clients should use the schema's endpoint rather than constructing URLs. A separate website origin requires the form's Allowed Embed Origins and compatible server CORS settings. DNS verification alone does not permit a cross-origin POST. Existing `/subscribe/{form}` and `/subscribe/{form}/schema` contracts remain for older clients; do not switch an integration by changing only its URL.

The hosted page and integration POST are distinct endpoints. Existing legacy API clients remain on their documented compatibility routes until migrated.

## Application and data-collection forms

The `ProductForm` platform also has application and data-collection modes. These store submissions and may use a review/status workflow; they do not create newsletter subscriptions as a side effect. The state-coordinator application and observer-violation flows have dedicated public and CP controllers and must not be treated as subscription forms. Their form windows, draft/resume, upload, and delivery-tracking behavior are feature-specific rather than universal form guarantees.

Evidence: `app/Services/Forms/ProductFormService.php`, `ProductFormSubscriptionService.php`, `app/Http/Controllers/Public/ProductFormPageController.php`, `ProductFormSubmissionController.php`, and `app/Providers/FormDataCollectionServiceProvider.php`. Focused coverage includes `tests/Feature/ProductFormPlatformTest.php` and the organisation-specific subscription-form tests.
