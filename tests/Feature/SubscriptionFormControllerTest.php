<?php

namespace Tests\Feature;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\NewsletterPublicUrlService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form;
use Tests\TestCase;

class SubscriptionFormControllerTest extends TestCase
{
    public function test_schema_endpoint_returns_newsletter_form_metadata(): void
    {
        $form = $this->makePolicyPointForm();

        $response = $this->getJson(route('newsletter.forms.schema', ['form' => 'policy-point']));

        $response->assertOk()
            ->assertJsonPath('handle', $form->handle())
            ->assertJsonPath('collection', 'policy_point_newsletters')
            ->assertJsonPath('preference_field', 'frequency')
            ->assertJsonPath('endpoint', route('newsletter.forms.submit', ['form' => 'policy-point']));

        $options = collect($response->json('fields.3.options'))->keyBy('value');

        $this->assertSame('As frequently', $options->get('as-frequently')['label']);
        $this->assertSame('Monthly', $options->get('monthly')['label']);
    }

    public function test_schema_endpoint_uses_verified_product_forms_domain_when_available(): void
    {
        $form = $this->makePolicyPointForm();
        $this->createPolicyPointProduct();

        $response = $this->getJson(route('newsletter.forms.schema', ['form' => 'policy-point']));

        $response->assertOk()
            ->assertJsonPath('handle', $form->handle())
            ->assertJsonPath('endpoint', 'https://join.policy.example.test/subscribe/policy-point');
    }

    public function test_subscription_confirmation_links_use_product_public_domain(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();
        $this->createPolicyPointProduct();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada-domain@example.com',
            'frequency' => 'monthly',
        ])->assertOk();

        Mail::assertQueued(SubscriptionConfirmationMail::class, function (SubscriptionConfirmationMail $mail) {
            $payload = $mail->content()->with;

            return $mail->hasTo('ada-domain@example.com')
                && str_starts_with($payload['preferencesUrl'], 'https://policy.example.test/preferences/')
                && str_contains($payload['preferencesUrl'], 'signature=')
                && str_starts_with($payload['unsubscribeUrl'], 'https://policy.example.test/unsubscribe/')
                && str_contains($payload['unsubscribeUrl'], 'signature=');
        });
    }

    public function test_product_domain_signed_preferences_page_keeps_actions_on_product_domain(): void
    {
        $this->makePolicyPointForm();
        $this->createPolicyPointProduct();

        $subscriber = Subscriber::factory()->create([
            'email' => 'preference-domain@example.com',
            'status' => 'active',
            'confirmation_token' => 'preference-domain-token',
            'confirmed_at' => now(),
        ]);

        $url = app(NewsletterPublicUrlService::class)
            ->preferencesUrl($subscriber, 'policy_point_newsletters', 'policy_point_newsletters');

        $this->get($url)
            ->assertOk()
            ->assertSee('https://policy.example.test/preferences/preference-domain-token', false)
            ->assertSee('https://policy.example.test/unsubscribe/preference-domain-token', false);
    }

    public function test_submit_endpoint_creates_subscriber_and_managed_sub_groups(): void
    {
        Mail::fake();
        $form = $this->makePolicyPointForm();

        $response = $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'subscribed')
            ->assertJsonPath('subscriber.email', 'ada@example.com');

        $subscriber = Subscriber::where('email', 'ada@example.com')->firstOrFail();
        $monthly = SubscriberSubGroup::where('slug', 'monthly')->firstOrFail();
        /** @var StatamicSubmission $submission */
        $submission = $form->querySubmissions()->first();

        $this->assertEquals('pending', $subscriber->status);
        $this->assertNull($subscriber->confirmed_at);
        $this->assertSame(0, $subscriber->pending_confirmation_resend_count);
        $this->assertNotNull($subscriber->pending_confirmation_expires_at);
        $this->assertSame('awaiting_confirmation', $subscriber->pending_lifecycle_state);
        $this->assertSame(1, $form->querySubmissions()->count());
        $this->assertDatabaseHas('subscriber_groups', ['slug' => 'policy-point']);
        $this->assertDatabaseHas('subscriber_sub_groups', ['slug' => 'as-frequently']);
        $this->assertDatabaseHas('subscriber_sub_groups', ['slug' => 'monthly']);
        $this->assertDatabaseHas('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $monthly->id,
            'unsubscribed_at' => null,
        ]);
        $this->assertSame('subscribed', $submission->get('subscription_status'));
        $this->assertTrue($submission->get('email_sent'));
        $this->assertSame($subscriber->id, $submission->get('subscriber_id'));
        $this->assertSame($monthly->group->id, $submission->get('subscriber_group_id'));
        Mail::assertQueued(SubscriptionConfirmationMail::class, function (SubscriptionConfirmationMail $mail) {
            return $mail->hasTo('ada@example.com') && $mail->status === 'subscribed';
        });
    }

    public function test_submit_endpoint_accepts_a_restored_group_through_the_existing_form_lookup(): void
    {
        Mail::fake();
        $form = $this->makePolicyPointForm();
        $group = SubscriberGroup::query()->findOrFail($form->get('newsletter_group'));

        $group->forceFill([
            'archived_at' => now(),
            'archived_by' => 88,
        ])->save();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada-restored@example.com',
            'frequency' => 'monthly',
        ])->assertNotFound();

        $this->assertDatabaseMissing('subscribers', [
            'email' => 'ada-restored@example.com',
        ]);

        $group->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada-restored@example.com',
            'frequency' => 'monthly',
        ])->assertOk()
            ->assertJsonPath('status', 'subscribed');

        $this->assertDatabaseHas('subscribers', [
            'email' => 'ada-restored@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_resubmitting_switches_managed_preferences_within_the_form_scope(): void
    {
        $this->makePolicyPointForm();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'as-frequently',
        ])->assertOk()
            ->assertJsonPath('status', 'subscription_updated');

        $subscriber = Subscriber::where('email', 'ada@example.com')->firstOrFail();
        $monthly = SubscriberSubGroup::where('slug', 'monthly')->firstOrFail();
        $frequent = SubscriberSubGroup::where('slug', 'as-frequently')->firstOrFail();

        $this->assertDatabaseHas('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $frequent->id,
            'unsubscribed_at' => null,
        ]);

        $this->assertDatabaseMissing('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $monthly->id,
            'unsubscribed_at' => null,
        ]);
    }

    public function test_pending_subscriber_preference_update_does_not_activate_before_delivery(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk();

        $subscriber = Subscriber::where('email', 'ada@example.com')->firstOrFail();
        $frequent = SubscriberSubGroup::where('slug', 'as-frequently')->firstOrFail();

        $this->post(URL::signedRoute('newsletter.preferences.update', [
            'token' => $subscriber->confirmation_token,
            'collection' => 'policy_point_newsletters',
        ]), [
            'sub_groups' => [$frequent->id],
        ])->assertOk();

        $this->assertSame('pending', $subscriber->fresh()->status);
        $this->assertNull($subscriber->fresh()->confirmed_at);
        $this->assertDatabaseHas('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $frequent->id,
            'unsubscribed_at' => null,
        ]);
    }

    public function test_repeat_submission_with_same_details_returns_already_subscribed(): void
    {
        Mail::fake();
        $form = $this->makePolicyPointForm();

        $payload = [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ];

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'subscribed');

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'already_subscribed')
            ->assertJsonPath('message', 'You are already subscribed.');

        $submission = $form->querySubmissions()->latest()->first();

        $this->assertSame('already_subscribed', $submission->get('subscription_status'));
        $this->assertFalse($submission->get('email_sent'));
        Mail::assertQueued(SubscriptionConfirmationMail::class, 1);
    }

    public function test_existing_email_with_missing_first_name_gets_enriched(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $subscriber = Subscriber::factory()->create([
            'email' => 'ada@example.com',
            'first_name' => null,
            'last_name' => 'Lovelace',
            'status' => 'active',
        ]);

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk()
            ->assertJsonPath('status', 'subscription_updated');

        $this->assertSame('Ada', $subscriber->fresh()->first_name);
        Mail::assertNothingQueued();
    }

    public function test_submit_endpoint_accepts_firstname_and_lastname_handles(): void
    {
        Mail::fake();

        Blueprint::make('policy-point-browser-subscribe')
            ->setNamespace('forms')
            ->setContents([
                'sections' => [
                    'main' => [
                        'fields' => [
                            [
                                'handle' => 'firstname',
                                'field' => ['type' => 'text', 'display' => 'Firstname'],
                            ],
                            [
                                'handle' => 'lastname',
                                'field' => ['type' => 'text', 'display' => 'Lastname'],
                            ],
                            [
                                'handle' => 'email',
                                'field' => ['type' => 'text', 'display' => 'Email', 'validate' => 'required|email'],
                            ],
                            [
                                'handle' => 'preference',
                                'field' => [
                                    'type' => 'select',
                                    'display' => 'Preference',
                                    'options' => [
                                        ['key' => 'regular', 'value' => 'Regular'],
                                        ['key' => 'monthly', 'value' => 'Monthly'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])->save();

        $group = SubscriberGroup::factory()->policyPoint()->create();

        Form::make('policy-point-browser-subscribe')
            ->title('Policy Point Browser Subscribe')
            ->store(true)
            ->merge([
                'newsletter_group' => $group->id,
                'newsletter_endpoint' => 'policy-point-browser',
                'newsletter_preference_field' => 'preference',
                'newsletter_send_confirmation_email' => false,
            ])->save();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point-browser']), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'email' => 'ada-browser@example.com',
            'preference' => 'monthly',
        ])->assertOk()
            ->assertJsonPath('status', 'subscribed');

        $subscriber = Subscriber::where('email', 'ada-browser@example.com')->firstOrFail();

        $this->assertSame('Ada', $subscriber->first_name);
        $this->assertSame('Lovelace', $subscriber->last_name);
    }

    public function test_unsubscribed_user_can_resubscribe(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $subscriber = Subscriber::factory()->unsubscribed()->create([
            'email' => 'ada@example.com',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk()
            ->assertJsonPath('status', 'resubscribed')
            ->assertJsonPath('message', 'Your subscription has been restored.');

        $this->assertSame('pending', $subscriber->fresh()->status);
        $this->assertNull($subscriber->fresh()->confirmed_at);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
        $this->assertSame(0, $subscriber->fresh()->pending_confirmation_resend_count);
        $this->assertSame('awaiting_reconfirmation', $subscriber->fresh()->pending_lifecycle_state);
        Mail::assertQueued(SubscriptionConfirmationMail::class, function (SubscriptionConfirmationMail $mail) {
            return $mail->hasTo('ada@example.com') && $mail->status === 'resubscribed';
        });
    }

    public function test_profile_update_email_is_not_sent_unless_enabled(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk();

        Mail::assertQueued(SubscriptionConfirmationMail::class, 1);

        $this->postJson(route('newsletter.forms.submit', ['form' => 'policy-point']), [
            'first_name' => 'Augusta Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'frequency' => 'monthly',
        ])->assertOk()
            ->assertJsonPath('status', 'subscription_updated');

        Mail::assertQueued(SubscriptionConfirmationMail::class, 1);
    }

    private function makePolicyPointForm()
    {
        $group = SubscriberGroup::factory()->policyPoint()->create();

        Blueprint::make('policy-point-subscribe')
            ->setNamespace('forms')
            ->setContents([
                'sections' => [
                    'main' => [
                        'fields' => [
                            [
                                'handle' => 'first_name',
                                'field' => ['type' => 'text', 'display' => 'First Name'],
                            ],
                            [
                                'handle' => 'last_name',
                                'field' => ['type' => 'text', 'display' => 'Last Name'],
                            ],
                            [
                                'handle' => 'email',
                                'field' => ['type' => 'text', 'display' => 'Email', 'validate' => 'required|email'],
                            ],
                            [
                                'handle' => 'frequency',
                                'field' => [
                                    'type' => 'select',
                                    'display' => 'How frequently would you like to receive updates from us?',
                                    'validate' => 'required',
                                    'options' => [
                                        'as-frequently' => 'As frequently',
                                        'monthly' => 'Monthly',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])->save();

        $form = Form::make('policy-point-subscribe')
            ->title('Policy Point Subscribe')
            ->store(true)
            ->merge([
                'newsletter_group' => $group->id,
                'newsletter_endpoint' => 'policy-point',
                'newsletter_preference_field' => 'frequency',
                'newsletter_logo_url' => 'https://example.com/policy-point-logo.png',
                'newsletter_brand_color' => '#3d405b',
                'newsletter_success_message' => 'You are subscribed.',
                'newsletter_send_confirmation_email' => true,
                'newsletter_confirmation_subject' => 'Welcome to Policy Point',
                'newsletter_resubscribe_subject' => 'Welcome back to Policy Point',
            ]);

        $form->save();

        return $form;
    }

    private function createPolicyPointProduct(): Product
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'status' => 'active',
            'product_type' => 'newsletter',
            'public_domain' => 'policy.example.test',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => now(),
            'primary_collection_handle' => 'policy_point_newsletters',
            'fallback_to_platform_domain' => true,
        ]);
    }
}
