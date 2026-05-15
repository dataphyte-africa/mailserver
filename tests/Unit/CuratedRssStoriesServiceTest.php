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
}
