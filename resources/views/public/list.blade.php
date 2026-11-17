@php
    $isSearch = isset($query);
    $parent = $category->parent ?? null;
    $canonicalPath = isset($category) ? $category->publicUrl() : ($isSearch ? '/cauta' : '/articole.php');
    $canonicalUrl = rtrim(config('app.url'), '/').$canonicalPath.($articles->currentPage() > 1 ? '?page='.$articles->currentPage() : '');
@endphp
<x-public-layout :title="$heading.' — Pisica Ta'" :description="$intro ?? null" :canonical="$canonicalUrl" :no-index="$isSearch" :categories="$categories" :category="$category ?? null">
    <nav class="breadcrumbs" aria-label="Fir de navigare">
        <a href="/">Acasă</a><span aria-hidden="true">/</span>
        @if ($parent)
            <a href="{{ $parent->publicUrl() }}">{{ $parent->name }}</a><span aria-hidden="true">/</span>
        @endif
        <span aria-current="page">{{ $isSearch ? 'Căutare' : ($category->name ?? 'Articole') }}</span>
    </nav>
    <section class="list-heading">
        <h1>{{ $heading }}</h1>
        @if (! empty($intro)) <p>{{ $intro }}</p> @endif
        @if ($isSearch)
            <form class="list-search" method="get" action="{{ route('publication.search', absolute: false) }}" role="search">
                <label class="sr-only" for="query">Caută în articole</label>
                <input id="query" type="search" name="q" maxlength="100" value="{{ $query }}" placeholder="Caută în articole…" required>
                <button class="button primary">Caută</button>
            </form>
        @endif
    </section>
    @php($filters = isset($category) ? $category->children : $categories)
    @if (! $isSearch && $filters->isNotEmpty())
        <nav class="filter-list" aria-label="Categorii">
            @foreach ($filters as $filter)
                <a href="{{ $filter->publicUrl() }}">{{ $filter->name }}</a>
            @endforeach
        </nav>
    @endif
    @if ($articles->isNotEmpty())
        <div class="card-grid list-grid">
            @foreach ($articles as $article)
                <x-article-card :article="$article" />
            @endforeach
        </div>
        <x-pagination :articles="$articles" />
    @else
        <p class="empty">{{ $isSearch ? ($query === '' ? 'Introdu un termen pentru a căuta.' : 'Nu am găsit articole. Încearcă alt termen.') : 'Nu sunt articole publicate aici.' }}</p>
    @endif
</x-public-layout>
