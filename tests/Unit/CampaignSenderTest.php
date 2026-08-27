<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Mail\NewsletterMailable;
use Tests\TestCase;

class CampaignSenderTest extends TestCase
{
    public function test_sender_uses_collection_config_for_insight(): void
    {
        $campaign = Campaign::factory()->insight()->make();

        $sender = $campaign->sender();

        $this->assertEquals('newsletter@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Dataphyte Insight',        $sender['from_name']);
    }

    public function test_sender_uses_collection_config_for_foundation(): void
    {
        $campaign = Campaign::factory()->foundation()->make();

        $sender = $campaign->sender();

        $this->assertEquals('newsletter@dataphyte.org', $sender['from_email']);
        $this->assertEquals('Dataphyte Foundation',     $sender['from_name']);
    }

    public function test_sender_uses_collection_config_for_policy_point(): void
    {
        $campaign = Campaign::factory()->policyPoint()->make();

        $sender = $campaign->sender();

        $this->assertEquals('newsletter@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Policy Point',             $sender['from_name']);
    }

    public function test_sender_uses_collection_config_for_academy(): void
    {
        $campaign = Campaign::factory()->academy()->make();

        $sender = $campaign->sender();

        $this->assertEquals('academy@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Dataphyte Academy',     $sender['from_name']);
    }

    public function test_academy_first_name_merge_tags_fall_back_to_reader(): void
    {
        $campaign = Campaign::factory()->academy()->create();
        $subscriber = Subscriber::factory()->create([
            'email' => 'reader@example.com',
            'first_name' => null,
            'last_name' => null,
        ]);

        $content = (new NewsletterMailable($campaign, $subscriber, 'test'))
            ->prepareCampaignContent('<p>Dear {{first_name}},</p><p>Hello {{firstname}}</p>', []);

        $this->assertStringContainsString('Dear Reader,', $content);
        $this->assertStringContainsString('Hello Reader', $content);
    }

    public function test_single_brace_first_name_merge_tags_are_supported(): void
    {
        $campaign = Campaign::factory()->insight()->create();
        $subscriber = Subscriber::factory()->create([
            'first_name' => 'Ada',
        ]);

        $content = (new NewsletterMailable($campaign, $subscriber, 'test'))
            ->prepareCampaignContent('<p>Dear {first_name},</p><p>Hello {firstname}</p>', []);

        $this->assertStringContainsString('Dear Ada,', $content);
        $this->assertStringContainsString('Hello Ada', $content);
    }

    public function test_insight_update_template_removes_leading_subject_line_from_body(): void
    {
        $html = view('emails.insight.insight-update', [
            'subject' => 'Recap: Dashboard Session',
            'preheader' => '',
            'heroImageUrl' => null,
            'content' => '<p style="margin:0"><strong>Subject: Recap: Dashboard Session</strong></p><p style="margin:0">Dear Reader,</p>',
            'ctaText' => 'Read more',
            'ctaUrl' => 'https://www.dataphyte.com',
            'headerColor' => '#0d1b2a',
            'newsletterSettings' => [],
            'footerConfig' => [],
            'footerPartial' => 'emails.partials.shared.footer-base',
            'fromName' => 'Dataphyte Insight',
            'unsubscribeUrl' => '#',
            'preferencesUrl' => '#',
        ])->render();

        $this->assertStringNotContainsString('Subject: Recap: Dashboard Session', $html);
        $this->assertStringContainsString('Dear Reader,', $html);
    }

    public function test_per_campaign_override_takes_precedence(): void
    {
        $campaign = Campaign::factory()->insight()->make([
            'from_email' => 'custom@dataphyte.com',
            'from_name'  => 'Custom Name',
        ]);

        $sender = $campaign->sender();

        $this->assertEquals('custom@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Custom Name',          $sender['from_name']);
    }

    public function test_fallback_used_for_unknown_collection(): void
    {
        $campaign = Campaign::factory()->make([
            'collection' => 'unknown_collection',
            'from_email' => null,
            'from_name'  => null,
        ]);

        $sender = $campaign->sender();

        $this->assertEquals(config('newsletter.fallback.from_email'), $sender['from_email']);
    }

    public function test_scope_due_returns_only_scheduled_past_campaigns(): void
    {
        Campaign::factory()->due()->create();
        Campaign::factory()->scheduled()->create(); // future — should not appear
        Campaign::factory()->draft()->create();
        Campaign::factory()->sent()->create();

        $due = Campaign::due()->get();

        $this->assertCount(1, $due);
        $this->assertEquals('scheduled', $due->first()->status);
    }

    public function test_scope_draft_filters_correctly(): void
    {
        Campaign::factory()->draft()->create();
        Campaign::factory()->sent()->create();

        $this->assertCount(1, Campaign::draft()->get());
    }
}
