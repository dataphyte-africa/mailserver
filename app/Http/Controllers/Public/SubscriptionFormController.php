<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Newsletter\SubscriptionFormService;
use Illuminate\Http\Request;
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
        $this->forms->syncManagedSubGroups($resolved);

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

        if (! filled($payload['email'] ?? null)) {
            throw ValidationException::withMessages(['email' => 'Email is required.']);
        }

        $submission = $this->forms->storeSubmission($resolved, $payload);
        $result = $this->forms->subscribe($resolved, $payload, $request);
        $subscriber = $result['subscriber'];

        if ($submission) {
            $this->forms->annotateSubmission($submission, [
                'subscription_status' => $result['status'],
                'email_sent' => $result['email_sent'],
                'subscriber_id' => $subscriber->id,
                'subscriber_group_id' => $result['subscriber_group_id'],
            ]);
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
