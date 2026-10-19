<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Newsletter\SubscriptionFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Forms\Form as StatamicForm;

class SubscriptionFormController extends Controller
{
    public function __construct(
        private readonly SubscriptionFormService $forms,
    ) {}

    public function schema(string $form)
    {
        $resolved = $this->resolvePublicForm($form);
        if (! $this->forms->isInsightCutover($resolved)) {
            $this->forms->syncManagedSubGroups($resolved);
        }

        return response()->json($this->forms->schema($resolved));
    }

    public function submit(Request $request, string $form)
    {
        $resolved = $this->resolvePublicForm($form);

        if ($request->filled($resolved->honeypot())) {
            return response()->json([
                'success' => true,
                'message' => $this->forms->successMessage($resolved),
            ]);
        }

        $input = $this->forms->prepareSubmissionPayload($resolved, $request->all(), $request);

        $fields = $resolved->blueprint()->fields()->addValues($input);
        $fields->validate();

        $payload = $fields->process()->values()->all();

        if ($this->forms->isInsightCutover($resolved)) {
            foreach (['country', 'profession', 'gender'] as $handle) {
                if (array_key_exists($handle, $input)) {
                    $payload[$handle] = $input[$handle];
                }
            }
            Validator::make($payload, [
                'country' => ['nullable', Rule::exists('countries', 'code')],
                'profession' => ['nullable', 'string', 'max:255'],
                'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            ])->validate();
        }

        if (! filled($payload['email'] ?? null)) {
            throw ValidationException::withMessages(['email' => 'Email is required.']);
        }

        if ($this->forms->submissionMode($resolved) === 'subscription'
            && $this->forms->preferenceFieldHandle($resolved)
            && $this->forms->selectedPreferenceSlugs($resolved, $payload) === []) {
            throw ValidationException::withMessages([
                $this->forms->preferenceFieldHandle($resolved) ?: 'preferences' => 'Select at least one preference.',
            ]);
        }

        if ($this->forms->isInsightCutover($resolved)) {
            $this->forms->validateInsightSelection($resolved, $payload);
        }

        if ($this->forms->submissionMode($resolved) === 'application') {
            $result = $this->forms->processApplication($resolved, $payload, $request);
            $subscriber = $result['subscriber'];
            $submission = $this->forms->storeSubmission($resolved, $payload);

            if ($submission) {
                $emailSent = $this->forms->dispatchStoredApplicationEmail(
                    $resolved,
                    $subscriber,
                    $result['application_payload'],
                    $submission,
                );

                $attributes = [
                    'subscription_status' => $result['status'],
                    'email_sent' => $emailSent,
                    'subscriber_id' => $subscriber->id,
                    'subscriber_group_id' => $result['subscriber_group_id'],
                ];

                if ($emailSent) {
                    $attributes = array_merge($attributes, $this->forms->queuedApplicationTrackingAttributes());
                }

                $this->forms->annotateSubmission($submission, $attributes);
                $result['email_sent'] = $emailSent;
            } else {
                $result['email_sent'] = $this->forms->dispatchApplicationEmailWithoutSubmission(
                    $resolved,
                    $subscriber,
                    $result['application_payload'],
                );
            }

            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'message' => $result['message'],
                'email_sent' => $result['email_sent'],
                'subscriber' => [
                    'email' => $subscriber->email,
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'status' => $subscriber->status,
                ],
            ]);
        }

        $submission = $this->forms->storeSubmission($resolved, $payload);
        $result = $this->forms->subscribe($resolved, $payload, $request, $submission);
        $subscriber = $result['subscriber'];

        if ($submission) {
            $this->forms->annotateSubmission($submission, [
                'subscription_status' => $result['status'],
                'email_sent' => $result['email_sent'],
                'subscriber_id' => $subscriber->id,
                'subscriber_group_id' => $result['subscriber_group_id'],
            ]);
        }

        if (! $request->expectsJson()) {
            return redirect()->back()->with('legacy_subscribe_success', $result['message']);
        }

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'message' => $result['message'],
            'email_sent' => $result['email_sent'],
            'subscriber' => [
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'status' => $subscriber->status,
            ],
        ]);
    }

    public function resolvePublicForm(string $identifier): StatamicForm
    {
        $form = $this->forms->resolveForm($identifier);

        abort_if(! $form, 404);

        return $form;
    }
}
