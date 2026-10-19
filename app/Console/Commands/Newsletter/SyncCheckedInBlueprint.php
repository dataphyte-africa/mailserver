<?php

namespace App\Console\Commands\Newsletter;

use Illuminate\Console\Command;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Symfony\Component\Yaml\Yaml;

class SyncCheckedInBlueprint extends Command
{
    protected $signature = 'newsletter:sync-checked-in-blueprint {collection} {blueprint}';

    protected $description = 'Apply one checked-in newsletter blueprint to the Statamic repository without changing entries.';

    public function handle(): int
    {
        $collection = (string) $this->argument('collection');
        $handle = (string) $this->argument('blueprint');

        if (! preg_match('/^[a-z0-9_]+$/', $collection)
            || ! preg_match('/^[a-z0-9_]+$/', $handle)
            || ! array_key_exists($collection, config('newsletter.collections', []))) {
            $this->error('Use a configured newsletter collection and a valid blueprint handle.');

            return self::FAILURE;
        }

        if (! Collection::findByHandle($collection)) {
            $this->error("Collection does not exist: {$collection}. Scaffold it first in this environment.");

            return self::FAILURE;
        }

        $source = resource_path("blueprints/collections/{$collection}/{$handle}.yaml");
        if (! is_file($source)) {
            $this->error("Checked-in blueprint does not exist: {$collection}/{$handle}");

            return self::FAILURE;
        }

        $contents = Yaml::parseFile($source);
        if (! is_array($contents) || ! isset($contents['title'])) {
            $this->error("Checked-in blueprint is invalid: {$collection}/{$handle}");

            return self::FAILURE;
        }

        $namespace = "collections.{$collection}";
        $blueprint = Blueprint::find("{$namespace}.{$handle}")
            ?? Blueprint::make($handle)->setNamespace($namespace);
        $blueprint->setContents($contents)->save();

        $this->info("Synced {$namespace}.{$handle} without changing entries.");

        return self::SUCCESS;
    }
}
