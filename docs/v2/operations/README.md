# Operations and verification

This guide describes what the repository requires. It does not assert that a particular production host is configured correctly. Never run `migrate:fresh` to validate or repair this application.

## Queue and scheduler

- `RecomputeOrganisationEngagementJob` implements `ShouldQueue` and has a per-organisation unique key. The manual CP action dispatches one job for the selected organisation. The daily command queues each due organisation separately; `GroupEngagementService` processes groups and chunks subscribers by 250.
- The engagement job uses the configured **default queue**. Campaign dispatch/stats jobs use `campaigns`, individual sends use `emails`, and provider webhooks use `webhooks`. Workers must consume every queue used in the target environment; workers restricted to `campaigns,emails,webhooks` do not consume engagement jobs on `default`.
- `.env.example` uses `QUEUE_CONNECTION=sync`, which executes jobs in the requesting process. An asynchronous production setup needs a working `database` or `redis` queue connection and worker(s) listening to the relevant queues. The repository has a `jobs` table migration and Redis connection definition; that does not prove the selected driver, migrated table, or worker processes on production.
- `routes/console.php` registers five-minute scheduled campaign dispatch/finalisation, hourly recent stats sync, daily deep stats sync at 02:00, and due-engagement dispatch at 03:30. The host must run Laravel's scheduler. Verify the host timezone and actual scheduler/worker state rather than assuming a code schedule runs itself.
- Engagement recalculation currently runs all groups for one organisation inside one job. It is **not** fanned out into one job per group; large organisations need measured queue timeout/retry and runtime checks.

## Deployment checks

1. Confirm the deployed commit and environment-specific configuration, including `QUEUE_CONNECTION`, database/Redis connectivity, mail provider settings, `APP_URL`, public host routing, and `CORS_ALLOWED_ORIGINS`. Do not print secrets into tickets or docs.
2. Check pending migrations with `php artisan migrate:status`, then apply reviewed migrations through the approved deployment process. Never replace this with `migrate:fresh` or a data reset.
3. Confirm the scheduler is active and workers listen to `default`, `campaigns`, `emails`, and `webhooks` as applicable. Check job failures and queue backlog, not just process existence.
4. Confirm organisation/product records, scoped operator grants, public audience and rating subgroups, form publication, checked-in blueprints, banners, and their migrations/provisioning in the target database. Provisioning commands have different effects and should be previewed and reviewed before `--apply`.
5. Verify each public hostname resolves and terminates TLS to this app before relying on generated form, preference, unsubscribe, browser-view, or archive URLs. Check CORS separately for website-to-mailserver integrations.
6. Run a safe test flow using controlled addresses and a non-production audience: hosted signup, immediate active/New-Unrated membership, preference/unsubscribe links, test email, CP preview, eligible campaign send, webhook status, and analytics reconciliation. Do not send to a live audience as a deployment smoke test.

## Local and automated verification

- Targeted tests: `php artisan test tests/Feature/ProductFormPlatformTest.php tests/Feature/SubscriptionFormControllerTest.php tests/Feature/NewsletterEngagementRatingsTest.php tests/Feature/AudienceGrowthTest.php tests/Feature/AnalyticsCampaignPaginationTest.php`.
- `php artisan schedule:list` shows registered schedules; it does not prove the host runs them. `php artisan queue:failed` shows recorded failures for the configured queue backend; inspect the actual worker manager for liveness.
- Local `.env` may use `sync` even when production uses real workers. Do not infer production behavior from local `npm run dev` or a locally passing feature test.

See [Analytics](../analytics/README.md) for reporting gaps and [Platform](../platform/README.md) for domain/scope boundaries.
