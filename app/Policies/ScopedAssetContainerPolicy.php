<?php

namespace App\Policies;

use App\Services\Platform\NewsletterAssetContainers;
use Statamic\Policies\AssetContainerPolicy;

class ScopedAssetContainerPolicy extends AssetContainerPolicy
{
    public function view($user, $container)
    {
        return app(NewsletterAssetContainers::class)->canUse($user, $container->handle());
    }
}
