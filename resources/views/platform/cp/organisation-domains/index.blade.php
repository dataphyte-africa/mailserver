@extends('statamic::layout')
@section('title', 'Organisation Domains')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Organisation Domains</h1>
            <p class="form-platform-description">
                Add the source domain for each collection organisation. The newsletter domain is generated as the configured subdomain, for example nl.dataphyte.org.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="form-platform-alert form-platform-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('warning'))
        <div class="form-platform-alert form-platform-alert-warning">{{ session('warning') }}</div>
    @endif

    @if($errors->any())
        <div class="form-platform-error">
            <p class="form-platform-error-title">Please correct the highlighted fields.</p>
            <ul class="form-platform-error-list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-platform-stack">
        @forelse($organisations as $organisation)
            @php
                $record = $domains->dnsRecord($organisation);
                $origins = $domains->allowedOrigins($organisation);
            @endphp
            <div class="form-platform-card form-platform-stack">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $organisation->name }}</h2>
                        <p class="form-platform-meta">
                            Collection: {{ $organisation->primary_collection_handle ?: 'not linked' }}
                            &middot;
                            Status: {{ str_replace('_', ' ', $organisation->newsletter_domain_status ?: 'unconfigured') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ cp_route('organisation-domains.verify', $organisation) }}">
                        @csrf
                        <button type="submit" class="form-platform-button">Verify DNS</button>
                    </form>
                </div>

                <form method="POST" action="{{ cp_route('organisation-domains.update', $organisation) }}" class="form-platform-stack">
                    @csrf
                    <div class="form-platform-grid">
                        <div class="form-platform-field">
                            <label class="form-platform-label">Source domain</label>
                            <input type="text" name="source_domain" value="{{ old('source_domain', $organisation->source_domain) }}" class="input-text form-platform-control" placeholder="dataphyte.org">
                            <p class="form-platform-help">Enter the root/source domain. Do not include https:// or the newsletter subdomain.</p>
                        </div>

                        <div class="form-platform-field">
                            <label class="form-platform-label">Newsletter subdomain</label>
                            <input type="text" name="newsletter_subdomain" value="{{ old('newsletter_subdomain', $organisation->newsletter_subdomain ?: config('platform.domain.newsletter_subdomain', 'nl')) }}" class="input-text form-platform-control" placeholder="nl">
                            <p class="form-platform-help">The app derives the public newsletter domain from this value.</p>
                        </div>

                        <div class="form-platform-field">
                            <label class="form-platform-label">DNS record type</label>
                            <select name="newsletter_dns_record_type" class="input-text form-platform-control">
                                @foreach(['A', 'CNAME'] as $type)
                                    <option value="{{ $type }}" @selected(($organisation->newsletter_dns_record_type ?: config('platform.domain.newsletter_dns_record_type', 'A')) === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-platform-field">
                            <label class="form-platform-label">Expected DNS value</label>
                            <input type="text" name="newsletter_dns_expected_value" value="{{ old('newsletter_dns_expected_value', $organisation->newsletter_dns_expected_value ?: config('platform.domain.newsletter_dns_target')) }}" class="input-text form-platform-control" placeholder="Server IP or canonical host">
                            <p class="form-platform-help">Use the mailserver IP for A records, or the canonical host for CNAME records.</p>
                        </div>
                    </div>

                    <div class="form-platform-grid">
                        <div class="form-platform-card form-platform-nested-card">
                            <h3>Derived public URLs</h3>
                            <p class="form-platform-meta">Newsletter domain: <strong>{{ $organisation->newsletter_domain ?: 'not configured' }}</strong></p>
                            <p class="form-platform-meta">Forms: {{ $organisation->newsletter_domain ? 'https://'.$organisation->newsletter_domain.'/forms/{form-slug}' : 'configure source domain first' }}</p>
                            <p class="form-platform-meta">Preference centre: {{ $organisation->newsletter_domain ? 'https://'.$organisation->newsletter_domain.'/preferences' : 'configure source domain first' }}</p>
                            <p class="form-platform-meta">Unsubscribe: {{ $organisation->newsletter_domain ? 'https://'.$organisation->newsletter_domain.'/unsubscribe/{signed-token}' : 'configure source domain first' }}</p>
                        </div>

                        <div class="form-platform-card form-platform-nested-card">
                            <h3>DNS instruction</h3>
                            @if($record['host'])
                                <p class="form-platform-meta">Type: <strong>{{ $record['type'] }}</strong></p>
                                <p class="form-platform-meta">Name: <strong>{{ $record['name'] }}</strong></p>
                                <p class="form-platform-meta">Value: <strong>{{ $record['value'] ?: 'not configured' }}</strong></p>
                                <p class="form-platform-meta">TTL: {{ $record['ttl'] }}</p>
                            @else
                                <p class="form-platform-meta">Add a source domain to generate the DNS record.</p>
                            @endif
                        </div>
                    </div>

                    <div class="form-platform-card form-platform-nested-card">
                        <h3>Inherited form origins</h3>
                        @if($origins === [])
                            <p class="form-platform-meta">No inherited origins yet.</p>
                        @else
                            @foreach($origins as $origin)
                                <code class="form-platform-chip">{{ $origin }}</code>
                            @endforeach
                        @endif
                    </div>

                    <div class="form-platform-actions">
                        <button type="submit" class="form-platform-button-primary">Save Domain Settings</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="form-platform-card form-platform-empty">
                No organisation is available in your active scope.
            </div>
        @endforelse
    </div>
@endsection
