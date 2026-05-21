<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CuratedRssStoriesService
{
    private const STORY_SET = 'story';

    public function __construct(
        private readonly RssFeedService $feeds,
    ) {}

    public function syncEntry(object $entry): void
    {
        $this->syncPrimaryStories($entry);
        $this->syncListStories(
            $entry,
            feedUrlField: 'related_rss_feed_url',
            limitField: 'related_rss_item_limit',
            refreshField: 'refresh_related_rss_items',
            itemsField: 'related_rss_items',
            defaultLimit: 4,
        );
        $this->syncListStories(
            $entry,
            feedUrlField: 'recommended_rss_feed_url',
            limitField: 'recommended_rss_item_limit',
            refreshField: 'refresh_recommended_rss_items',
            itemsField: 'recommended_rss_items',
            defaultLimit: 4,
        );
    }

    public function preparedItems(?object $entry, array $fallbackFetchedItems = []): array
    {
        $stored = collect(Arr::wrap($entry?->get('rss_items')))
            ->map(fn ($row) => $this->normalizeStoredRow($row))
            ->filter(fn (array $item) => filled($item['title']) && filled($item['url']))
            ->values();

        $items = $stored->isNotEmpty()
            ? $stored
            : collect($fallbackFetchedItems)
                ->map(fn (array $item, int $index) => array_merge($item, [
                    'is_lead' => $index === 0,
                ]))
                ->values();

        if ($items->isEmpty()) {
            return [
                'items' => [],
                'lead' => null,
                'secondary' => [],
            ];
        }

        $lead = $items->first(fn (array $item) => (bool) ($item['is_lead'] ?? false))
            ?? $items->first();

        $secondary = $items
            ->reject(fn (array $item) => $item['url'] === $lead['url'])
            ->values()
            ->all();

        return [
            'items' => $items->all(),
            'lead' => $lead,
            'secondary' => $secondary,
        ];
    }

    public function preparedList(?object $entry, string $field, array $fallbackFetchedItems = [], int $limit = 4): array
    {
        $stored = collect(Arr::wrap($entry?->get($field)))
            ->map(fn ($row) => $this->normalizeStoredRow($row))
            ->filter(fn (array $item) => filled($item['title']) && filled($item['url']))
            ->values();

        if ($stored->isNotEmpty()) {
            return $stored->take($limit)->all();
        }

        return collect($fallbackFetchedItems)
            ->map(fn (array $item) => $this->normalizeStoredRow($item))
            ->filter(fn (array $item) => filled($item['title']) && filled($item['url']))
            ->take($limit)
            ->values()
            ->all();
    }

    private function syncPrimaryStories(object $entry): void
    {
        $this->syncListStories(
            $entry,
            feedUrlField: 'rss_feed_url',
            limitField: 'rss_item_limit',
            refreshField: 'refresh_rss_items',
            itemsField: 'rss_items',
            defaultLimit: 6,
            map: fn (array $fetched) => $this->mapFetchedItemsToRows($fetched),
        );
    }

    private function syncListStories(
        object $entry,
        string $feedUrlField,
        string $limitField,
        string $refreshField,
        string $itemsField,
        int $defaultLimit,
        ?callable $map = null,
    ): void {
        $feedUrl = trim((string) ($entry->get($feedUrlField) ?? ''));
        $existing = collect(Arr::wrap($entry->get($itemsField)));
        $forceRefresh = (bool) $entry->get($refreshField);

        if ($feedUrl === '') {
            if ($existing->isNotEmpty() && ! $this->rowsUseReplicatorShape($existing)) {
                $entry->set($itemsField, ($map ?? fn (array $items) => $this->mapFetchedItemsToListRows($items))($existing->all()));
            }

            return;
        }

        if ($existing->isNotEmpty() && ! $forceRefresh) {
            if (! $this->rowsUseReplicatorShape($existing)) {
                $entry->set($itemsField, ($map ?? fn (array $items) => $this->mapFetchedItemsToListRows($items))($existing->all()));
            }

            return;
        }

        $limit = (int) ($entry->get($limitField) ?: $defaultLimit);
        $fetched = $this->feeds->items($feedUrl, $limit);

        if ($fetched === []) {
            if ($forceRefresh) {
                $entry->set($refreshField, false);
            }

            return;
        }

        $entry->set($itemsField, ($map ?? fn (array $items) => $this->mapFetchedItemsToListRows($items))($fetched));

        if ($forceRefresh) {
            $entry->set($refreshField, false);
        }
    }

    private function mapFetchedItemsToRows(array $items): array
    {
        return collect($items)
            ->values()
            ->map(function (array $item, int $index) {
                $primaryTaxonomyTitle = Arr::get($item, 'primary_taxonomy.title');

                return [
                    'type' => self::STORY_SET,
                    'is_lead' => $index === 0,
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'image_url' => $item['image_url'] ?? '',
                    'excerpt' => $item['excerpt'] ?? '',
                    'author' => $item['author'] ?? '',
                    'published_label' => $item['published_label'] ?? '',
                    'primary_taxonomy_title' => $primaryTaxonomyTitle,
                    'primary_taxonomy' => [
                        'title' => $primaryTaxonomyTitle,
                    ],
                ];
            })
            ->all();
    }

    private function mapFetchedItemsToListRows(array $items): array
    {
        return collect($items)
            ->values()
            ->map(function (array $item) {
                $primaryTaxonomyTitle = Arr::get($item, 'primary_taxonomy.title');

                return [
                    'type' => self::STORY_SET,
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'image_url' => $item['image_url'] ?? '',
                    'excerpt' => $item['excerpt'] ?? '',
                    'author' => $item['author'] ?? '',
                    'published_label' => $item['published_label'] ?? '',
                    'primary_taxonomy_title' => $primaryTaxonomyTitle,
                    'primary_taxonomy' => [
                        'title' => $primaryTaxonomyTitle,
                    ],
                ];
            })
            ->all();
    }

    private function rowsUseReplicatorShape(Collection $rows): bool
    {
        return $rows->every(function (mixed $row) {
            if ($row instanceof \Statamic\Fields\Values) {
                $row = $row->all();
            } elseif ($row instanceof Collection) {
                $row = $row->all();
            } elseif (is_object($row) && method_exists($row, 'toArray')) {
                $row = $row->toArray();
            }

            return Arr::get(Arr::wrap($row), 'type') === self::STORY_SET;
        });
    }

    private function normalizeStoredRow(mixed $row): array
    {
        if ($row instanceof \Statamic\Fields\Values) {
            $row = $row->all();
        } elseif ($row instanceof Collection) {
            $row = $row->all();
        } elseif (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        }

        $row = Arr::wrap($row);

        return [
            'is_lead' => (bool) Arr::get($row, 'is_lead'),
            'title' => (string) Arr::get($row, 'title', ''),
            'url' => (string) Arr::get($row, 'url', ''),
            'image_url' => (string) Arr::get($row, 'image_url', ''),
            'excerpt' => (string) Arr::get($row, 'excerpt', ''),
            'author' => (string) Arr::get($row, 'author', ''),
            'published_label' => (string) Arr::get($row, 'published_label', ''),
            'primary_taxonomy_title' => $primaryTaxonomyTitle = (string) Arr::get($row, 'primary_taxonomy_title', Arr::get($row, 'primary_taxonomy.title', '')),
            'primary_taxonomy' => [
                'title' => $primaryTaxonomyTitle,
            ],
        ];
    }
}
