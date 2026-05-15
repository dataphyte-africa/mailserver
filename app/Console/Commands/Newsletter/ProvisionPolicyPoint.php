<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\PolicyPointProvisioner;
use Illuminate\Console\Command;

class ProvisionPolicyPoint extends Command
{
    protected $signature = 'newsletter:provision-policy-point';

    protected $description = 'Create or update the Policy Point collection, blueprint, form, and subscriber group directly in the current environment.';

    public function handle(PolicyPointProvisioner $provisioner): int
    {
        $result = $provisioner->provision();

        $this->table(
            ['Resource', 'Value'],
            [
                ['Collection', $result['collection']],
                ['Collection Blueprint', $result['blueprint']],
                ['Subscriber Group ID', (string) $result['group']],
                ['Form Blueprint', $result['form_blueprint']],
                ['Form', $result['form']],
            ]
        );

        $this->info('Policy Point provisioning complete.');

        return self::SUCCESS;
    }
}
