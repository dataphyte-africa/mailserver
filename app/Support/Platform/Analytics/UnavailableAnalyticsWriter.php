<?php

namespace App\Support\Platform\Analytics;

use App\Contracts\Analytics\AnalyticsWriterInterface;
use LogicException;

class UnavailableAnalyticsWriter implements AnalyticsWriterInterface
{
    public function recordNewsletterEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('The configured analytics writer backend is not available for version/2.');
    }

    public function recordSubscriberEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('The configured analytics writer backend is not available for version/2.');
    }

    public function recordSubmissionEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('The configured analytics writer backend is not available for version/2.');
    }

    public function rebuildReadModels(array $filters = []): void
    {
        throw new LogicException('The configured analytics writer backend is not available for version/2.');
    }
}
