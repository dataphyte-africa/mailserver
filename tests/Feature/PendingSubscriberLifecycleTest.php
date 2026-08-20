<?php

namespace Tests\Feature;

use App\Http\Controllers\CP\Newsletter\SubscriberController;
use App\Mail\SubscriptionConfirmationMail;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\PendingSubscriberLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form;
use Tests\TestCase;

class PendingSubscriberLifecycleTest extends TestCase
{
    public function test_show_exposes_expired_pending_lifecycle_state(): void
    {
        $subscriber = Subscriber::factory()->pending()->create([
            'pending_confirmation_expires_at' => now()->subDay(),
            'pending_lifecycle_state' => PendingSubscriberLifecycleService::STATE_AWAITING_CONFIRMATION,
        ]);

        $response = (new SubscriberController)->show($subscriber);
        $pendingLifecycle = $response->getData()['pendingLifecycle'];

        $this->assertTrue($pendingLifecycle['is_pending']);
        $this->assertTrue($pendingLifecycle['is_expired']);
        $this->assertSame('Expired pending', $pendingLifecycle['label']);
        $this->assertSame(PendingSubscriberLifecycleService::STATE_EXPIRED_PENDING, $subscriber->fresh()->pending_lifecycle_state);
    }

    public function test_resend_confirmation_queues_mail_and_updates_pending_audit_fields(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $subscriber = Subscriber::factory()->pending()->create([
            'email' => 'pending-resend@example.test',
            'metadata' => [
                'newsletter_form' => [
                    'handle' => 'policy-point-subscribe',
                    'collection' => 'policy_point_newsletters',
                    'endpoint' => 'policy-point',
                ],
            ],
            'pending_confirmation_expires_at' => now()->addDays(6),
            'pending_lifecycle_state' => PendingSubscriberLifecycleService::STATE_AWAITING_CONFIRMATION,
        ]);

        (new SubscriberController)->resendConfirmation($subscriber);

        Mail::assertQueued(SubscriptionConfirmationMail::class, function (SubscriptionConfirmationMail $mail) {
            return $mail->hasTo('pending-resend@example.test') && $mail->status === 'subscribed';
        });

        $fresh = $subscriber->fresh();

        $this->assertSame(1, $fresh->pending_confirmation_resend_count);
        $this->assertNotNull($fresh->pending_confirmation_last_resent_at);
        $this->assertSame(PendingSubscriberLifecycleService::STATE_CONFIRMATION_RESENT, $fresh->pending_lifecycle_state);
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->confirmed_at);
    }

    public function test_resend_confirmation_enforces_cooldown(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $subscriber = Subscriber::factory()->pending()->create([
            'email' => 'pending-cooldown@example.test',
            'metadata' => [
                'newsletter_form' => [
                    'handle' => 'policy-point-subscribe',
                    'collection' => 'policy_point_newsletters',
                    'endpoint' => 'policy-point',
                ],
            ],
            'pending_confirmation_resend_count' => 1,
            'pending_confirmation_last_resent_at' => now()->subMinutes(5),
            'pending_confirmation_expires_at' => now()->addDays(6),
            'pending_lifecycle_state' => PendingSubscriberLifecycleService::STATE_CONFIRMATION_RESENT,
        ]);

        (new SubscriberController)->resendConfirmation($subscriber);

        Mail::assertNothingQueued();
        $this->assertSame(1, $subscriber->fresh()->pending_confirmation_resend_count);
    }

    public function test_resend_confirmation_enforces_limit(): void
    {
        Mail::fake();
        $this->makePolicyPointForm();

        $subscriber = Subscriber::factory()->pending()->create([
            'email' => 'pending-limit@example.test',
            'metadata' => [
                'newsletter_form' => [
                    'handle' => 'policy-point-subscribe',
                    'collection' => 'policy_point_newsletters',
                    'endpoint' => 'policy-point',
                ],
            ],
            'pending_confirmation_resend_count' => 3,
            'pending_confirmation_last_resent_at' => now()->subMinutes(30),
            'pending_confirmation_expires_at' => now()->addDays(6),
            'pending_lifecycle_state' => PendingSubscriberLifecycleService::STATE_CONFIRMATION_RESENT,
        ]);

        (new SubscriberController)->resendConfirmation($subscriber);

        Mail::assertNothingQueued();
        $this->assertSame(3, $subscriber->fresh()->pending_confirmation_resend_count);
    }

    public function test_update_cannot_promote_pending_subscriber_to_active(): void
    {
        $subGroup = $this->createSubGroup();
        $subscriber = Subscriber::factory()->pending()->create([
            'pending_confirmation_expires_at' => now()->addDays(6),
            'pending_lifecycle_state' => PendingSubscriberLifecycleService::STATE_AWAITING_CONFIRMATION,
        ]);
        $subscriber->subGroups()->attach($subGroup->id, ['subscribed_at' => now()]);

        try {
            (new SubscriberController)->update($this->request('PUT', [
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'status' => 'active',
                'sub_groups' => [$subGroup->id],
            ]), $subscriber);

            $this->fail('Expected pending-to-active CP update validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
    }

    private function makePolicyPointForm(): void
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

        Form::make('policy-point-subscribe')
            ->title('Policy Point Subscribe')
            ->store(true)
            ->merge([
                'newsletter_group' => $group->id,
                'newsletter_endpoint' => 'policy-point',
                'newsletter_preference_field' => 'frequency',
                'newsletter_send_confirmation_email' => true,
                'newsletter_confirmation_subject' => 'Welcome to Policy Point',
                'newsletter_resubscribe_subject' => 'Welcome back to Policy Point',
            ])->save();
    }

    private function createSubGroup(): SubscriberSubGroup
    {
        $group = SubscriberGroup::factory()->create();

        return SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function request(string $method, array $parameters = []): Request
    {
        return Request::create('/cp/newsletter/subscribers', $method, $parameters);
    }
}
