@extends('statamic::layout')
@section('title', 'Subscriber Import')

@section('content')
    @if(in_array($result['status'], ['queued', 'processing'], true))
        <meta http-equiv="refresh" content="5">
    @endif
    <div class="max-w-2xl space-y-5">
        <a href="{{ cp_route('newsletter.subscribers.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Subscribers</a>
        <div class="card p-6 space-y-4">
            <h1 class="text-2xl font-bold">Subscriber import</h1>
            <p class="text-sm text-gray-600">Import ID: <code>{{ $importId }}</code></p>
            <p class="font-semibold">
                @if($result['status'] === 'complete')
                    Import complete
                @elseif($result['status'] === 'failed')
                    Import stopped
                @else
                    Import {{ $result['status'] }}. This page updates automatically.
                @endif
            </p>
            <p>{{ number_format($result['processed']) }} processed &middot; {{ number_format($result['imported']) }} imported &middot; {{ number_format($result['skipped']) }} skipped</p>
            @if($result['errors'] !== [])
                <h2 class="font-semibold">First {{ count($result['errors']) }} issues</h2>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
