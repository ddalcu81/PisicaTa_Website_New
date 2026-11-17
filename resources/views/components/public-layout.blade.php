@php
    $title = $title ?? 'Pisica Ta — Ghiduri pentru iubitorii de pisici';
    $description = $description ?? 'Articole în limba română despre îngrijirea, sănătatea, hrana, comportamentul și rasele pisicilor.';
    $canonical = $canonical ?? rtrim(config('app.url'), '/').'/';
    $socialImage = $socialImage ?? rtrim(config('app.url'), '/').'/media/editorial/hero.webp';
    $topicNames = [
        'notiuni-de-baza-despre-pisici' => 'Îngrijire',
        'sanatatea-pisicilor' => 'Sănătate',
        'comportamentul-si-dresajul-pisicilor' => 'Comportament',
        'hrana-si-nutritia-pisicilor' => 'Nutriție',
        'rase' => 'Rase',
    ];
    $topics = collect($categories ?? [])->keyBy('slug');
@endphp
<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf8f2">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if (! config('pisicata.indexable') || ($noIndex ?? false))
        <meta name="robots" content="noindex, follow">
    @endif
    <meta property="og:site_name" content="Pisica Ta">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:type" content="{{ $socialType ?? 'website' }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta name="twitter:card" content="summary_large_image">
    @if (isset($structuredData))
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
    @vite('resources/css/site.css')
</head>
<body>
    <a class="skip" href="#continut">Sari la conținut</a>
    <header class="site-header">
        <div class="masthead wrap">
            <details class="mobile-nav">
                <summary aria-label="Meniu"><span class="hamburger" aria-hidden="true"></span></summary>
                <nav aria-label="Meniu mobil">
                    <a href="{{ route('publication.index', absolute: false) }}">Toate articolele</a>
                    @foreach ($topicNames as $slug => $label)
                        @if ($topics->has($slug)) <a href="{{ $topics[$slug]->publicUrl() }}">{{ $label }}</a> @endif
                    @endforeach
                </nav>
            </details>
            <a class="brand" href="/" aria-label="Pisica Ta, acasă">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span>
                <span>Pisica<span class="brand-space"> </span><em>Ta</em><span class="brand-period">.</span></span>
            </a>
            <form class="header-search" action="{{ route('publication.search', absolute: false) }}" method="get" role="search">
                <label class="sr-only" for="site-search">Caută articole</label>
                <input id="site-search" type="search" name="q" placeholder="Caută articole…" maxlength="100" required>
                <button aria-label="Caută" type="submit"><span class="search-icon" aria-hidden="true"></span></button>
            </form>
            <a class="mobile-search" href="{{ route('publication.search', absolute: false) }}" aria-label="Caută articole"><span class="search-icon" aria-hidden="true"></span></a>
        </div>
        <nav class="primary-nav" aria-label="Navigare principală">
            <div class="wrap nav-inner">
                <a href="{{ route('publication.index', absolute: false) }}" @if(request()->routeIs('publication.index')) aria-current="page" @endif>Toate articolele</a>
                @foreach ($topicNames as $slug => $label)
                    @if ($topics->has($slug))
                        <a href="{{ $topics[$slug]->publicUrl() }}" @if(($category->slug ?? $article->category->slug ?? '') === $slug) aria-current="page" @endif>{{ $label }}</a>
                    @endif
                @endforeach
            </div>
        </nav>
    </header>

    <main class="wrap" id="continut">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="wrap footer-inner">
            <a class="brand footer-brand" href="/" aria-label="Pisica Ta, acasă"><span>Pisica <em>Ta</em><span class="brand-period">.</span></span></a>
            <span>© {{ now()->year }} Pisica Ta</span>
            <a href="{{ route('publication.index', absolute: false) }}">Toate articolele <span aria-hidden="true">↗</span></a>
        </div>
    </footer>
    {{ $scripts ?? '' }}
</body>
</html>
