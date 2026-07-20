<?php

namespace App\Services\Newsletter;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Forms\Form as StatamicForm;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;
use Statamic\Facades\Form;
use Statamic\Fields\Field;

class SubscriptionFormService
{
    public function __construct(
        private readonly CollectionRegistry $collections,
        private readonly ApplicationSubmissionTrackingService $applicationTracking,
    ) {}

    public function isNewsletterForm(StatamicForm $form): bool
    {
        return $this->group($form) !== null;
    }

    public function collectionHandle(StatamicForm $form): ?string
    {
        return $this->group($form)?->collection_handle;
    }

    public function group(StatamicForm $form): ?SubscriberGroup
    {
        $id = $form->get('newsletter_group');

        if (! filled($id)) {
            return null;
        }

        return SubscriberGroup::query()
            ->whereKey($id)
            ->whereNotNull('collection_handle')
            ->first();
    }

    public function endpointSlug(StatamicForm $form): string
    {
        return $form->get('newsletter_endpoint')
            ?: Str::of($form->handle())->replace('_', '-')->toString();
    }

    public function privacyUrl(StatamicForm $form): ?string
    {
        return $form->get('newsletter_privacy_url') ?: null;
    }

    public function logoUrl(StatamicForm $form): ?string
    {
        return $form->get('newsletter_logo_url') ?: null;
    }

    public function brandColor(StatamicForm $form): ?string
    {
        return $form->get('newsletter_brand_color') ?: null;
    }

    public function successMessage(StatamicForm $form): string
    {
        return $form->get('newsletter_success_message') ?: 'Subscription successful.';
    }

    public function sendConfirmationEmail(StatamicForm $form): bool
    {
        return (bool) $form->get('newsletter_send_confirmation_email');
    }

    public function sendUpdateEmail(StatamicForm $form): bool
    {
        return (bool) $form->get('newsletter_send_update_email');
    }

    public function preferenceFieldHandle(StatamicForm $form): ?string
    {
        $handle = $form->get('newsletter_preference_field');

        return is_string($handle) && $handle !== '' ? $handle : null;
    }

    public function submissionMode(StatamicForm $form): string
    {
        $mode = (string) ($form->get('newsletter_submission_mode') ?: 'subscription');

        return in_array($mode, ['subscription', 'application'], true)
            ? $mode
            : 'subscription';
    }

    public function targetSubGroupSlug(StatamicForm $form): ?string
    {
        $slug = $form->get('newsletter_target_sub_group_slug');

        return is_string($slug) && trim($slug) !== '' ? Str::slug($slug) : null;
    }

    public function targetSubGroupName(StatamicForm $form): ?string
    {
        $name = $form->get('newsletter_target_sub_group_name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    public function turnstileFieldHandle(StatamicForm $form): ?string
    {
        $handle = $form->get('newsletter_turnstile_field');

        return is_string($handle) && trim($handle) !== '' ? trim($handle) : null;
    }

    public function confirmationSummaryFields(StatamicForm $form): array
    {
        return collect(explode(',', (string) ($form->get('newsletter_confirmation_summary_fields') ?? '')))
            ->map(fn (string $handle) => trim($handle))
            ->filter()
            ->values()
            ->all();
    }

    public function closedAt(StatamicForm $form): ?CarbonImmutable
    {
        $value = $form->get('newsletter_closed_at');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    public function closedMessage(StatamicForm $form): string
    {
        return (string) ($form->get('newsletter_closed_message') ?: 'This form no longer takes submissions.');
    }

    public function ineligibleMessage(StatamicForm $form): string
    {
        return (string) ($form->get('newsletter_ineligible_message')
            ?: 'Thank you for your interest. This application is only open to applicants who are currently resident in Osun State. Because election observers will be deployed within their local government areas of residence, we are unable to continue your application.');
    }

    public function prepareSubmissionPayload(StatamicForm $form, array $payload, Request $request): array
    {
        $turnstileField = $this->turnstileFieldHandle($form);

        if ($turnstileField && ! filled($payload[$turnstileField] ?? null) && filled($payload['cf-turnstile-response'] ?? null)) {
            $payload[$turnstileField] = $payload['cf-turnstile-response'];
        }

        $payload['ip_address'] = (string) ($payload['ip_address'] ?? $request->ip() ?? '');
        $payload['device'] = (string) ($payload['device'] ?? Str::limit((string) $request->userAgent(), 255, ''));

        if (isset($payload['email']) && is_string($payload['email'])) {
            $payload['email'] = strtolower(trim($payload['email']));
        }

        foreach (['phone_number', 'emergency_phone_number'] as $handle) {
            if (isset($payload[$handle]) && is_string($payload[$handle])) {
                $payload[$handle] = $this->normalizePhone($payload[$handle]);
            }
        }

        if ($this->shouldBypassTurnstile()) {
            if ($turnstileField && ! filled($payload[$turnstileField] ?? null)) {
                $payload[$turnstileField] = 'dev-turnstile-bypass';
            }
        }

        return $payload;
    }

    public function resolveForm(string $identifier): ?StatamicForm
    {
        if ($form = Form::find($identifier)) {
            return $this->isNewsletterForm($form) ? $form : null;
        }

        return Form::all()
            ->first(fn (StatamicForm $form) =>
                $this->isNewsletterForm($form) && $this->endpointSlug($form) === $identifier
            );
    }

    public function schema(StatamicForm $form): array
    {
        return [
            'handle' => $form->handle(),
            'title' => $form->title(),
            'collection' => $this->collectionHandle($form),
            'group' => $this->group($form)?->only(['id', 'name', 'slug']),
            'endpoint' => $this->collections->formEndpoint($this->endpointSlug($form)),
            'privacy_url' => $this->privacyUrl($form),
            'logo_url' => $this->logoUrl($form),
            'brand_color' => $this->brandColor($form),
            'success_message' => $this->successMessage($form),
            'preference_field' => $this->preferenceFieldHandle($form),
            'fields' => $form->fields()
                ->map(fn (Field $field) => $this->serializeField($field))
                ->values()
                ->all(),
        ];
    }

    public function syncManagedSubGroups(StatamicForm $form): Collection
    {
        $collectionHandle = $this->collectionHandle($form);

        $group = $this->group($form);

        if (! $collectionHandle || ! $group) {
            return collect();
        }

        return collect($this->preferenceOptions($form))
            ->map(function (array $option) use ($group) {
                return SubscriberSubGroup::firstOrCreate(
                    [
                        'subscriber_group_id' => $group->id,
                        'slug' => $option['slug'],
                    ],
                    [
                        'name' => $option['label'],
                        'description' => 'Auto-managed from subscribe form preferences.',
                    ]
                );
            });
    }

    public function storeSubmission(StatamicForm $form, array $payload): ?StatamicSubmission
    {
        if (! $form->store()) {
            return null;
        }

        $submission = $form->makeSubmission()
            ->data($payload);

        $submission->save();

        return $submission;
    }

    public function annotateSubmission(StatamicSubmission $submission, array $attributes): void
    {
        $submission
            ->data(array_merge($submission->data()->all(), $attributes))
            ->save();
    }

    public function queuedApplicationTrackingAttributes(): array
    {
        return $this->applicationTracking->queuedAttributes();
    }

    public function processApplication(StatamicForm $form, array $payload, Request $request): array
    {
        return $this->submitApplication($form, $payload, $request, submission: null, dispatchEmail: false);
    }

    public function dispatchStoredApplicationEmail(
        StatamicForm $form,
        Subscriber $subscriber,
        array $applicationPayload,
        StatamicSubmission $submission,
    ): bool {
        return $this->dispatchLifecycleEmail($form, $subscriber, 'submitted', $applicationPayload, $submission);
    }

    public function dispatchApplicationEmailWithoutSubmission(
        StatamicForm $form,
        Subscriber $subscriber,
        array $applicationPayload,
    ): bool {
        return $this->dispatchLifecycleEmail($form, $subscriber, 'submitted', $applicationPayload);
    }

    public function subscribe(StatamicForm $form, array $payload, Request $request, ?StatamicSubmission $submission = null): array
    {
        if ($this->submissionMode($form) === 'application') {
            return $this->submitApplication($form, $payload, $request, $submission);
        }

        $collectionHandle = $this->collectionHandle($form);
        $group = $this->group($form);
        abort_if(! $group || ! $collectionHandle, 422, 'Newsletter form is not linked to a valid subscriber group.');
        $groupSlug = $group->slug;
        $managedSubGroups = $this->syncManagedSubGroups($form)->keyBy('slug');
        $groupId = $group->id;

        $subscriber = Subscriber::firstOrNew([
            'email' => strtolower(trim((string) Arr::get($payload, 'email'))),
        ]);
        $wasExisting = $subscriber->exists;
        $previousStatus = $subscriber->status;
        $previousFirstName = $subscriber->first_name;
        $previousLastName = $subscriber->last_name;
        $previousManagedIds = $wasExisting
            ? $subscriber->allSubGroups()
                ->where('subscriber_group_id', $groupId)
                ->whereIn('subscriber_sub_groups.id', $managedSubGroups->pluck('id')->all())
                ->wherePivotNull('subscriber_sub_group.unsubscribed_at')
                ->pluck('subscriber_sub_groups.id')
                ->sort()
                ->values()
                ->all()
            : [];

        $metadata = array_merge($subscriber->metadata ?? [], [
            'newsletter_form' => [
                'handle' => $form->handle(),
                'collection' => $collectionHandle,
                'endpoint' => $this->endpointSlug($form),
                'selected_preferences' => $this->selectedPreferenceSlugs($form, $payload),
                'subscribed_via' => 'statamic_form',
            ],
        ]);

        $subscriber->fill([
            'first_name' => $this->resolveNameField($payload, ['first_name', 'firstname', 'first']),
            'last_name' => $this->resolveNameField($payload, ['last_name', 'lastname', 'last']),
            'status' => 'active',
            'confirmed_at' => $subscriber->confirmed_at ?? now(),
            'unsubscribed_at' => null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
            'metadata' => $metadata,
        ]);

        $subscriber->ensureConfirmationToken();
        $subscriber->save();

        $managedIds = $managedSubGroups->pluck('id')->all();
        $selectedIds = collect($this->selectedPreferenceSlugs($form, $payload))
            ->map(fn (string $slug) => $managedSubGroups->get($slug)?->id)
            ->filter()
            ->values()
            ->all();

        $existingManaged = $subscriber->allSubGroups()
            ->where('subscriber_group_id', $groupId)
            ->whereIn('subscriber_sub_groups.id', $managedIds)
            ->pluck('subscriber_sub_groups.id')
            ->all();

        $toAdd = array_diff($selectedIds, $existingManaged);
        $toReactivate = array_intersect($selectedIds, $existingManaged);
        $toRemove = array_diff($existingManaged, $selectedIds);

        foreach ($toReactivate as $subGroupId) {
            $subscriber->allSubGroups()->updateExistingPivot($subGroupId, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        if ($toAdd) {
            $subscriber->subGroups()->attach($toAdd, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        if ($toRemove) {
            $subscriber->allSubGroups()->updateExistingPivot($toRemove, [
                'unsubscribed_at' => now(),
            ]);
        }

        $subscriber = $subscriber->fresh(['subGroups.group']);
        $currentManagedIds = $subscriber->subGroups
            ->where('group.slug', $groupSlug)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $namesChanged = $this->normalize($previousFirstName) !== $this->normalize($subscriber->first_name)
            || $this->normalize($previousLastName) !== $this->normalize($subscriber->last_name);
        $preferencesChanged = $previousManagedIds !== $currentManagedIds;

        $status = match (true) {
            ! $wasExisting => 'subscribed',
            $previousStatus === 'unsubscribed' => 'resubscribed',
            $namesChanged || $preferencesChanged => 'subscription_updated',
            default => 'already_subscribed',
        };

        return [
            'subscriber' => $subscriber,
            'subscriber_group_id' => $group->id,
            'status' => $status,
            'message' => $this->messageForStatus($status, $form),
            'email_sent' => $this->dispatchLifecycleEmail($form, $subscriber, $status),
        ];
    }

    private function submitApplication(
        StatamicForm $form,
        array $payload,
        Request $request,
        ?StatamicSubmission $submission = null,
        bool $dispatchEmail = true,
    ): array
    {
        $collectionHandle = $this->collectionHandle($form);
        $group = $this->group($form);
        abort_if(! $group || ! $collectionHandle, 422, 'Application form is not linked to a valid subscriber group.');

        $this->assertFormIsOpen($form);
        $this->assertEligibleResidency($form, $payload);
        $this->assertAdultApplicant($payload);
        $this->assertValidTurnstile($form, $payload, $request);

        $targetSubGroup = $this->ensureTargetSubGroup($form, $group);
        $normalizedEmail = strtolower(trim((string) Arr::get($payload, 'email')));
        $normalizedPhone = $this->normalizePhone((string) Arr::get($payload, 'phone_number'));

        $duplicateByEmail = Subscriber::query()
            ->where('email', $normalizedEmail)
            ->whereHas('allSubGroups', fn ($query) => $query->where('subscriber_sub_groups.id', $targetSubGroup->id))
            ->first();

        $duplicateByPhone = Subscriber::query()
            ->whereHas('allSubGroups', fn ($query) => $query->where('subscriber_sub_groups.id', $targetSubGroup->id))
            ->where($this->applicationPhoneJsonPath($form), $normalizedPhone)
            ->first();

        if ($duplicateByEmail && $duplicateByPhone) {
            throw ValidationException::withMessages([
                'email' => 'We already have your record.',
            ]);
        }

        if ($duplicateByEmail) {
            throw ValidationException::withMessages([
                'email' => 'Application already received for this email address.',
            ]);
        }

        if ($duplicateByPhone) {
            throw ValidationException::withMessages([
                'phone_number' => 'Application already received for this phone number.',
            ]);
        }

        $subscriber = Subscriber::firstOrNew([
            'email' => $normalizedEmail,
        ]);

        [$firstName, $lastName] = $this->splitFullName((string) Arr::get($payload, 'full_name'));
        $applicationPayload = $this->applicationPayloadForStorage($payload, $request);
        $metadata = array_merge($subscriber->metadata ?? [], [
            'application_forms' => array_merge(
                Arr::get($subscriber->metadata ?? [], 'application_forms', []),
                [
                    $form->handle() => [
                        'submitted_at' => now()->toIso8601String(),
                        'group_slug' => $targetSubGroup->slug,
                        'phone_number' => $normalizedPhone,
                        'payload' => $applicationPayload,
                    ],
                ]
            ),
        ]);

        $subscriber->fill([
            'first_name' => $firstName ?: $subscriber->first_name,
            'last_name' => $lastName ?: $subscriber->last_name,
            'status' => 'active',
            'confirmed_at' => $subscriber->confirmed_at ?? now(),
            'unsubscribed_at' => null,
            'ip_address' => (string) Arr::get($applicationPayload, 'ip_address', $request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
            'metadata' => $metadata,
        ]);

        $subscriber->ensureConfirmationToken();
        $subscriber->save();

        $existingSubGroupIds = $subscriber->allSubGroups()
            ->pluck('subscriber_sub_groups.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (in_array((int) $targetSubGroup->id, $existingSubGroupIds, true)) {
            $subscriber->allSubGroups()->updateExistingPivot($targetSubGroup->id, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        } else {
            $subscriber->allSubGroups()->attach($targetSubGroup->id, [
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        $subscriber = $subscriber->fresh(['subGroups.group']);

        $emailSent = $dispatchEmail
            ? $this->dispatchLifecycleEmail($form, $subscriber, 'submitted', $applicationPayload, $submission)
            : false;

        if ($submission && $emailSent) {
            $this->annotateSubmission($submission, $this->applicationTracking->queuedAttributes());
        }

        return [
            'subscriber' => $subscriber,
            'subscriber_group_id' => $group->id,
            'status' => 'submitted',
            'message' => $this->successMessage($form),
            'email_sent' => $emailSent,
            'application_payload' => $applicationPayload,
        ];
    }

    public function preferenceOptions(StatamicForm $form): array
    {
        $fieldHandle = $this->preferenceFieldHandle($form);

        if (! $fieldHandle) {
            return [];
        }

        $field = $form->fields()->get($fieldHandle);

        if (! $field) {
            return [];
        }

        return collect($field->get('options', []))
            ->map(function ($label, $value) {
                if (is_array($label)) {
                    $optionValue = $label['key'] ?? $label['value'] ?? $value;
                    $optionLabel = $label['label'] ?? $label['text'] ?? $label['value'] ?? $optionValue;

                    return [
                        'slug' => Str::slug((string) $optionValue),
                        'label' => (string) $optionLabel,
                    ];
                }

                return [
                    'slug' => Str::slug((string) $value),
                    'label' => (string) $label,
                ];
            })
            ->filter(fn (array $option) => filled($option['slug']) && filled($option['label']))
            ->values()
            ->all();
    }

    public function selectedPreferenceSlugs(StatamicForm $form, array $payload): array
    {
        $fieldHandle = $this->preferenceFieldHandle($form);

        if (! $fieldHandle) {
            return [];
        }

        return collect(Arr::wrap(Arr::get($payload, $fieldHandle)))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => Str::slug((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function serializeField(Field $field): array
    {
        return [
            'handle' => $field->handle(),
            'type' => $field->type(),
            'display' => $field->display(),
            'instructions' => $field->instructions(),
            'required' => $field->isRequired(),
            'options' => collect($field->get('options', []))
                ->map(function ($label, $value) {
                    if (is_array($label)) {
                        return [
                            'value' => (string) ($label['key'] ?? $label['value'] ?? $value),
                            'label' => (string) ($label['label'] ?? $label['text'] ?? $label['value'] ?? $label['key'] ?? $value),
                        ];
                    }

                    return [
                        'value' => (string) $value,
                        'label' => (string) $label,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function messageForStatus(string $status, StatamicForm $form): string
    {
        return match ($status) {
            'already_subscribed' => 'You are already subscribed.',
            'subscription_updated' => 'You are already subscribed. Your details or preferences were updated.',
            'resubscribed' => 'Your subscription has been restored.',
            'submitted' => $this->successMessage($form),
            default => $this->successMessage($form),
        };
    }

    private function dispatchLifecycleEmail(StatamicForm $form, Subscriber $subscriber, string $status, array $submissionPayload = [], ?StatamicSubmission $submission = null): bool
    {
        if (! $this->shouldSendLifecycleEmail($form, $status)) {
            return false;
        }

        $collectionHandle = $this->collectionHandle($form);

        Mail::to($subscriber->email, $subscriber->full_name)->queue(
            new SubscriptionConfirmationMail(
                subscriber: $subscriber,
                status: $status,
                mailConfig: [
                    'collection_handle' => $collectionHandle,
                    'collection_label' => $this->collections->label($collectionHandle),
                    'sender' => $this->collections->sender($collectionHandle),
                    'privacy_url' => $this->privacyUrl($form),
                    'logo_url' => $this->logoUrl($form),
                    'brand_color' => $this->brandColor($form),
                    'subject' => $this->confirmationSubject($form, $status),
                    'body' => $this->confirmationBody($form, $status),
                    'submission_summary' => $this->confirmationSummary($form, $submissionPayload),
                    'summary_heading' => $form->get('newsletter_confirmation_summary_heading'),
                    'submission_id' => $submission?->id(),
                    'form_handle' => $submission ? $form->handle() : null,
                    'submission_mode' => $submission ? $this->submissionMode($form) : null,
                ],
            )
        );

        return true;
    }

    private function shouldSendLifecycleEmail(StatamicForm $form, string $status): bool
    {
        if (! $this->sendConfirmationEmail($form)) {
            return false;
        }

        return match ($status) {
            'subscribed', 'resubscribed' => true,
            'submitted' => true,
            'subscription_updated' => $this->sendUpdateEmail($form),
            default => false,
        };
    }

    private function confirmationSubject(StatamicForm $form, string $status): string
    {
        $collectionLabel = $this->collections->label($this->collectionHandle($form));
        $configured = match ($status) {
            'resubscribed' => $form->get('newsletter_resubscribe_subject') ?: $form->get('newsletter_confirmation_subject'),
            default => $form->get('newsletter_confirmation_subject'),
        };

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($status) {
            'resubscribed' => "Welcome back to {$collectionLabel}",
            'subscription_updated' => "Your {$collectionLabel} preferences were updated",
            'submitted' => (string) ($form->get('newsletter_confirmation_subject') ?: 'Your application has been received'),
            default => "Welcome to {$collectionLabel}",
        };
    }

    private function confirmationBody(StatamicForm $form, string $status): string
    {
        $collectionLabel = $this->collections->label($this->collectionHandle($form));
        $configured = match ($status) {
            'resubscribed' => $form->get('newsletter_resubscribe_body') ?: $form->get('newsletter_confirmation_body'),
            default => $form->get('newsletter_confirmation_body'),
        };

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($status) {
            'resubscribed' => "You are subscribed to {$collectionLabel} again. We will continue sending updates to this email address.",
            'subscription_updated' => "Your {$collectionLabel} subscriber details have been updated.",
            'submitted' => (string) ($form->get('newsletter_confirmation_body')
                ?: "Your application has been received.\n\nThis application does not automatically guarantee your selection. Applicants will receive an email on the status of their application a week or less after the deadline, with specific information on the next steps."),
            default => "Thank you for subscribing to {$collectionLabel}. We will send future updates to this email address.",
        };
    }

    private function confirmationSummary(StatamicForm $form, array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        $labelMap = [
            'full_name' => 'Full Name',
            'lga_name' => 'LGA',
            'ward_name' => 'Ward',
            'age' => 'Age',
            'confirm_above_18' => 'I confirm that I am 18 years or older.',
        ];

        return collect($this->confirmationSummaryFields($form))
            ->map(function (string $handle) use ($form, $payload) {
                $field = $form->fields()->get($handle);
                $value = Arr::get($payload, $handle);

                if ($field === null || $value === null || $value === '') {
                    return null;
                }

                if ($handle === 'confirm_above_18') {
                    $confirmed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

                    if ($confirmed !== true) {
                        return null;
                    }

                    return [
                        'handle' => $handle,
                        'label' => 'I confirm that I am 18 years or older.',
                        'value' => '',
                        'standalone' => true,
                    ];
                }

                return [
                    'handle' => $handle,
                    'label' => $labelMap[$handle] ?? $field->display(),
                    'value' => is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function assertFormIsOpen(StatamicForm $form): void
    {
        $closedAt = $this->closedAt($form);

        if ($closedAt && now()->greaterThan($closedAt)) {
            throw ValidationException::withMessages([
                'form' => $this->closedMessage($form),
            ]);
        }
    }

    private function assertEligibleResidency(StatamicForm $form, array $payload): void
    {
        $value = strtolower(trim((string) Arr::get($payload, 'resident_in_osun', '')));

        if (! in_array($value, ['yes', '1', 'true'], true)) {
            throw ValidationException::withMessages([
                'resident_in_osun' => $this->ineligibleMessage($form),
            ]);
        }
    }

    private function assertAdultApplicant(array $payload): void
    {
        $confirmed = filter_var(Arr::get($payload, 'confirm_above_18'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $age = (int) Arr::get($payload, 'age', 0);

        if ($confirmed !== true) {
            throw ValidationException::withMessages([
                'confirm_above_18' => 'You must confirm that you are 18 years or older.',
            ]);
        }

        if ($age < 18) {
            throw ValidationException::withMessages([
                'age' => 'Applicants must be 18 years or older.',
            ]);
        }
    }

    private function assertValidTurnstile(StatamicForm $form, array $payload, Request $request): void
    {
        $field = $this->turnstileFieldHandle($form);

        if (! $field) {
            return;
        }

        if ($this->shouldBypassTurnstile()) {
            return;
        }

        $token = trim((string) Arr::get($payload, $field, ''));
        $secret = (string) config('services.turnstile.secret');

        if ($token === '') {
            throw ValidationException::withMessages([
                $field => 'Security verification is required.',
            ]);
        }

        if ($secret === '') {
            throw ValidationException::withMessages([
                $field => 'Security verification is not configured.',
            ]);
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (! $response->ok() || ! (bool) $response->json('success')) {
            throw ValidationException::withMessages([
                $field => 'Security verification failed. Please try again.',
            ]);
        }
    }

    public function shouldBypassTurnstile(): bool
    {
        return ! app()->environment('production')
            && (bool) config('services.turnstile.bypass');
    }

    private function ensureTargetSubGroup(StatamicForm $form, SubscriberGroup $group): SubscriberSubGroup
    {
        $slug = $this->targetSubGroupSlug($form);

        if (! $slug) {
            throw ValidationException::withMessages([
                'form' => 'Application target subgroup is not configured.',
            ]);
        }

        return SubscriberSubGroup::query()->firstOrCreate(
            [
                'subscriber_group_id' => $group->id,
                'slug' => $slug,
            ],
            [
                'name' => $this->targetSubGroupName($form) ?: Str::of($slug)->replace('-', ' ')->title()->toString(),
                'description' => 'Auto-managed from public application intake.',
            ]
        );
    }

    private function applicationPhoneJsonPath(StatamicForm $form): string
    {
        return 'metadata->application_forms->' . $form->handle() . '->phone_number';
    }

    private function applicationPayloadForStorage(array $payload, Request $request): array
    {
        $payload = $payload;
        unset($payload['turnstile_token'], $payload['honeypot']);

        $payload['ip_address'] = (string) ($payload['ip_address'] ?? $request->ip() ?? '');
        $payload['device'] = (string) ($payload['device'] ?? Str::limit((string) $request->userAgent(), 255, ''));

        return $payload;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?: '';
    }

    private function splitFullName(string $value): array
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        if ($value === '') {
            return [null, null];
        }

        $parts = explode(' ', $value, 2);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }

    private function normalize(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }

    private function resolveNameField(array $payload, array $handles): ?string
    {
        foreach ($handles as $handle) {
            $value = Arr::get($payload, $handle);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
