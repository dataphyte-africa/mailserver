<?php

namespace App\Contracts\Analytics;

interface AnalyticsEventStoreInterface
{
    /**
     * Persist a canonical analytics event when an event store is required.
     */
    public function append(string $eventFamily, string $eventName, array $payload = []): void;
}
