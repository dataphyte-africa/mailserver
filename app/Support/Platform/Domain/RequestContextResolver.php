<?php

namespace App\Support\Platform\Domain;

use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\RequestContextResolverInterface;

class RequestContextResolver implements RequestContextResolverInterface
{
    public function __construct(
        private readonly DomainResolverInterface $domainResolver,
    ) {}

    public function resolve(string $host, string $path = '/'): array
    {
        return $this->domainResolver->resolveRequestContext($host, $path);
    }
}
