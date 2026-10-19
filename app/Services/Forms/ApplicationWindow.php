<?php

namespace App\Services\Forms;

use Carbon\CarbonImmutable;

class ApplicationWindow
{
    public function closesAt(?string $value): ?CarbonImmutable
    {
        return filled($value) ? CarbonImmutable::parse($value, 'Africa/Lagos')->endOfMinute() : null;
    }

    public function isOpen(bool $accepting, ?string $closesAt): bool
    {
        if (! $accepting) {
            return false;
        }

        $deadline = $this->closesAt($closesAt);

        return ! $deadline || now('Africa/Lagos')->lessThanOrEqualTo($deadline);
    }
}
