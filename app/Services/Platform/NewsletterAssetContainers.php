<?php

namespace App\Services\Platform;

use App\Models\Organisation;
use Illuminate\Contracts\Auth\Authenticatable;

class NewsletterAssetContainers
{
    public const DEFINITIONS = [
        'newsletter_assets_insight' => ['organisation' => 'insight-newsletters', 'title' => 'Dataphyte Insight Images'],
        'newsletter_assets_foundation' => ['organisation' => 'foundation-newsletters', 'title' => 'Dataphyte Foundation Images'],
        'newsletter_assets_academy' => ['organisation' => 'academy-newsletters', 'title' => 'Dataphyte Academy Images'],
        'newsletter_assets_policy_point' => ['organisation' => 'policy-point-newsletters', 'title' => 'Policy Point Images'],
    ];

    public function canUse(?Authenticatable $operator, string $handle): bool
    {
        $definition = self::DEFINITIONS[$handle] ?? null;
        if (! $definition || ! $operator) {
            return false;
        }

        $organisation = Organisation::query()->where('slug', $definition['organisation'])
            ->where('status', 'active')->first();

        return $organisation !== null && in_array(
            (int) $organisation->id,
            app(CpOperatorScope::class)->organisationIds($operator),
            true,
        );
    }
}
