<?php

namespace App\Contracts\Domain;

interface RequestContextResolverInterface
{
    /**
     * Resolve the current request into organisation, product, and surface context.
     *
     * @return array<string, mixed>
     */
    public function resolve(string $host, string $path = '/'): array;
}
