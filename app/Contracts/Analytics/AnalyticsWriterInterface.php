<?php

namespace App\Contracts\Analytics;

interface AnalyticsWriterInterface
{
    /**
     * Record a canonical newsletter analytics event.
     */
    public function recordNewsletterEvent(string $eventName, array $payload = []): void;

    /**
     * Record a canonical subscriber or audience analytics event.
     */
    public function recordSubscriberEvent(string $eventName, array $payload = []): void;

    /**
     * Record a canonical submission analytics event.
     */
    public function recordSubmissionEvent(string $eventName, array $payload = []): void;

    /**
     * Rebuild or refresh read-optimised analytics models.
     */
    public function rebuildReadModels(array $filters = []): void;
}
