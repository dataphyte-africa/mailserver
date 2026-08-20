<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Statamic\Contracts\Forms\Form as StatamicForm;
use Statamic\Facades\Form;

class HistoricalOwnershipAuditService
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'organisations',
        'products',
        'subscriber_groups',
        'subscriber_sub_groups',
        'subscriber_sub_group',
        'subscribers',
        'campaigns',
        'campaign_audiences',
        'email_templates',
    ];

    public function generate(): array
    {
        $before = $this->databaseFingerprint();
        $collections = $this->collectionMeta();
        $productCandidates = $this->productCandidates($collections);
        $groups = $this->groupRows();
        $subGroups = $this->subGroupRows();
        $campaigns = $this->campaignRows();
        $campaignAudiences = $this->campaignAudienceRows();
        $forms = $this->formRows($groups, $subGroups, $productCandidates, $collections);
        $subscribers = $this->subscriberAudit();
        $emailTemplates = $this->emailTemplateAudit($productCandidates, $collections);
        $after = $this->databaseFingerprint();

        $groupAudit = $this->groupAudit($groups, $productCandidates, $collections);
        $subGroupAudit = $this->subGroupAudit($subGroups, $groups, $groupAudit['rows_by_id']);
        $campaignAudit = $this->campaignAudit($campaigns, $productCandidates, $collections);
        $campaignAudienceAudit = $this->campaignAudienceAudit(
            $campaignAudiences,
            $campaignAudit['rows_by_id'],
            $groupAudit['rows_by_id'],
            $subGroups
        );

        $blockers = $this->blockers(
            $before,
            $after,
            $productCandidates,
            $groupAudit,
            $campaignAudit,
            $campaignAudienceAudit,
            $subscribers
        );

        return [
            'generated_at' => now()->toIso8601String(),
            'database' => [
                'fingerprint_before' => $before,
                'fingerprint_after' => $after,
                'unchanged' => $before === $after,
            ],
            'summary' => [
                'organisation_count' => $this->countTable('organisations'),
                'product_count' => $this->countTable('products'),
                'subscriber_group_count' => $this->countTable('subscriber_groups'),
                'subscriber_sub_group_count' => $this->countTable('subscriber_sub_groups'),
                'subscriber_count' => $this->countTable('subscribers'),
                'campaign_count' => $this->countTable('campaigns'),
                'campaign_audience_count' => $this->countTable('campaign_audiences'),
                'email_template_count' => $this->countTable('email_templates'),
            ],
            'mapping_sources' => [
                'configured_collections' => array_values($collections),
                'product_candidates' => array_values($productCandidates),
                'reliable_product_mapping_available' => collect($productCandidates)
                    ->isNotEmpty()
                    && collect($productCandidates)->every(fn (array $candidate) => $candidate['mapping_status'] === 'mappable'),
                'form_rows' => $forms,
            ],
            'records' => [
                'subscriber_groups' => [
                    'affected_count' => $groupAudit['affected_count'],
                    'rows' => array_values($groupAudit['rows']),
                ],
                'subscriber_sub_groups' => [
                    'affected_count' => $subGroupAudit['affected_count'],
                    'rows' => array_values($subGroupAudit['rows']),
                ],
                'campaigns' => [
                    'affected_count' => $campaignAudit['affected_count'],
                    'rows' => array_values($campaignAudit['rows']),
                ],
                'campaign_audiences' => $campaignAudienceAudit,
                'subscribers' => $subscribers,
                'email_templates' => $emailTemplates,
            ],
            'blockers' => $blockers,
            'safe_to_auto_backfill' => empty($blockers),
            'requires_mutation_approval' => true,
        ];
    }

    private function collectionMeta(): array
    {
        return collect(config('newsletter.collections', []))
            ->map(fn (array $config, string $handle) => [
                'collection_handle' => $handle,
                'label' => $config['label'] ?? $handle,
                'group_slug' => $config['group_slug'] ?? null,
                'group_name' => $config['group_name'] ?? null,
            ])
            ->keyBy('collection_handle')
            ->all();
    }

    private function productCandidates(array $collections): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('organisations')) {
            return collect($collections)
                ->map(fn (array $collection) => [
                    'collection_handle' => $collection['collection_handle'],
                    'candidate_count' => 0,
                    'active_candidate_count' => 0,
                    'mapping_status' => 'missing_product_mapping_source',
                    'candidates' => [],
                ])
                ->keyBy('collection_handle')
                ->all();
        }

        $products = DB::table('products as p')
            ->leftJoin('organisations as o', 'o.id', '=', 'p.organisation_id')
            ->orderBy('p.id')
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                'p.status',
                'p.organisation_id',
                'p.primary_collection_handle',
                'o.name as organisation_name',
                'o.slug as organisation_slug',
                'o.status as organisation_status',
            ])
            ->groupBy(fn ($row) => $row->primary_collection_handle ?: '__null__');

        return collect($collections)
            ->map(function (array $collection) use ($products) {
                $handle = $collection['collection_handle'];
                $rows = $products->get($handle, collect());
                $active = $rows->filter(function ($row) {
                    return ($row->status ?? null) === 'active'
                        && ($row->organisation_id !== null)
                        && ($row->organisation_status ?? null) === 'active';
                })->values();

                return [
                    'collection_handle' => $handle,
                    'candidate_count' => $rows->count(),
                    'active_candidate_count' => $active->count(),
                    'mapping_status' => match (true) {
                        $active->count() === 1 => 'mappable',
                        $active->isEmpty() => 'missing_product_mapping_source',
                        default => 'ambiguous_active_product_candidate',
                    },
                    'candidates' => $rows
                        ->map(fn ($row) => [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                            'slug' => $row->slug,
                            'status' => $row->status,
                            'organisation_id' => $row->organisation_id === null ? null : (int) $row->organisation_id,
                            'organisation_name' => $row->organisation_name,
                            'organisation_slug' => $row->organisation_slug,
                            'organisation_status' => $row->organisation_status,
                        ])
                        ->all(),
                ];
            })
            ->keyBy('collection_handle')
            ->all();
    }

    private function groupRows(): array
    {
        if (! Schema::hasTable('subscriber_groups')) {
            return [];
        }

        return DB::table('subscriber_groups')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'slug',
                'collection_handle',
                'organisation_id',
                'product_id',
                'archived_at',
                'created_at',
                'updated_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->keyBy('id')
            ->all();
    }

    private function subGroupRows(): array
    {
        if (! Schema::hasTable('subscriber_sub_groups')) {
            return [];
        }

        return DB::table('subscriber_sub_groups')
            ->orderBy('id')
            ->get([
                'id',
                'subscriber_group_id',
                'name',
                'slug',
                'archived_at',
                'created_at',
                'updated_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->keyBy('id')
            ->all();
    }

    private function campaignRows(): array
    {
        if (! Schema::hasTable('campaigns')) {
            return [];
        }

        return DB::table('campaigns')
            ->orderBy('id')
            ->get([
                'id',
                'collection',
                'name',
                'status',
                'organisation_id',
                'product_id',
                'created_at',
                'updated_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->keyBy('id')
            ->all();
    }

    private function campaignAudienceRows(): array
    {
        if (! Schema::hasTable('campaign_audiences')) {
            return [];
        }

        return DB::table('campaign_audiences')
            ->orderBy('id')
            ->get([
                'id',
                'campaign_id',
                'targetable_type',
                'targetable_id',
                'send_to_all',
                'created_at',
                'updated_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function groupAudit(array $groups, array $productCandidates, array $collections): array
    {
        $rows = [];
        $affectedCount = 0;

        foreach ($groups as $id => $group) {
            $mapping = $this->resolveCollectionMapping(
                $group['collection_handle'],
                $productCandidates,
                $collections
            );

            $row = [
                'id' => (int) $group['id'],
                'slug' => $group['slug'],
                'name' => $group['name'],
                'collection_handle' => $group['collection_handle'],
                'organisation_id' => $group['organisation_id'] === null ? null : (int) $group['organisation_id'],
                'product_id' => $group['product_id'] === null ? null : (int) $group['product_id'],
                'archived_at' => $group['archived_at'],
                'mapping_status' => $mapping['status'],
                'mapping_reason' => $mapping['reason'],
                'suggested_product_id' => $mapping['suggested_product_id'],
                'suggested_organisation_id' => $mapping['suggested_organisation_id'],
            ];

            if ($row['organisation_id'] === null || $row['product_id'] === null || $mapping['status'] !== 'mappable') {
                $affectedCount++;
            }

            $rows[$id] = $row;
        }

        return [
            'affected_count' => $affectedCount,
            'rows' => $rows,
            'rows_by_id' => $rows,
        ];
    }

    private function subGroupAudit(array $subGroups, array $groups, array $groupAuditRows): array
    {
        $rows = [];
        $affectedCount = 0;

        foreach ($subGroups as $id => $subGroup) {
            $group = $groups[$subGroup['subscriber_group_id']] ?? null;
            $groupAudit = $groupAuditRows[$subGroup['subscriber_group_id']] ?? null;
            $mappingStatus = $groupAudit['mapping_status'] ?? 'missing_parent_group';

            $row = [
                'id' => (int) $subGroup['id'],
                'slug' => $subGroup['slug'],
                'name' => $subGroup['name'],
                'subscriber_group_id' => (int) $subGroup['subscriber_group_id'],
                'parent_group_slug' => $group['slug'] ?? null,
                'parent_collection_handle' => $group['collection_handle'] ?? null,
                'parent_mapping_status' => $mappingStatus,
                'archived_at' => $subGroup['archived_at'],
            ];

            if ($mappingStatus !== 'mappable') {
                $affectedCount++;
            }

            $rows[$id] = $row;
        }

        return [
            'affected_count' => $affectedCount,
            'rows' => $rows,
        ];
    }

    private function campaignAudit(array $campaigns, array $productCandidates, array $collections): array
    {
        $rows = [];
        $affectedCount = 0;

        foreach ($campaigns as $id => $campaign) {
            $mapping = $this->resolveCollectionMapping(
                $campaign['collection'],
                $productCandidates,
                $collections
            );

            $row = [
                'id' => (int) $campaign['id'],
                'name' => $campaign['name'],
                'collection' => $campaign['collection'],
                'status' => $campaign['status'],
                'organisation_id' => $campaign['organisation_id'] === null ? null : (int) $campaign['organisation_id'],
                'product_id' => $campaign['product_id'] === null ? null : (int) $campaign['product_id'],
                'mapping_status' => $mapping['status'],
                'mapping_reason' => $mapping['reason'],
                'suggested_product_id' => $mapping['suggested_product_id'],
                'suggested_organisation_id' => $mapping['suggested_organisation_id'],
            ];

            if ($row['organisation_id'] === null || $row['product_id'] === null || $mapping['status'] !== 'mappable') {
                $affectedCount++;
            }

            $rows[$id] = $row;
        }

        return [
            'affected_count' => $affectedCount,
            'rows' => $rows,
            'rows_by_id' => $rows,
        ];
    }

    private function campaignAudienceAudit(
        array $campaignAudiences,
        array $campaignRows,
        array $groupRows,
        array $subGroups
    ): array {
        $missingTargets = [];
        $collectionMismatches = [];
        $ownershipBlocked = [];

        foreach ($campaignAudiences as $row) {
            $campaign = $campaignRows[$row['campaign_id']] ?? null;
            $target = $this->resolveAudienceTarget($row, $groupRows, $subGroups);
            $auditRow = [
                'id' => (int) $row['id'],
                'campaign_id' => (int) $row['campaign_id'],
                'campaign_name' => $campaign['name'] ?? null,
                'campaign_collection' => $campaign['collection'] ?? null,
                'targetable_type' => $row['targetable_type'],
                'targetable_id' => $row['targetable_id'] === null ? null : (int) $row['targetable_id'],
                'send_to_all' => (bool) $row['send_to_all'],
                'target_group_id' => $target['group_id'],
                'target_group_slug' => $target['group_slug'],
                'target_collection_handle' => $target['collection_handle'],
                'target_exists' => $target['exists'],
                'target_archived_at' => $target['archived_at'],
            ];

            if (! $target['exists']) {
                $missingTargets[] = $auditRow;
                continue;
            }

            if (($campaign['collection'] ?? null) !== ($target['collection_handle'] ?? null)) {
                $collectionMismatches[] = $auditRow;
            }

            if (
                ($campaign['organisation_id'] ?? null) === null
                || ($campaign['product_id'] ?? null) === null
                || ($target['group_organisation_id'] ?? null) === null
                || ($target['group_product_id'] ?? null) === null
            ) {
                $ownershipBlocked[] = $auditRow;
            }
        }

        return [
            'missing_targets_count' => count($missingTargets),
            'missing_targets' => $missingTargets,
            'collection_mismatch_count' => count($collectionMismatches),
            'collection_mismatches' => $collectionMismatches,
            'ownership_blocked_count' => count($ownershipBlocked),
            'ownership_blocked_rows' => $ownershipBlocked,
        ];
    }

    private function subscriberAudit(): array
    {
        if (! Schema::hasTable('subscribers') || ! Schema::hasTable('subscriber_sub_group')) {
            return [
                'status_counts' => [],
                'without_any_membership_count' => 0,
                'without_any_membership' => [],
                'without_active_membership_count' => 0,
                'without_active_membership' => [],
            ];
        }

        $statusCounts = DB::table('subscribers')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->count])
            ->all();

        $withoutAnyMembership = DB::table('subscribers as s')
            ->leftJoin('subscriber_sub_group as pivot', 'pivot.subscriber_id', '=', 's.id')
            ->whereNull('pivot.subscriber_id')
            ->orderBy('s.id')
            ->get(['s.id', 's.email', 's.status', 's.created_at', 's.updated_at'])
            ->map(fn ($row) => (array) $row)
            ->all();

        $withoutActiveMembership = DB::table('subscribers as s')
            ->leftJoin('subscriber_sub_group as pivot', function ($join) {
                $join->on('pivot.subscriber_id', '=', 's.id')
                    ->whereNull('pivot.unsubscribed_at');
            })
            ->whereNull('pivot.subscriber_id')
            ->orderBy('s.id')
            ->get(['s.id', 's.email', 's.status', 's.created_at', 's.updated_at'])
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'status_counts' => $statusCounts,
            'without_any_membership_count' => count($withoutAnyMembership),
            'without_any_membership' => $withoutAnyMembership,
            'without_active_membership_count' => count($withoutActiveMembership),
            'without_active_membership' => $withoutActiveMembership,
        ];
    }

    private function emailTemplateAudit(array $productCandidates, array $collections): array
    {
        if (! Schema::hasTable('email_templates')) {
            return [
                'affected_count' => 0,
                'rows' => [],
            ];
        }

        $rows = DB::table('email_templates')
            ->orderBy('id')
            ->get(['id', 'name', 'collection', 'organisation_id', 'product_id'])
            ->map(function ($row) use ($productCandidates, $collections) {
                $mapping = $this->resolveCollectionMapping(
                    $row->collection ?? null,
                    $productCandidates,
                    $collections
                );

                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'collection' => $row->collection ?? null,
                    'organisation_id' => $row->organisation_id === null ? null : (int) $row->organisation_id,
                    'product_id' => $row->product_id === null ? null : (int) $row->product_id,
                    'mapping_status' => $mapping['status'],
                    'mapping_reason' => $mapping['reason'],
                ];
            })
            ->all();

        $affectedCount = collect($rows)
            ->filter(fn (array $row) => $row['organisation_id'] === null || $row['product_id'] === null)
            ->count();

        return [
            'affected_count' => $affectedCount,
            'rows' => $rows,
        ];
    }

    private function formRows(
        array $groups,
        array $subGroups,
        array $productCandidates,
        array $collections
    ): array {
        return Form::all()
            ->map(function (StatamicForm $form) use ($groups, $subGroups, $productCandidates, $collections) {
                $groupId = $this->nullableInt($form->get('newsletter_group'));
                $group = $groupId === null ? null : ($groups[$groupId] ?? null);
                $mapping = $group === null
                    ? ['status' => 'missing_group', 'reason' => 'newsletter_group does not resolve to an existing subscriber_groups row.', 'suggested_product_id' => null, 'suggested_organisation_id' => null]
                    : $this->resolveCollectionMapping($group['collection_handle'], $productCandidates, $collections);

                $targetSlug = $form->get('newsletter_target_sub_group_slug');
                $targetSubGroup = collect($subGroups)
                    ->first(fn (array $subGroup) => $groupId !== null
                        && (int) $subGroup['subscriber_group_id'] === $groupId
                        && $subGroup['slug'] === $targetSlug);

                return [
                    'handle' => $form->handle(),
                    'title' => $form->title(),
                    'submission_mode' => $form->get('newsletter_submission_mode') ?: 'subscription',
                    'newsletter_group' => $groupId,
                    'group_slug' => $group['slug'] ?? null,
                    'collection_handle' => $group['collection_handle'] ?? null,
                    'mapping_status' => $mapping['status'],
                    'mapping_reason' => $mapping['reason'],
                    'target_sub_group_slug' => $targetSlug,
                    'target_sub_group_exists' => $targetSlug ? $targetSubGroup !== null : null,
                    'target_sub_group_archived_at' => $targetSubGroup['archived_at'] ?? null,
                    'preference_field' => $form->get('newsletter_preference_field'),
                    'endpoint' => $form->get('newsletter_endpoint'),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveCollectionMapping(?string $collectionHandle, array $productCandidates, array $collections): array
    {
        if ($collectionHandle === null || $collectionHandle === '') {
            return [
                'status' => 'missing_collection_handle',
                'reason' => 'No collection handle is available for ownership resolution.',
                'suggested_product_id' => null,
                'suggested_organisation_id' => null,
            ];
        }

        if (! array_key_exists($collectionHandle, $collections)) {
            return [
                'status' => 'unknown_collection_handle',
                'reason' => 'Collection handle is not configured in newsletter collections.',
                'suggested_product_id' => null,
                'suggested_organisation_id' => null,
            ];
        }

        $candidate = $productCandidates[$collectionHandle] ?? null;

        if ($candidate === null || ($candidate['candidate_count'] ?? 0) === 0) {
            return [
                'status' => 'missing_product_mapping_source',
                'reason' => 'No product row exists with this primary collection handle.',
                'suggested_product_id' => null,
                'suggested_organisation_id' => null,
            ];
        }

        if (($candidate['mapping_status'] ?? null) !== 'mappable') {
            return [
                'status' => $candidate['mapping_status'],
                'reason' => match ($candidate['mapping_status']) {
                    'no_active_product_candidate', 'missing_product_mapping_source' => 'No active product row exists with this primary collection handle.',
                    'ambiguous_active_product_candidate' => 'More than one active product matches this collection handle.',
                    default => 'Product mapping source is not in a safe auto-backfill state.',
                },
                'suggested_product_id' => null,
                'suggested_organisation_id' => null,
            ];
        }

        $activeCandidate = collect($candidate['candidates'])->first(function (array $product) {
            return $product['status'] === 'active' && $product['organisation_status'] === 'active';
        });

        return [
            'status' => 'mappable',
            'reason' => 'Exactly one active product and active organisation match this collection handle.',
            'suggested_product_id' => $activeCandidate['id'] ?? null,
            'suggested_organisation_id' => $activeCandidate['organisation_id'] ?? null,
        ];
    }

    private function resolveAudienceTarget(array $campaignAudience, array $groupRows, array $subGroups): array
    {
        if ($campaignAudience['targetable_type'] === 'subscriber_group') {
            $group = $groupRows[$campaignAudience['targetable_id']] ?? null;

            return [
                'exists' => $group !== null,
                'group_id' => $group['id'] ?? null,
                'group_slug' => $group['slug'] ?? null,
                'group_organisation_id' => $group['organisation_id'] ?? null,
                'group_product_id' => $group['product_id'] ?? null,
                'collection_handle' => $group['collection_handle'] ?? null,
                'archived_at' => $group['archived_at'] ?? null,
            ];
        }

        if ($campaignAudience['targetable_type'] === 'subscriber_sub_group') {
            $subGroup = $subGroups[$campaignAudience['targetable_id']] ?? null;
            $group = $subGroup === null ? null : ($groupRows[$subGroup['subscriber_group_id']] ?? null);

            return [
                'exists' => $subGroup !== null && $group !== null,
                'group_id' => $group['id'] ?? null,
                'group_slug' => $group['slug'] ?? null,
                'group_organisation_id' => $group['organisation_id'] ?? null,
                'group_product_id' => $group['product_id'] ?? null,
                'collection_handle' => $group['collection_handle'] ?? null,
                'archived_at' => $subGroup['archived_at'] ?? null,
            ];
        }

        return [
            'exists' => false,
            'group_id' => null,
            'group_slug' => null,
            'group_organisation_id' => null,
            'group_product_id' => null,
            'collection_handle' => null,
            'archived_at' => null,
        ];
    }

    private function blockers(
        array $before,
        array $after,
        array $productCandidates,
        array $groupAudit,
        array $campaignAudit,
        array $campaignAudienceAudit,
        array $subscribers
    ): array {
        $blockers = [];

        if (($before['organisations']['row_count'] ?? 0) === 0) {
            $blockers[] = 'No organisation rows exist in the relational ownership layer.';
        }

        if (($before['products']['row_count'] ?? 0) === 0) {
            $blockers[] = 'No product rows exist in the relational ownership layer.';
        }

        if (! collect($productCandidates)->every(fn (array $candidate) => $candidate['mapping_status'] === 'mappable')) {
            $blockers[] = 'At least one newsletter collection lacks exactly one active product mapping source.';
        }

        if (($groupAudit['affected_count'] ?? 0) > 0) {
            $blockers[] = 'Historical subscriber groups still have null or unsafe ownership state.';
        }

        if (($campaignAudit['affected_count'] ?? 0) > 0) {
            $blockers[] = 'Historical campaigns still have null or unsafe ownership state.';
        }

        if (($campaignAudienceAudit['missing_targets_count'] ?? 0) > 0) {
            $blockers[] = 'Some campaign_audiences rows reference missing target groups or subgroups.';
        }

        if (($subscribers['without_any_membership_count'] ?? 0) > 0) {
            $blockers[] = 'Some subscribers have no audience membership rows and cannot be auto-remapped safely.';
        }

        if ($before !== $after) {
            $blockers[] = 'The pre and post dry-run database fingerprints differed during audit execution.';
        }

        return $blockers;
    }

    private function databaseFingerprint(): array
    {
        return collect($this->tables)
            ->mapWithKeys(fn (string $table) => [$table => $this->tableFingerprint($table)])
            ->all();
    }

    private function tableFingerprint(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [
                'exists' => false,
                'row_count' => 0,
            ];
        }

        $fingerprint = [
            'exists' => true,
            'row_count' => (int) DB::table($table)->count(),
        ];

        foreach (['created_at', 'updated_at', 'archived_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $fingerprint['max_'.$column] = DB::table($table)->max($column);
            }
        }

        return $fingerprint;
    }

    private function countTable(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
