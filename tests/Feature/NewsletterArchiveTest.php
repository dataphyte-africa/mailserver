<?php

namespace Tests\Feature;

use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\EntryModel;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection as StatamicCollection;
use Tests\TestCase;

class NewsletterArchiveTest extends TestCase
{
    public function test_archive_lists_only_sent_issues_for_the_selected_product(): void
    {
        [$organisation, $product, $entry] = $this->fixtures();
        $sent = $this->campaign($organisation, $product, $entry, 'sent');
        $draft = $this->campaign($organisation, $product, $entry, 'draft');
        $otherProduct = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Other', 'slug' => 'other',
            'status' => 'active', 'product_type' => 'newsletter', 'primary_collection_handle' => 'insight_newsletters',
        ]);
        $otherIssue = $this->campaign($organisation, $otherProduct, $entry, 'sent');

        $response = $this->get(route('newsletter.archive.index', [$organisation, 'insight_newsletters', $product]));

        $response->assertOk()->assertSee(route('newsletter.archive.show', $sent), false)
            ->assertDontSee(route('newsletter.archive.show', $draft), false)
            ->assertDontSee(route('newsletter.archive.show', $otherIssue), false);
        $this->get(route('newsletter.archive.index', [$organisation, 'insight_newsletters', $otherProduct]))->assertOk();
        $this->get(route('newsletter.archive.index', [$organisation, 'foundation_newsletters', $product]))->assertNotFound();
    }

    public function test_browser_issue_is_public_but_draft_is_not_and_contains_no_subscriber_links(): void
    {
        [$organisation, $product, $entry] = $this->fixtures();
        $sent = $this->campaign($organisation, $product, $entry, 'sent');
        $draft = $this->campaign($organisation, $product, $entry, 'draft');

        $this->get(route('newsletter.archive.show', $draft))->assertNotFound();
        $this->get(route('newsletter.archive.show', $sent))
            ->assertOk()->assertSee('Past newsletters')
            ->assertDontSee('Manage preferences')->assertDontSee('Unsubscribe')
            ->assertDontSee('/preferences/')->assertDontSee('/unsubscribe/');

        $mail = new NewsletterMailable($sent, Subscriber::factory()->create(), 'send-1');
        $this->assertStringContainsString('View in browser', $mail->render());
        $this->assertStringContainsString('Past newsletters', $mail->render());
    }

    public function test_browser_link_works_during_delivery_only_after_a_successful_send(): void
    {
        [$organisation, $product, $entry] = $this->fixtures();
        $sending = $this->campaign($organisation, $product, $entry, 'sending');
        $sending->update(['sent_at' => now()]);

        $this->get(route('newsletter.archive.show', $sending))->assertNotFound();
        $sending->sends()->create([
            'subscriber_id' => Subscriber::factory()->create()->id,
            'status' => 'sent', 'sent_at' => now(),
        ]);

        $this->get(route('newsletter.archive.show', $sending))->assertOk();
        $this->get(route('newsletter.archive.index', [$organisation, 'insight_newsletters', $product]))
            ->assertDontSee(route('newsletter.archive.show', $sending), false);
    }

    public function test_legacy_campaign_without_organisation_can_render_a_test_email(): void
    {
        [$organisation, $product, $entry] = $this->fixtures();
        $legacy = $this->campaign($organisation, $product, $entry, 'partial');
        $legacy->update(['organisation_id' => null, 'product_id' => null]);

        $mail = new NewsletterMailable($legacy->fresh(), Subscriber::factory()->create(), 'test-legacy');
        $content = $mail->content()->with;

        $this->assertNull($content['browserUrl']);
        $this->assertNull($content['archiveUrl']);
        $this->assertStringNotContainsString('View in browser', $mail->render());
        $this->assertStringNotContainsString('Past newsletters', $mail->render());
    }

    private function fixtures(): array
    {
        StatamicCollection::make('insight_newsletters')->title('Insight Newsletters')->save();
        Blueprint::make('data_dive')->setNamespace('collections.insight_newsletters')
            ->setContents(['sections' => ['main' => ['fields' => []]]])->save();
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Data Dive', 'slug' => 'data-dive',
            'status' => 'active', 'product_type' => 'newsletter', 'primary_collection_handle' => 'insight_newsletters',
        ]);
        $entry = EntryModel::query()->create([
            'site' => 'default', 'collection' => 'insight_newsletters', 'blueprint' => 'data_dive',
            'slug' => 'first-issue', 'date' => '2026-10-19', 'published' => true,
            'data' => ['title' => 'First issue', 'subject' => 'First issue', 'content' => '<p>Archive content</p>'],
        ]);

        return [$organisation, $product, $entry];
    }

    private function campaign(Organisation $organisation, Product $product, EntryModel $entry, string $status): Campaign
    {
        return Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'entry_id' => $entry->id, 'collection' => 'insight_newsletters',
            'name' => $status.' issue', 'subject' => $status.' issue', 'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
