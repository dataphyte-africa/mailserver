<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RssFeedService
{
    public function items(?string $url, int $limit = 6): array
    {
        $url = is_string($url) ? trim($url) : '';

        if ($url === '') {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $cacheKey = 'newsletter:rss:' . sha1($url) . ':' . $limit;

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($url, $limit) {
            $response = Http::timeout(10)->accept('application/rss+xml, application/xml, text/xml')->get($url);

            if (! $response->successful()) {
                return [];
            }

            return $this->parseItems($response->body(), $limit);
        });
    }

    private function parseItems(string $xml, int $limit): array
    {
        try {
            $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        } catch (\Throwable) {
            return [];
        }

        if (! $feed) {
            return [];
        }

        $items = $feed->channel->item ?? $feed->item ?? [];
        $parsed = [];

        foreach ($items as $item) {
            $parsed[] = $this->parseItem($item);
        }

        return collect($parsed)
            ->filter(fn (array $item) => filled($item['title']) && filled($item['url']))
            ->take($limit)
            ->values()
            ->all();
    }

    private function parseItem(\SimpleXMLElement $item): array
    {
        $namespaces = $item->getNamespaces(true);
        $dc = isset($namespaces['dc']) ? $item->children($namespaces['dc']) : null;
        $media = isset($namespaces['media']) ? $item->children($namespaces['media']) : null;
        $insight = isset($namespaces['insight']) ? $item->children($namespaces['insight']) : null;

        $mediaContent = $media?->content;
        $mediaThumb = $media?->thumbnail;

        $categories = collect($item->category ?? [])
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->values()
            ->all();

        $publishedAt = $this->parseDate((string) $item->pubDate);

        return [
            'title' => $this->decodeText((string) $item->title),
            'url' => trim((string) $item->link),
            'guid' => trim((string) $item->guid),
            'published_at' => $publishedAt?->toIso8601String(),
            'published_label' => $publishedAt?->format('F j, Y'),
            'author' => $this->decodeText((string) ($dc?->creator ?? '')),
            'excerpt' => $this->excerpt((string) $item->description),
            'image_url' => $this->mediaUrl($mediaContent) ?: $this->mediaUrl($mediaThumb),
            'collection' => $this->decodeText((string) ($insight?->collection ?? '')),
            'primary_taxonomy' => [
                'handle' => trim((string) ($insight?->primaryTaxonomyHandle ?? '')),
                'slug' => trim((string) ($insight?->primaryTaxonomySlug ?? '')),
                'title' => $this->decodeText((string) ($insight?->primaryTaxonomyTitle ?? '')),
                'url' => trim((string) ($insight?->primaryTaxonomyUrl ?? '')),
            ],
            'categories' => collect($categories)
                ->map(fn (string $category) => $this->decodeText($category))
                ->all(),
        ];
    }

    private function mediaUrl(mixed $node): ?string
    {
        if (! $node) {
            return null;
        }

        $attributes = $node->attributes();
        $url = trim((string) ($attributes['url'] ?? ''));

        return $url !== '' ? $url : null;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function excerpt(string $value): string
    {
        $text = $this->decodeText(strip_tags($value));

        return Str::limit($text, 220);
    }

    private function decodeText(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
