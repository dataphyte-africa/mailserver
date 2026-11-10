<?php

namespace App\Policies;

use App\Services\Platform\NewsletterAssetContainers;
use Statamic\Policies\AssetPolicy;

class ScopedAssetPolicy extends AssetPolicy
{
    public function view($user, $asset)
    {
        return app(NewsletterAssetContainers::class)->canUse($user, $asset->containerHandle());
    }

    public function store($user, $assetContainer)
    {
        return app(NewsletterAssetContainers::class)->canUse($user, $assetContainer->handle());
    }
}
