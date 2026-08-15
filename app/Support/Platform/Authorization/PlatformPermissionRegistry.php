<?php

namespace App\Support\Platform\Authorization;

use App\Contracts\Authorization\PermissionRegistryInterface;

class PlatformPermissionRegistry implements PermissionRegistryInterface
{
    /**
     * @param  array<string, array<int, string>>|null  $categories
     */
    public function __construct(
        private readonly ?array $categories = null,
    ) {}

    public function categories(): array
    {
        return $this->categories ?? (array) config('platform.authorization.permissions', PermissionSlugs::categories());
    }

    public function all(): array
    {
        return array_values(array_unique(array_merge(...array_values($this->categories()))));
    }

    public function has(string $slug): bool
    {
        return in_array($slug, $this->all(), true);
    }

    public function categoryFor(string $slug): ?string
    {
        foreach ($this->categories() as $category => $slugs) {
            if (in_array($slug, $slugs, true)) {
                return $category;
            }
        }

        return null;
    }

    public function slugsForCategory(string $category): array
    {
        return array_values($this->categories()[$category] ?? []);
    }
}
