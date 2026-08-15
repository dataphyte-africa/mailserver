<?php

namespace App\Support\Platform\Analytics;

use App\Contracts\Analytics\AnalyticsReaderInterface;
use LogicException;

class UnavailableAnalyticsReader implements AnalyticsReaderInterface
{
    public function campaignSummary(int|string $campaignId): array
    {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }

    public function campaignPerformance(string $productKey, ?string $fromDate = null, ?string $toDate = null): array
    {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }

    public function subscriberSummary(string $productKey, ?string $status = null): array
    {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }

    public function audienceSegmentSummary(string $productKey): array
    {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }

    public function submissionSummary(
        string $scopeKey,
        ?string $mode = null,
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }

    public function productOverview(string $productKey, ?string $fromDate = null, ?string $toDate = null): array
    {
        throw new LogicException('The configured analytics reader backend is not available for version/2.');
    }
}
