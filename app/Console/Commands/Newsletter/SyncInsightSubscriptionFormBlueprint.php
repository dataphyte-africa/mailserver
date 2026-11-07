<?php

namespace App\Console\Commands\Newsletter;

use Illuminate\Console\Command;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form;
use Symfony\Component\Yaml\Yaml;

class SyncInsightSubscriptionFormBlueprint extends Command
{
    protected $signature = 'newsletter:sync-insight-subscription-form {--apply : Update the Statamic form blueprint}';

    protected $description = 'Sync the checked-in Insight subscription fields, including organisation and consent.';

    public function handle(): int
    {
        if (! Form::find('insight_subscribe')) {
            $this->error('The Insight subscription form does not exist. No changes made.');

            return self::FAILURE;
        }

        $source = resource_path('blueprints/forms/insight_subscribe.yaml');
        $contents = is_file($source) ? Yaml::parseFile($source) : null;
        if (! is_array($contents) || ! isset($contents['tabs'])) {
            $this->error('The checked-in Insight form blueprint is missing or invalid. No changes made.');

            return self::FAILURE;
        }

        $this->line('Insight form fields will include Organisation and required subscription consent.');
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply to sync the blueprint.');

            return self::SUCCESS;
        }

        $blueprint = Blueprint::find('forms.insight_subscribe')
            ?? Blueprint::make('insight_subscribe')->setNamespace('forms');
        $blueprint->setContents($contents)->save();
        $this->info('Insight subscription form blueprint synced. Existing submissions were not changed.');

        return self::SUCCESS;
    }
}
