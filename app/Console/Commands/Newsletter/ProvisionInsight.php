<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\InsightProvisioner;
use Illuminate\Console\Command;

class ProvisionInsight extends Command
{
    protected $signature = 'newsletter:provision-insight';

    protected $description = 'Create or update the Dataphyte Insight collection, blueprints, subscriber group, and subscription form.';

    public function handle(InsightProvisioner $provisioner): int
    {
        $result = $provisioner->provision();

        $this->table(
            ['Type', 'Value'],
            [
                ['Collection', $result['collection']],
                ['Blueprints', implode(', ', $result['blueprints'])],
                ['Group ID', (string) $result['group']],
                ['Form Blueprint', $result['form_blueprint']],
                ['Form', $result['form']],
            ]
        );

        $this->info('Insight newsletter assets provisioned.');

        return self::SUCCESS;
    }
}
