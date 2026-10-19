@extends('statamic::layout')
@section('title', 'Subscription Messages')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Subscription messages</h1>
            <p class="form-platform-description">{{ $organisation->name }} &middot; {{ $form->title() }}</p>
        </div>
        <a href="{{ cp_route('subscription-forms.index') }}" class="form-platform-button">Back to subscription forms</a>
    </div>

    <div class="form-platform-card form-platform-stack">
        <p class="form-platform-help">This existing Statamic form keeps its public URL and subscriber lifecycle. These settings control its automatic responses; audience choices remain on the form blueprint.</p>

        <form method="POST" action="{{ cp_route('subscription-forms.legacy.update', $form->handle()) }}" class="form-platform-stack">
            @csrf
            @method('PUT')

            <div class="form-platform-field">
                <label class="form-platform-label" for="legacy-success-message">Message after submission</label>
                <input id="legacy-success-message" type="text" name="newsletter_success_message" value="{{ old('newsletter_success_message', $form->get('newsletter_success_message')) }}" class="input-text form-platform-control" required>
            </div>

            <label class="form-platform-checkbox">
                <input type="checkbox" name="newsletter_send_confirmation_email" value="1" @checked(old('newsletter_send_confirmation_email', $form->get('newsletter_send_confirmation_email')))> Send email after a new subscription or resubscription
            </label>
            <p class="form-platform-help">Keep this enabled unless another verified confirmation path exists. Without confirmation, new subscribers remain pending and cannot receive newsletters.</p>
            <label class="form-platform-checkbox">
                <input type="checkbox" name="newsletter_send_update_email" value="1" @checked(old('newsletter_send_update_email', $form->get('newsletter_send_update_email')))> Send email when existing preferences are updated
            </label>

            @foreach([
                'confirmation' => 'New subscription',
                'resubscribe' => 'Resubscription',
            ] as $kind => $label)
                <div class="form-platform-field">
                    <label class="form-platform-label" for="legacy-{{ $kind }}-subject">{{ $label }} email subject</label>
                    <input id="legacy-{{ $kind }}-subject" type="text" name="newsletter_{{ $kind }}_subject" value="{{ old('newsletter_'.$kind.'_subject', $form->get('newsletter_'.$kind.'_subject')) }}" class="input-text form-platform-control">
                </div>
                <div class="form-platform-field">
                    <label class="form-platform-label" for="legacy-{{ $kind }}-body">{{ $label }} email message</label>
                    <textarea id="legacy-{{ $kind }}-body" name="newsletter_{{ $kind }}_body" rows="6" class="input-text form-platform-control">{{ old('newsletter_'.$kind.'_body', $form->get('newsletter_'.$kind.'_body')) }}</textarea>
                </div>
            @endforeach

            @if($errors->any())
                <div class="form-platform-error">{{ $errors->first() }}</div>
            @endif
            <div class="form-platform-actions">
                <button type="submit" class="form-platform-button-primary">Save messages</button>
            </div>
        </form>
    </div>
@endsection
