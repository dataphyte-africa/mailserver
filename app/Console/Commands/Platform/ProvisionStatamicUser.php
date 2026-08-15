<?php

namespace App\Console\Commands\Platform;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use Illuminate\Console\Command;
use InvalidArgumentException;
use LogicException;
use Statamic\Facades\User as StatamicUser;

class ProvisionStatamicUser extends Command
{
    protected $signature = 'platform:provision-statamic-user
                            {identifier : Statamic user ID or email address}
                            {--name= : Relational display name; defaults to the Statamic email}';

    protected $description = 'Provision or synchronise the relational identity linked to a Statamic operator';

    public function handle(StatamicUserIdentityBridgeInterface $bridge): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $operator = StatamicUser::find($identifier)
            ?? (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? StatamicUser::findByEmail($identifier) : null);

        if ($operator === null) {
            $this->components->error('No Statamic user matches the supplied identifier.');

            return self::FAILURE;
        }

        try {
            $user = $bridge->provision($operator, $this->option('name'));
        } catch (InvalidArgumentException|LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Statamic user %s is linked to relational user %d.',
            (string) $operator->getAuthIdentifier(),
            $user->getKey(),
        ));

        return self::SUCCESS;
    }
}
