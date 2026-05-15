<?php

namespace Tests\Unit;

use App\Services\Newsletter\RssFeedService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RssFeedServiceTest extends TestCase
{
    public function test_it_parses_expected_fields_from_an_rss_feed(): void
    {
        Http::fake([
            'https://dataphyte.com/rss/policy_point.xml' => Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:insight="https://dataphyte.com/ns/insight">
    <channel>
        <item>
            <title>Policy Point Story</title>
            <link>https://dataphyte.com/policy-point/story</link>
            <guid isPermaLink="true">https://dataphyte.com/policy-point/story</guid>
            <pubDate>Fri, 15 May 2026 12:00:00 +0100</pubDate>
            <dc:creator>Jane Doe</dc:creator>
            <description>Policy summary for readers.</description>
            <media:content url="https://dataphyte.com/assets/policy-point.jpg" medium="image" />
            <insight:collection>policy_point</insight:collection>
            <insight:primaryTaxonomyHandle>policy_point_categories</insight:primaryTaxonomyHandle>
            <insight:primaryTaxonomySlug>governance</insight:primaryTaxonomySlug>
            <insight:primaryTaxonomyTitle>Governance</insight:primaryTaxonomyTitle>
            <insight:primaryTaxonomyUrl>https://dataphyte.com/policy_point/policy-point-categories/governance</insight:primaryTaxonomyUrl>
            <category>Governance</category>
        </item>
    </channel>
</rss>
XML, 200, ['Content-Type' => 'application/rss+xml']),
        ]);

        $items = app(RssFeedService::class)->items('https://dataphyte.com/rss/policy_point.xml', 6);

        $this->assertCount(1, $items);
        $this->assertSame('Policy Point Story', $items[0]['title']);
        $this->assertSame('https://dataphyte.com/policy-point/story', $items[0]['url']);
        $this->assertSame('Jane Doe', $items[0]['author']);
        $this->assertSame('https://dataphyte.com/assets/policy-point.jpg', $items[0]['image_url']);
        $this->assertSame('policy_point', $items[0]['collection']);
        $this->assertSame('governance', $items[0]['primary_taxonomy']['slug']);
        $this->assertSame('Governance', $items[0]['primary_taxonomy']['title']);
    }
}
