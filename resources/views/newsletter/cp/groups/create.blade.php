@extends('statamic::layout')
@section('title', 'Create Group')

@section('content')
    <div class="newsletter-group-form-page">
    <div class="newsletter-group-form-header">
        <div>
            <a href="{{ cp_route('newsletter.groups.index') }}" class="newsletter-group-form-back">&larr; Groups</a>
            <h1 class="newsletter-group-form-title">Create Group</h1>
            <p class="newsletter-group-form-subtitle">Create a private organisation audience. It will not appear on public subscription forms.</p>
        </div>
    </div>

    <form method="POST" action="{{ cp_route('newsletter.groups.store') }}" class="newsletter-group-form">
        @csrf
        <div class="newsletter-group-form-card">
            <div class="newsletter-group-form-field">
                <label class="newsletter-group-form-label">Group Name <span>*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="newsletter-group-form-control @error('name') newsletter-group-form-control-error @enderror"
                       placeholder="e.g. Culture Newsletter" required>
                @error('name') <p class="newsletter-group-form-error">{{ $message }}</p> @enderror
            </div>
            <div class="newsletter-group-form-field">
                <label class="newsletter-group-form-label">Organisation <span>*</span></label>
                <select name="organisation_id" class="newsletter-group-form-control @error('organisation_id') newsletter-group-form-control-error @enderror" required>
                    <option value="">Select an organisation</option>
                    @foreach($organisations as $organisation)
                        <option value="{{ $organisation->getKey() }}" @selected((string) old('organisation_id', $organisations->count() === 1 ? $organisations->first()->getKey() : '') === (string) $organisation->getKey())>
                            {{ $organisation->name }}
                        </option>
                    @endforeach
                </select>
                <p class="newsletter-group-form-help">The current rating segments are created automatically.</p>
                @error('organisation_id') <p class="newsletter-group-form-error">{{ $message }}</p> @enderror
            </div>
            <div class="newsletter-group-form-field">
                <label class="newsletter-group-form-label">Description</label>
                <textarea name="description" rows="2"
                          class="newsletter-group-form-control newsletter-group-form-textarea">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="newsletter-group-form-actions">
            <button type="submit" class="newsletter-groups-button-primary">Create Group</button>
            <a href="{{ cp_route('newsletter.groups.index') }}" class="newsletter-groups-button">Cancel</a>
        </div>
    </form>
    </div>
@endsection
