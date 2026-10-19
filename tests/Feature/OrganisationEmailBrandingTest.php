<?php

namespace Tests\Feature;

use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\EntryModel;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Services\Newsletter\NewsletterBanner;
use Statamic\Facades\Collection as StatamicCollection;
use Statamic\Facades\Blueprint;
use Tests\TestCase;

class OrganisationEmailBrandingTest extends TestCase
{
    public function test_campaign_uses_organisation_profile_as_email_branding_fallback(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
            'logo_path' => 'organisation-branding/1/logo.png',
            'banner_path' => 'organisation-branding/1/banner.png',
            'brand_primary_color' => '#123456',
            'brand_secondary_color' => '#ABCDEF',
            'addresses' => [
                ['label' => 'Nigeria', 'address' => '1 Insight Road, Lagos'],
                ['label' => 'UK', 'address' => '34 Example Road, London'],
            ],
            'website_url' => 'https://insight.example.test',
            'social_links' => ['linkedin' => 'https://linkedin.example.test/insight'],
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Data Dive', 'slug' => 'data-dive',
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $campaign = Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection' => 'insight_newsletters', 'name' => 'Demo', 'subject' => 'Demo',
            'status' => 'draft',
        ]);
        $subscriber = Subscriber::factory()->create();

        $with = (new NewsletterMailable($campaign, $subscriber, 'demo'))->content()->with;

        $this->assertStringEndsWith('/storage/organisation-branding/1/logo.png', $with['collectionLogo']);
        $this->assertStringEndsWith('/storage/organisation-branding/1/banner.png', $with['heroImageUrl']);
        $this->assertSame('#123456', $with['headerColor']);
        $this->assertSame('https://insight.example.test', $with['footerConfig']['website_url']);
        $this->assertSame('https://linkedin.example.test/insight', $with['footerConfig']['social_links']['linkedin']);
        $this->assertSame($organisation->publicAddresses(), $with['footerConfig']['offices']);
    }

    public function test_product_banner_overrides_organisation_banner_and_can_fall_back_again(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'banner_path' => 'organisation-branding/1/banner.png',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Marina and Maitama',
            'slug' => 'marina-maitama', 'status' => 'active', 'product_type' => 'newsletter',
            'banner_path' => 'product-branding/1/banner.png',
        ]);
        $campaign = Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection' => 'insight_newsletters', 'name' => 'Demo', 'subject' => 'Demo', 'status' => 'draft',
        ]);
        $subscriber = Subscriber::factory()->create();

        $with = (new NewsletterMailable($campaign, $subscriber, 'demo'))->content()->with;
        $this->assertStringEndsWith('/storage/product-branding/1/banner.png', $with['heroImageUrl']);

        $product->update(['banner_path' => null]);
        $with = (new NewsletterMailable($campaign->fresh(), $subscriber, 'demo'))->content()->with;
        $this->assertStringEndsWith('/storage/organisation-branding/1/banner.png', $with['heroImageUrl']);
    }

    public function test_hero_image_link_only_accepts_https_urls(): void
    {
        $banner = app(NewsletterBanner::class);

        $this->assertSame('https://example.org/story', $banner->linkUrl('https://example.org/story'));
        $this->assertNull($banner->linkUrl('http://example.org/story'));
        $this->assertNull($banner->linkUrl('javascript:alert(1)'));
    }

    public function test_migrated_academy_template_receives_entry_cta_and_organisation_branding(): void
    {
        StatamicCollection::make('academy_newsletters')->title('Academy Newsletters')->save();
        Blueprint::make('datalab')->setNamespace('collections.academy_newsletters')
            ->setContents(['sections' => ['main' => ['fields' => []]]])->save();
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Academy', 'slug' => 'academy-newsletters', 'status' => 'active',
            'primary_collection_handle' => 'academy_newsletters', 'brand_primary_color' => '#0f766e',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'DataLab',
            'slug' => 'academy-newsletters-datalab', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'academy_newsletters', 'blueprint_handle' => 'datalab',
        ]);
        $entry = EntryModel::query()->create([
            'site' => 'default', 'collection' => 'academy_newsletters', 'blueprint' => 'datalab',
            'slug' => 'academy-issue', 'date' => '2026-10-19', 'published' => true,
            'data' => [
                'title' => 'Academy issue', 'subject' => 'Academy issue',
                'template' => 'emails.academy.datalab', 'content' => '<p>Academy update</p>',
                'cta_text' => 'Explore Academy', 'cta_url' => 'https://academy.dataphyte.com/data',
            ],
        ]);
        $campaign = Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'entry_id' => $entry->id, 'collection' => 'academy_newsletters',
            'name' => 'Academy issue', 'subject' => 'Academy issue', 'status' => 'draft',
        ]);

        $content = (new NewsletterMailable($campaign, Subscriber::factory()->create(), 'academy-test'))->content();

        $this->assertSame('emails.academy.datalab', $content->view);
        $this->assertSame('Explore Academy', $content->with['ctaText']);
        $this->assertSame('https://academy.dataphyte.com/data', $content->with['ctaUrl']);
        $this->assertSame('#0f766e', $content->with['headerColor']);
        $this->assertSame('emails.partials.academy.footer', $content->with['footerPartial']);
        $this->assertStringContainsString('Explore Academy', (new NewsletterMailable(
            $campaign, Subscriber::factory()->create(), 'academy-render-test'
        ))->render());
    }

    public function test_entry_one_time_image_and_link_override_product_default(): void
    {
        StatamicCollection::make('insight_newsletters')->title('Insight Newsletters')->save();
        Blueprint::make('marina_maitama')->setNamespace('collections.insight_newsletters')
            ->setContents(['sections' => ['main' => ['fields' => []]]])->save();
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Marina and Maitama',
            'slug' => 'marina-maitama', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters', 'blueprint_handle' => 'marina_maitama',
            'banner_path' => 'product-branding/1/banner.png',
        ]);
        $entry = EntryModel::query()->create([
            'site' => 'default', 'collection' => 'insight_newsletters',
            'blueprint' => 'marina_maitama', 'slug' => 'one-time-banner',
            'date' => '2026-10-19', 'published' => true,
            'data' => [
                'title' => 'One-time issue', 'subject' => 'One-time issue',
                'hero_image_override_url' => 'https://example.org/issue.png',
                'hero_image_link_url' => 'https://example.org/story',
            ],
        ]);
        $campaign = Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'entry_id' => $entry->id, 'collection' => 'insight_newsletters',
            'name' => 'Demo', 'subject' => 'Demo', 'status' => 'draft',
        ]);

        $with = (new NewsletterMailable($campaign, Subscriber::factory()->create(), 'demo'))->content()->with;
        $this->assertSame('https://example.org/issue.png', $with['heroImageUrl']);
        $this->assertSame('https://example.org/story', $with['heroImageLinkUrl']);
    }
}
