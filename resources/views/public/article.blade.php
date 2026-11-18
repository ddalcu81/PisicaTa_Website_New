@php
    $rootUrl = rtrim(config('app.url'), '/');
    $canonical = $rootUrl.$article->publicUrl();
    $plain = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($article->body_html)));
    $description = $article->summary ?: \Illuminate\Support\Str::limit(trim($plain), 155);
    $image = $rootUrl.$article->cover();
    $cover = $article->sourcedCover();
    $published = $article->published_at->toDateString();
    $parent = $article->category->parent;
    $crumbs = collect([['Acasă', '/'], $parent ? [$parent->name, $parent->publicUrl()] : null,
        [$article->category->name, $article->category->publicUrl()], [$article->title, $article->publicUrl()]])->filter()->values();
    $structured = [
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Article', 'headline' => $article->title, 'description' => $description, 'datePublished' => $published,
                'mainEntityOfPage' => $canonical, 'image' => [$image], 'inLanguage' => 'ro',
                'publisher' => ['@type' => 'Organization', 'name' => 'Pisica Ta', 'url' => $rootUrl.'/']],
            ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs->map(fn($crumb, $i) =>
                ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $rootUrl.$crumb[1]])->all()],
        ],
    ];
    $substantial = preg_match_all('/[\p{L}\p{N}]+/u', $plain) >= 300;
@endphp
<x-public-layout :title="$article->title.' — Pisica Ta'" :description="$description" :canonical="$canonical" :social-image="$image" social-type="article" :structured-data="$structured" :categories="$categories" :article="$article">
    <nav class="breadcrumbs" aria-label="Fir de navigare">
        @foreach ($crumbs->slice(0, -1) as [$name, $url])
            <a href="{{ $url }}">{{ $name }}</a>
            @if (! $loop->last) <span aria-hidden="true">/</span> @endif
        @endforeach
    </nav>
    <div class="reading-grid">
        <article class="reading">
            <header class="article-header">
                <a class="eyebrow" href="{{ $article->category->publicUrl() }}">{{ $article->category->name }}</a>
                <h1>{{ $article->title }}</h1>
                @if ($article->summary) <p class="article-deck">{{ $article->summary }}</p> @endif
                <time datetime="{{ $published }}">{{ $article->published_at->translatedFormat('j F Y') }}</time>
            </header>
            <figure class="article-cover">
                <img src="{{ $article->cover() }}" alt="{{ $article->coverAlt() }}"
                     width="{{ $cover['width'] ?? 740 }}" height="{{ $cover['height'] ?? 493 }}" fetchpriority="high">
                @if ($cover)
                    <figcaption>
                        Foto <a href="{{ $cover['source'] }}" title="{{ $cover['title'] }}">{{ $cover['author'] ?: 'sursa originală' }}</a>
                        · <a href="{{ $cover['license_url'] }}">{{ $cover['license'] }}</a>
                        · Redimensionată pentru web
                    </figcaption>
                @endif
            </figure>
            <div class="prose">{!! $article->body_html !!}</div>
            @if ($substantial)
                <x-ad-slot placement="article" />
            @endif
        </article>
        <aside class="reading-aside">
            <div class="topic-panel">
                <h2>Explorează</h2>
                <ul>
                    @foreach ($categories as $topic)
                        <li><a href="{{ $topic->publicUrl() }}">{{ $topic->name }} <span aria-hidden="true">↗</span></a></li>
                    @endforeach
                </ul>
            </div>
            @if ($substantial)
                <x-ad-slot placement="rail" />
            @endif
        </aside>
    </div>
    @if ($related->isNotEmpty())
        <section class="related" aria-labelledby="related-heading">
            <div class="section-heading">
                <h2 id="related-heading">Din aceeași categorie</h2>
                <a class="text-link" href="{{ $article->category->publicUrl() }}">Vezi categoria <span aria-hidden="true">↗</span></a>
            </div>
            <div class="card-grid list-grid">
                @foreach ($related as $item) <x-article-card :article="$item" /> @endforeach
            </div>
        </section>
    @endif
    @if ($substantial && config('pisicata.indexable') && config('pisicata.adsense.enabled') && preg_match('/^ca-pub-\d{16}$/D', (string) config('pisicata.adsense.client')))
        <x-slot:scripts>
            <script>
                (() => {
                    const slots = [...document.querySelectorAll('.ad-slot[data-ad-slot]')]
                        .filter(slot => slot.getBoundingClientRect().width > 0);
                    if (!slots.length) return;
                    const client = @json(config('pisicata.adsense.client'));
                    const script = document.createElement('script');
                    script.async = true;
                    script.crossOrigin = 'anonymous';
                    script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + client;
                    document.head.append(script);
                    const observer = new IntersectionObserver(entries => {
                        for (const entry of entries) {
                            if (!entry.isIntersecting) continue;
                            const element = entry.target;
                            observer.unobserve(element);
                            if (!element.getBoundingClientRect().width) continue;
                            const unit = document.createElement('ins');
                            unit.className = 'adsbygoogle';
                            unit.style.display = 'block';
                            unit.dataset.adClient = client;
                            unit.dataset.adSlot = element.dataset.adSlot;
                            unit.dataset.adFormat = 'auto';
                            unit.dataset.fullWidthResponsive = 'true';
                            element.querySelector('.ad-space').append(unit);
                            (window.adsbygoogle = window.adsbygoogle || []).push({});
                        }
                    }, {rootMargin: '240px'});
                    slots.forEach(slot => observer.observe(slot));
                })();
            </script>
        </x-slot:scripts>
    @endif
</x-public-layout>
