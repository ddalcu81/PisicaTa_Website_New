<x-public-layout :categories="$categories">
    <section class="home-hero" aria-labelledby="home-heading">
        <div class="hero-copy">
            <p class="eyebrow hero-kicker"><span aria-hidden="true"></span> Ghidul iubitorilor de pisici</p>
            <h1 id="home-heading">Totul despre<br><em>pisica ta.</em></h1>
            <p class="hero-description">Sfaturi despre îngrijire, sănătate și micile obiceiuri care fac viața cu o pisică atât de specială.</p>
            <a class="button primary" href="{{ route('publication.index', absolute: false) }}">Explorează articolele <span aria-hidden="true">↗</span></a>
        </div>
        <picture class="hero-picture">
            <img src="/media/editorial/hero.webp" srcset="/media/editorial/hero-small.webp 640w, /media/editorial/hero.webp 1280w"
                 sizes="(max-width: 740px) 100vw, 58vw" width="1280" height="853" fetchpriority="high" alt="Pisică roșcată care se odihnește pe o canapea de in">
        </picture>
    </section>

    <div class="home-content">
        <section aria-labelledby="latest-title">
            <div class="section-heading">
                <h2 id="latest-title">Ultimele articole</h2>
                <a class="text-link" href="{{ route('publication.index', absolute: false) }}">Vezi toate <span aria-hidden="true">↗</span></a>
            </div>
            <div class="card-grid home-grid">
                @forelse ($featured ? $latest->prepend($featured) : $latest as $article)
                    <x-article-card :article="$article" />
                @empty
                    <p class="empty">Articolele publicate vor apărea aici.</p>
                @endforelse
            </div>
        </section>

        <aside class="topic-panel" aria-labelledby="topics-title">
            <h2 id="topics-title">Categorii</h2>
            <ul>
                @foreach ($categories as $category)
                    <li><a href="{{ $category->publicUrl() }}">{{ $category->name }} <span aria-hidden="true">↗</span></a></li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-public-layout>
