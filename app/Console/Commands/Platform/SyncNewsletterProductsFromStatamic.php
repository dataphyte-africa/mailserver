<?php

namespace App\Console\Commands\Platform;

use App\Services\Platform\StatamicNewsletterProductSyncService;
use Illuminate\Console\Command;

class SyncNewsletterProductsFromStatamic extends Command
{
    protected $signature = 'platform:sync-newsletter-products {--dry-run : Preview the organisation/product sync without writing}';

    protected $description = 'Sync relational organisations and products from configured Statamic newsletter collections and blueprints.';

    public function handle(StatamicNewsletterProductSyncService $sync): int
    {
        $result = $sync->sync((bool) $this->option('dry-run'));

        $this->line($result['dry_run'] ? 'Dry-run only. No records were changed.' : 'Sync completed.');

        $this->info('Organisations');
        foreach ($result['organisations'] as $organisation) {
            $this->line(sprintf(
                '- %s: %s (%s)',
                $organisation['action'],
                $organisation['name'],
                $organisation['handle'],
            ));
        }

        $this->info('Products');
        foreach ($result['products'] as $product) {
            $this->line(sprintf(
                '- %s: %s (%s / %s)',
                $product['action'],
                $product['name'],
                $product['collection_handle'],
                $product['blueprint_handle'],
            ));
        }

        return self::SUCCESS;
    }
}
