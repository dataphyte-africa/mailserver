<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product?->name ?? $organisation->name }} newsletters</title>
    <style>
        :root { color-scheme: light; font-family: Georgia, serif; color: #182638; background: #f3f0e9; }
        body { margin: 0; }
        main { max-width: 740px; margin: 0 auto; padding: 56px 24px 80px; }
        header { border-bottom: 2px solid #182638; padding-bottom: 24px; margin-bottom: 30px; }
        .eyebrow { font: 700 12px/1.4 sans-serif; text-transform: uppercase; letter-spacing: .13em; color: #52677c; }
        h1 { font-size: clamp(2.2rem, 6vw, 4rem); line-height: 1.05; margin: 12px 0; }
        p { line-height: 1.55; }
        article { border-bottom: 1px solid #cbd2d4; padding: 22px 0; }
        article a { color: #182638; font-size: 1.45rem; font-weight: 700; text-decoration: none; }
        article a:hover, article a:focus { text-decoration: underline; }
        time { display: block; margin-top: 8px; font: 13px sans-serif; color: #52677c; }
        nav { margin-top: 32px; font: 14px sans-serif; }
        nav a { color: #182638; margin-right: 18px; }
    </style>
</head>
<body>
<main>
    <header>
        <span class="eyebrow">{{ $organisation->name }}</span>
        <h1>{{ $product?->name ?? $organisation->name }} newsletters</h1>
        <p>Browse previous issues, including those published before you subscribed.</p>
    </header>
    @forelse($issues as $issue)
        <article>
            <a href="{{ route('newsletter.archive.show', $issue) }}">{{ $issue->subject ?: $issue->name }}</a>
            <time datetime="{{ $issue->sent_at->toDateString() }}">{{ $issue->sent_at->format('j F Y') }}</time>
        </article>
    @empty
        <p>No past issues are available yet.</p>
    @endforelse
    <nav aria-label="Archive pages">
        @if($issues->previousPageUrl())<a href="{{ $issues->previousPageUrl() }}">Newer issues</a>@endif
        @if($issues->nextPageUrl())<a href="{{ $issues->nextPageUrl() }}">Older issues</a>@endif
    </nav>
</main>
</body>
</html>
