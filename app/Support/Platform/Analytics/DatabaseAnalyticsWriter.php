<?php

namespace App\Support\Platform\Analytics;

use App\Contracts\Analytics\AnalyticsWriterInterface;
use LogicException;

class DatabaseAnalyticsWriter implements AnalyticsWriterInterface
{
    public function recordNewsletterEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('Database analytics write models are not implemented yet.');
    }

    public function recordSubscriberEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('Database analytics write models are not implemented yet.');
    }

    public function recordSubmissionEvent(string $eventName, array $payload = []): void
    {
        throw new LogicException('Database analytics write models are not implemented yet.');
    }

    public function rebuildReadModels(array $filters = []): void
    {
        throw new LogicException('Database analytics write models are not implemented yet.');
    }
}
