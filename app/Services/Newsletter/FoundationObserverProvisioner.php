<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form;

class FoundationObserverProvisioner
{
    public const FORM_HANDLE = 'osun_election_observers';
    public const FORM_ENDPOINT = 'osun-election-observers';
    public const TARGET_SUB_GROUP_SLUG = 'osun-election-observers-2026';
    public const TARGET_SUB_GROUP_NAME = 'Osun Election Observers 2026';

    public function __construct(
        private readonly ApplicationSubmissionTrackingService $applicationTracking,
    ) {}

    public function provision(): array
    {
        return [
            'group' => $this->ensureFoundationGroup(),
            'target_sub_group' => $this->ensureTargetSubGroup(),
            'form_blueprint' => $this->ensureFormBlueprint(),
            'form' => $this->ensureForm(),
        ];
    }

    private function ensureFoundationGroup(): int
    {
        $group = SubscriberGroup::query()->firstOrCreate(
            ['slug' => 'foundation'],
            [
                'name' => 'Foundation',
                'collection_handle' => 'foundation_newsletters',
                'description' => 'Dataphyte Foundation subscribers and operational cohorts.',
            ],
        );

        return $group->id;
    }

    private function ensureTargetSubGroup(): int
    {
        $groupId = SubscriberGroup::query()
            ->where('slug', 'foundation')
            ->value('id');

        $subGroup = SubscriberSubGroup::query()->updateOrCreate(
            [
                'subscriber_group_id' => $groupId,
                'slug' => self::TARGET_SUB_GROUP_SLUG,
            ],
            [
                'name' => self::TARGET_SUB_GROUP_NAME,
                'description' => 'Applicants for the Osun State Governorship Election observer call.',
            ],
        );

        return $subGroup->id;
    }

    private function ensureFormBlueprint(): string
    {
        $blueprint = Blueprint::find('forms.' . self::FORM_HANDLE)
            ?? Blueprint::make(self::FORM_HANDLE)->setNamespace('forms');

        $blueprint
            ->setContents($this->formBlueprintContents())
            ->save();

        return 'forms.' . self::FORM_HANDLE;
    }

    private function ensureForm(): string
    {
        $groupId = SubscriberGroup::query()
            ->where('slug', 'foundation')
            ->value('id');

        $form = Form::find(self::FORM_HANDLE)
            ?? Form::make(self::FORM_HANDLE);

        $form
            ->title('Call for Election Observers for Osun State Governorship Election')
            ->store(true)
            ->honeypot('honeypot')
            ->merge([
                'newsletter_group' => $groupId,
                'newsletter_endpoint' => self::FORM_ENDPOINT,
                'newsletter_submission_mode' => 'application',
                'newsletter_target_sub_group_slug' => self::TARGET_SUB_GROUP_SLUG,
                'newsletter_target_sub_group_name' => self::TARGET_SUB_GROUP_NAME,
                'newsletter_turnstile_field' => 'turnstile_token',
                'newsletter_confirmation_summary_fields' => 'full_name,lga_name,ward_name,age,confirm_above_18',
                'newsletter_closed_at' => '2026-07-21 23:59:00',
                'newsletter_closed_message' => 'This form no longer takes submissions.',
                'newsletter_ineligible_message' => 'Thank you for your interest. This application is only open to applicants who are currently resident in Osun State. Because election observers will be deployed within their local government areas of residence, we are unable to continue your application.',
                'newsletter_success_message' => 'Your application has been received.',
                'newsletter_send_confirmation_email' => true,
                'newsletter_send_update_email' => false,
                'newsletter_confirmation_subject' => 'Call for Election Observers for Osun State Governorship Election',
                'newsletter_logo_url' => '/storage/foundation-banner.png',
                'newsletter_confirmation_body' => "Your application to serve as an Election Observer for the Osun State Governorship Election has been received.\n\nPlease note that submitting an application does not automatically guarantee your selection. Applicants will be notified of the outcome of their application by email within one week of the application deadline, or earlier. The email will also provide information on the next steps.",
                'newsletter_confirmation_summary_heading' => null,
                'newsletter_resubscribe_subject' => null,
                'newsletter_resubscribe_body' => null,
                'newsletter_brand_color' => null,
                'newsletter_privacy_url' => null,
            ])
            ->save();

        return $form->handle();
    }

    private function formBlueprintContents(): array
    {
        return [
            'title' => 'Osun Election Observers',
            'tabs' => [
                'main' => [
                    'display' => 'Main',
                    'sections' => [
                        [
                            'fields' => [
                                $this->textField('full_name', 'Full Name'),
                                $this->textField('phone_number', 'Phone Number (WhatsApp preferably)'),
                                $this->emailField('email', 'Email Address'),
                                $this->selectField('gender', 'Gender', [
                                    ['key' => 'male', 'value' => 'Male'],
                                    ['key' => 'female', 'value' => 'Female'],
                                    ['key' => 'other', 'value' => 'Other'],
                                ]),
                                $this->toggleField('confirm_above_18', 'I confirm that I am 18 years or older.'),
                                $this->integerField('age', 'Age'),
                                $this->selectField('resident_in_osun', 'Are you currently resident in Osun State?', [
                                    ['key' => 'yes', 'value' => 'Yes'],
                                    ['key' => 'no', 'value' => 'No'],
                                ]),
                                $this->textField('lga_id', 'Local Government Area ID'),
                                $this->textField('lga_name', 'Local Government Area of Residence'),
                                $this->textField('ward_id', 'Electoral Ward ID'),
                                $this->textField('ward_name', 'Electoral Ward of Residence'),
                                $this->selectField('has_election_observation_experience', 'Do you have experience in election observation?', [
                                    ['key' => 'yes', 'value' => 'Yes'],
                                    ['key' => 'no', 'value' => 'No'],
                                ]),
                                $this->selectField('available_for_election_day', 'Will you be available on Saturday, August 15, 2026 to observe the Osun Governorship election?', [
                                    ['key' => 'yes', 'value' => 'Yes'],
                                    ['key' => 'no', 'value' => 'No'],
                                ]),
                                $this->selectField('available_for_training', 'Selected applicants will be required to participate in a mandatory training session. Will you be available for the training?', [
                                    ['key' => 'yes', 'value' => 'Yes'],
                                    ['key' => 'no', 'value' => 'No'],
                                ]),
                                $this->textField('emergency_phone_number', 'Emergency Phone Number (for security reasons)'),
                                $this->toggleField('future_foundation_updates', 'I would like to receive future communication from Dataphyte Foundation on data collections and other Dataphyte Foundation related content.', false),
                                $this->textField('turnstile_token', 'Turnstile Token'),
                                $this->textField('ip_address', 'IP Address'),
                                $this->textField('device', 'Device'),
                            ],
                        ],
                        [
                            'display' => 'Internal Delivery Tracking',
                            'fields' => $this->applicationTracking->trackingFieldDefinitions(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function textField(string $handle, string $display): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'text',
                'display' => $display,
                'validate' => 'required',
            ],
        ];
    }

    private function emailField(string $handle, string $display): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'text',
                'display' => $display,
                'input_type' => 'email',
                'validate' => 'required|email',
            ],
        ];
    }

    private function integerField(string $handle, string $display): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'integer',
                'display' => $display,
                'validate' => 'required|integer|min:18',
            ],
        ];
    }

    private function toggleField(string $handle, string $display, bool $required = true): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'toggle',
                'display' => $display,
                'validate' => $required ? 'required' : null,
            ],
        ];
    }

    private function selectField(string $handle, string $display, array $options): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'select',
                'display' => $display,
                'validate' => 'required',
                'options' => $options,
            ],
        ];
    }
}
