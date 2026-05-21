<?php

namespace Tests\Unit;

use App\Services\Newsletter\CuratedRssStoriesService;
use Tests\TestCase;

class CuratedRssStoriesServiceTest extends TestCase
{
    public function test_it_prepares_stored_rows_with_lead_and_secondary_order(): void
    {
        $entry = new class
        {
            public function get(string $key): mixed
            {
                return match ($key) {
                    'rss_items' => [
                        [
                            'is_lead' => false,
                            'title' => 'Second Story',
                            'url' => 'https://example.com/second',
                            'primary_taxonomy_title' => 'Governance',
                        ],
                        [
                            'is_lead' => true,
                            'title' => 'Lead Story',
                            'url' => 'https://example.com/lead',
                            'primary_taxonomy_title' => 'Elections',
                        ],
                        [
                            'is_lead' => false,
                            'title' => 'Third Story',
                            'url' => 'https://example.com/third',
                            'primary_taxonomy_title' => 'Budget',
                        ],
                    ],
                    default => null,
                };
            }
        };

        $prepared = app(CuratedRssStoriesService::class)->preparedItems($entry);

        $this->assertSame('Lead Story', $prepared['lead']['title']);
        $this->assertSame('Elections', $prepared['lead']['primary_taxonomy']['title']);
        $this->assertCount(2, $prepared['secondary']);
        $this->assertSame('Second Story', $prepared['secondary'][0]['title']);
        $this->assertSame('Governance', $prepared['secondary'][0]['primary_taxonomy']['title']);
        $this->assertSame('Third Story', $prepared['secondary'][1]['title']);
    }

    public function test_it_falls_back_to_the_first_story_when_no_lead_is_flagged(): void
    {
        $entry = new class
        {
            public function get(string $key): mixed
            {
                return match ($key) {
                    'rss_items' => [
                        [
                            'is_lead' => false,
                            'title' => 'First Story',
                            'url' => 'https://example.com/first',
                        ],
                        [
                            'is_lead' => false,
                            'title' => 'Second Story',
                            'url' => 'https://example.com/second',
                        ],
                    ],
                    default => null,
                };
            }
        };

        $prepared = app(CuratedRssStoriesService::class)->preparedItems($entry);

        $this->assertSame('First Story', $prepared['lead']['title']);
        $this->assertCount(1, $prepared['secondary']);
        $this->assertSame('Second Story', $prepared['secondary'][0]['title']);
    }

    public function test_it_prepares_secondary_feed_lists_from_stored_rows(): void
    {
        $entry = new class
        {
            public function get(string $key): mixed
            {
                return match ($key) {
                    'related_rss_items' => [
                        [
                            'type' => 'story',
                            'title' => 'Related One',
                            'url' => 'https://example.com/related-one',
                        ],
                        [
                            'type' => 'story',
                            'title' => 'Related Two',
                            'url' => 'https://example.com/related-two',
                        ],
                    ],
                    default => null,
                };
            }
        };

        $items = app(CuratedRssStoriesService::class)->preparedList($entry, 'related_rss_items');

        $this->assertCount(2, $items);
        $this->assertSame('Related One', $items[0]['title']);
        $this->assertSame('Related Two', $items[1]['title']);
    }

    public function test_it_falls_back_to_fetched_rows_for_secondary_lists(): void
    {
        $entry = new class
        {
            public function get(string $key): mixed
            {
                return null;
            }
        };

        $items = app(CuratedRssStoriesService::class)->preparedList($entry, 'recommended_rss_items', [
            [
                'title' => 'Recommended One',
                'url' => 'https://example.com/recommended-one',
            ],
            [
                'title' => 'Recommended Two',
                'url' => 'https://example.com/recommended-two',
            ],
        ]);

        $this->assertCount(2, $items);
        $this->assertSame('Recommended One', $items[0]['title']);
        $this->assertSame('Recommended Two', $items[1]['title']);
    }
}
