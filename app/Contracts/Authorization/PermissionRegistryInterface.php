<?php

namespace App\Contracts\Authorization;

interface PermissionRegistryInterface
{
    /**
     * @return array<string, array<int, string>>
     */
    public function categories(): array;

    /**
     * @return array<int, string>
     */
    public function all(): array;

    public function has(string $slug): bool;

    public function categoryFor(string $slug): ?string;

    /**
     * @return array<int, string>
     */
    public function slugsForCategory(string $category): array;
}
