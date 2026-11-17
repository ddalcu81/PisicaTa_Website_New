@props(['article'])
<article class="article-card">
    <a class="card-image" href="{{ $article->publicUrl() }}" tabindex="-1" aria-hidden="true">
        <img src="{{ $article->cover() }}" alt="" width="740" height="493" loading="lazy" decoding="async">
    </a>
    <div class="card-copy">
        <a class="eyebrow" href="{{ $article->category->publicUrl() }}">{{ $article->category->name }}</a>
        <h3><a href="{{ $article->publicUrl() }}">{{ $article->title }}</a></h3>
        @if ($article->summary)
            <p>{{ $article->summary }}</p>
        @endif
        <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('j F Y') }}</time>
    </div>
</article>
