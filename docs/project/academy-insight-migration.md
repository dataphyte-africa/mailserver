# Academy and Insight Updates migration

The v2 branch includes the Academy `datalab` blueprint/template from `main` and the Insight `insight_updates` blueprint/template. Academy is its own organisation; Insight Updates belongs to the existing Dataphyte Insight organisation.

For each environment, back up the database and deploy code. If Academy is missing from Statamic, scaffold the collection in a fresh environment first. On an existing environment, sync only these two checked-in blueprints so database-backed definitions receive the v2 fields without rewriting entries. Do not use `newsletter:scaffold --fresh` on an environment with newsletter entries.

```sh
php artisan newsletter:sync-checked-in-blueprint academy_newsletters datalab
php artisan newsletter:sync-checked-in-blueprint insight_newsletters insight_updates
php artisan platform:sync-newsletter-products --dry-run --collection=academy_newsletters
php artisan platform:sync-newsletter-products --dry-run --collection=insight_newsletters
php artisan platform:sync-newsletter-products --collection=academy_newsletters
php artisan platform:sync-newsletter-products --collection=insight_newsletters
php artisan newsletter:backfill-historical-ownership --collection=academy_newsletters --json
```

Review the Academy backfill report before applying it. It assigns only unowned groups and campaigns when the collection has exactly one active product. Do not apply if the reported IDs or targets differ from the approved mapping.

```sh
php artisan newsletter:backfill-historical-ownership --collection=academy_newsletters --apply --json
```

On local MySQL this created Academy/DataLab and Insight Updates, then assigned Academy's existing group `12` and draft campaign `20` to the new Academy organisation/product. The existing Academy entry was not rewritten. No subscriber memberships, public preference settings, operator access, domains, or live deliveries were changed. Production IDs and counts may differ; repeat the dry-run review there before mutation.
