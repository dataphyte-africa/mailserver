<?php

namespace App\Contracts\Analytics;

interface AnalyticsReaderInterface
{
    /**
     * Return summary metrics for a single campaign.
     */
    public function campaignSummary(int|string $campaignId): array;

    /**
     * Return campaign performance metrics for a product within an optional range.
     */
    public function campaignPerformance(string $productKey, ?string $fromDate = null, ?string $toDate = null): array;

    /**
     * Return subscriber lifecycle metrics for a product, optionally filtered by status.
     */
    public function subscriberSummary(string $productKey, ?string $status = null): array;

    /**
     * Return audience and subgroup summary metrics for a product.
     */
    public function audienceSegmentSummary(string $productKey): array;

    /**
     * Return submission metrics for a scope, form mode, and workflow status.
     */
    public function submissionSummary(
        string $scopeKey,
        ?string $mode = null,
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array;

    /**
     * Return a product-level operational overview.
     */
    public function productOverview(string $productKey, ?string $fromDate = null, ?string $toDate = null): array;
}
