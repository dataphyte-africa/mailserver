<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\FoundationObserverProvisioner;
use Illuminate\Console\Command;

class ProvisionOsunElectionObservers extends Command
{
    protected $signature = 'newsletter:provision-osun-election-observers';

    protected $description = 'Create or update the Foundation Osun election observer subgroup and application form.';

    public function handle(FoundationObserverProvisioner $provisioner): int
    {
        $result = $provisioner->provision();

        $this->table(
            ['Type', 'Value'],
            [
                ['Foundation Group ID', (string) $result['group']],
                ['Target Sub-group ID', (string) $result['target_sub_group']],
                ['Form Blueprint', $result['form_blueprint']],
                ['Form', $result['form']],
            ]
        );

        $this->info('Foundation Osun election observer form provisioned.');

        return self::SUCCESS;
    }
}
