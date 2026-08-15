<?php

namespace App\Support\Platform\Analytics;

use App\Contracts\Analytics\AnalyticsReaderInterface;
use LogicException;

class DatabaseAnalyticsReader implements AnalyticsReaderInterface
{
    public function campaignSummary(int|string $campaignId): array
    {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }

    public function campaignPerformance(string $productKey, ?string $fromDate = null, ?string $toDate = null): array
    {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }

    public function subscriberSummary(string $productKey, ?string $status = null): array
    {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }

    public function audienceSegmentSummary(string $productKey): array
    {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }

    public function submissionSummary(
        string $scopeKey,
        ?string $mode = null,
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }

    public function productOverview(string $productKey, ?string $fromDate = null, ?string $toDate = null): array
    {
        throw new LogicException('Database analytics read models are not implemented yet.');
    }
}
