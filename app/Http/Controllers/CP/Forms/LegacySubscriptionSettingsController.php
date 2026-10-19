<?php

namespace App\Http\Controllers\CP\Forms;

use App\Models\Organisation;
use App\Services\Newsletter\SubscriptionFormService;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\Facades\Form;

class LegacySubscriptionSettingsController
{
    public function edit(Request $request, string $handle, SubscriptionFormService $subscriptions, CpOperatorScope $scope): View
    {
        [$form, $organisation] = $this->resolve($request, $handle, $subscriptions, $scope);

        return view('forms.cp.legacy-settings', compact('form', 'organisation'));
    }

    public function update(Request $request, string $handle, SubscriptionFormService $subscriptions, CpOperatorScope $scope): RedirectResponse
    {
        [$form] = $this->resolve($request, $handle, $subscriptions, $scope);

        $data = $request->validate([
            'newsletter_success_message' => ['required', 'string', 'max:255'],
            'newsletter_send_confirmation_email' => ['nullable', 'boolean'],
            'newsletter_send_update_email' => ['nullable', 'boolean'],
            'newsletter_confirmation_subject' => ['nullable', 'string', 'max:255'],
            'newsletter_confirmation_body' => ['nullable', 'string', 'max:5000'],
            'newsletter_resubscribe_subject' => ['nullable', 'string', 'max:255'],
            'newsletter_resubscribe_body' => ['nullable', 'string', 'max:5000'],
        ]);

        $form->merge([
            ...$data,
            'newsletter_send_confirmation_email' => $request->boolean('newsletter_send_confirmation_email'),
            'newsletter_send_update_email' => $request->boolean('newsletter_send_update_email'),
        ])->save();

        return redirect(cp_route('subscription-forms.legacy.edit', $handle))
            ->with('success', 'Subscription messages updated.');
    }

    private function resolve(Request $request, string $handle, SubscriptionFormService $subscriptions, CpOperatorScope $scope): array
    {
        $form = Form::find($handle);
        abort_unless($form && $subscriptions->isNewsletterForm($form)
            && $subscriptions->submissionMode($form) === 'subscription', 404);

        $organisation = Organisation::query()
            ->where('primary_collection_handle', $subscriptions->collectionHandle($form))
            ->first();
        abort_unless($organisation, 404);
        abort_unless($scope->isSuper($request->user())
            || in_array($organisation->getKey(), $scope->organisationIds($request->user()), true), 403);

        return [$form, $organisation];
    }
}
