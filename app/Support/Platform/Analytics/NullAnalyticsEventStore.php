<?php

namespace App\Support\Platform\Analytics;

use App\Contracts\Analytics\AnalyticsEventStoreInterface;

class NullAnalyticsEventStore implements AnalyticsEventStoreInterface
{
    public function append(string $eventFamily, string $eventName, array $payload = []): void
    {
        // Intentionally left blank until an event store is explicitly required.
    }
}
