# Local Organisation Newsletter Demo Data

Run from the `version/2` checkout after migrations and the organisation/product audience setup:

```bash
php artisan newsletter:seed-organisation-demos
```

The command runs only in `local` or `testing`. It uses the active Data Dive (Insight), Policy Point, and Weekly Update (Foundation) products and their non-archived `regular` subgroups. It creates two already-sent campaigns per product, 20 subscribers per product, 18 synthetic sends per campaign, and link-click data. No email is queued or sent. The fixture addresses use `@example.test`, campaign names begin `V2 Demo |`, and subscriber metadata contains `fixture: organisation-v2`.

Each product has 16 active, one bounced, one complained, one pending, and one unsubscribed subscriber. Pending and unsubscribed records have no campaign sends. The command is idempotent and aborts if a fixture email or campaign name belongs to a non-fixture record. Its writes are transactional.

To test in the CP, sign in as a super admin or an operator assigned to the relevant organisation, then inspect Campaigns, Subscribers, and Analytics. The last-30-days analytics view should show two campaigns for each product's organisation. Campaign detail should show delivery, opens, clicks, failures, and bounced recipients. Change the collection filter to compare Insight, Policy Point, and Foundation. The synthetic data should never be interpreted as provider delivery evidence or production traffic.
