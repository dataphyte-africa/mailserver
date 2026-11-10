<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Platform\NewsletterAssetContainers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Statamic\Facades\AssetContainer;

class ProvisionAssetContainers extends Command
{
    protected $signature = 'newsletter:provision-asset-containers {--sync-blueprints : Apply checked-in newsletter blueprints after creating containers}';

    protected $description = 'Create isolated newsletter image containers without changing existing assets';

    public function handle(): int
    {
        foreach (NewsletterAssetContainers::DEFINITIONS as $handle => $definition) {
            $disk = config("filesystems.disks.{$handle}");
            if (! is_array($disk) || ! isset($disk['root'])) {
                $this->error("Missing filesystem disk: {$handle}");
                return self::FAILURE;
            }

            $container = AssetContainer::find($handle);
            if ($container && $container->diskHandle() !== $handle) {
                $this->error("{$handle} already exists with a different disk; no changes made to it.");
                return self::FAILURE;
            }

            File::ensureDirectoryExists($disk['root']);
            if (! $container) {
                AssetContainer::make($handle)->title($definition['title'])->disk($handle)->save();
                $this->info("Created {$handle}");
            } else {
                $this->line("Already exists: {$handle}");
            }
        }

        if ($this->option('sync-blueprints')) {
            foreach ([
                'insight_newsletters' => ['insight_updates', 'data_dive', 'marina_maitama', 'senorrita', 'pocket_science'],
                'foundation_newsletters' => ['project_update', 'weekly', 'activities', 'foundation_newsletters', 'policy_point'],
                'academy_newsletters' => ['datalab'],
                'policy_point_newsletters' => ['policy_point'],
            ] as $collection => $blueprints) {
                foreach ($blueprints as $blueprint) {
                    if (Artisan::call('newsletter:sync-checked-in-blueprint', [
                        'collection' => $collection,
                        'blueprint' => $blueprint,
                    ]) !== self::SUCCESS) {
                        $this->error("Could not sync {$collection}.{$blueprint}: ".Artisan::output());
                        return self::FAILURE;
                    }
                    $this->line(trim(Artisan::output()));
                }
            }
        }

        return self::SUCCESS;
    }
}
