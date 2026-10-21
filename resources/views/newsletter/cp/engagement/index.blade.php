@extends('statamic::layout')
@section('title', 'Engagement Ratings')

@section('content')
    <div class="newsletter-groups-header">
        <h1 class="newsletter-groups-title">Engagement Ratings</h1>
    </div>
    @if(session('success')) <div class="newsletter-groups-notice">{{ session('success') }}</div> @endif
    <div class="newsletter-engagement-page">
        <section class="newsletter-groups-card newsletter-engagement-card" aria-labelledby="engagement-rules-title">
            <div class="newsletter-engagement-heading">
                <div>
                    <h2 id="engagement-rules-title" class="newsletter-engagement-title">How ratings work</h2>
                    <p class="newsletter-engagement-copy">The score uses the 10 most recent campaign sends. A click earns 5 points, an open earns 2, and a failed or bounced send loses 2. A delivered email with no recorded open or click earns 0. A click takes precedence over an open on the same send.</p>
                </div>
                <form method="GET" action="{{ cp_route('newsletter.engagement.index') }}" class="newsletter-engagement-organisation">
                    <label for="organisation_id">Organisation</label>
                    <select id="organisation_id" name="organisation_id" class="newsletter-group-form-control" onchange="this.form.submit()">
                        @foreach($organisations as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $organisation->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="newsletter-engagement-table-wrap">
                <table class="newsletter-engagement-table">
                    <thead><tr><th scope="col">Rating</th><th scope="col">Current rule</th></tr></thead>
                    <tbody>
                        <tr><th scope="row">High</th><td>Score of at least 8 and a click within 30 days</td></tr>
                        <tr><th scope="row">Moderate</th><td>Score of at least 4 and engagement within 90 days</td></tr>
                        <tr><th scope="row">Inactive</th><td>No engagement across 10 sends, or last engagement more than 90 days ago</td></tr>
                        <tr><th scope="row">Low</th><td>Everything else, including a subscriber with little activity</td></tr>
                        <tr><th scope="row">Unsubscribed</th><td>Subscriber chose to stop receiving emails</td></tr>
                        <tr><th scope="row">Suppressed</th><td>Subscriber hard-bounced or complained</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="newsletter-engagement-note">A subscriber with no campaign sends starts as New / Unrated. A single temporary delivery failure does not permanently suppress them. Opens can be affected by email privacy features, so clicks are stronger evidence of engagement. Unsubscribed and suppressed subscribers cannot be selected for campaigns.</p>
        </section>

        <section class="newsletter-groups-card newsletter-engagement-card" aria-labelledby="engagement-recalculation-title">
            <h2 id="engagement-recalculation-title" class="newsletter-engagement-title">Recalculation</h2>
            <div class="newsletter-engagement-status">
                <span><strong>Last full run</strong>{{ $setting->last_recalculated_at?->format('d M Y, H:i') ?? 'Not yet run' }}</span>
                <span><strong>Next due</strong>{{ $setting->last_recalculated_at?->copy()->addDays($setting->interval_days)->format('d M Y, H:i') ?? 'Now' }}</span>
                <span><strong>Status</strong>{{ $setting->last_status ?? 'Not queued' }}</span>
            </div>
            @if($setting->last_error) <p class="newsletter-engagement-error" role="alert">Last run failed: {{ $setting->last_error }}</p> @endif
            <div class="newsletter-engagement-actions">
                <form method="POST" action="{{ cp_route('newsletter.engagement.update', $organisation) }}" class="newsletter-engagement-interval">
                    @csrf @method('PUT')
                    <label for="interval_days">Recalculate every (days, minimum 30)</label>
                    <div class="newsletter-engagement-interval-controls">
                        <input id="interval_days" class="newsletter-group-form-control" type="number" name="interval_days" min="30" max="3650" required value="{{ old('interval_days', $setting->interval_days) }}">
                        <button type="submit" class="newsletter-groups-button">Save interval</button>
                    </div>
                    @error('interval_days') <p class="newsletter-engagement-error" role="alert">{{ $message }}</p> @enderror
                </form>
                <form method="POST" action="{{ cp_route('newsletter.engagement.recalculate', $organisation) }}">
                    @csrf
                    <button type="submit" class="newsletter-groups-button-primary">Recalculate now</button>
                </form>
            </div>
        </section>

        <h2 class="newsletter-engagement-section-title">Group breakdown</h2>
        @foreach($groups as $group)
        <div class="newsletter-groups-card">
            <h2 class="newsletter-groups-card-title">{{ $group->name }}</h2>
            <div class="newsletter-groups-subgroups">
                @foreach(\App\Services\Newsletter\GroupEngagementService::SEGMENTS as $rating => $label)
                    <span class="newsletter-groups-chip">{{ $label }}: {{ $counts->get($group->id)?->firstWhere('rating', $rating)?->total ?? 0 }}</span>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
@endsection
