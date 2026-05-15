<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CuratedRssStoriesService
{
    public function __construct(
        private readonly RssFeedService $feeds,
    ) {}

    public function syncEntry(object $entry): void
    {
        $feedUrl = trim((string) ($entry->get('rss_feed_url') ?? ''));

        if ($feedUrl === '') {
            return;
        }

        $existing = collect(Arr::wrap($entry->get('rss_items')));
        $forceRefresh = (bool) $entry->get('refresh_rss_items');

        if ($existing->isNotEmpty() && ! $forceRefresh) {
            return;
        }

        $limit = (int) ($entry->get('rss_item_limit') ?: 6);
        $fetched = $this->feeds->items($feedUrl, $limit);

        if ($fetched === []) {
            if ($forceRefresh) {
                $entry->set('refresh_rss_items', false);
            }

            return;
        }

        $entry->set('rss_items', $this->mapFetchedItemsToRows($fetched));

        if ($forceRefresh) {
            $entry->set('refresh_rss_items', false);
        }
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

    private function mapFetchedItemsToRows(array $items): array
    {
        return collect($items)
            ->values()
            ->map(function (array $item, int $index) {
                $primaryTaxonomyTitle = Arr::get($item, 'primary_taxonomy.title');

                return [
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
