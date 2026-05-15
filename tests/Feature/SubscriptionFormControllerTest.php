<?php

namespace Tests\Feature;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\Mail;
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

        $this->assertEquals('active', $subscriber->status);
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

        $this->assertSame('active', $subscriber->fresh()->status);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
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
}
