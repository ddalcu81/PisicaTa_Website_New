@props(['articles'])
@if ($articles->hasPages())
    <nav class="pagination" aria-label="Paginare">
        @if ($articles->previousPageUrl()) <a rel="prev" href="{{ $articles->previousPageUrl() }}">← Anterioară</a> @endif
        <span>Pagina {{ $articles->currentPage() }} din {{ $articles->lastPage() }}</span>
        @if ($articles->nextPageUrl()) <a rel="next" href="{{ $articles->nextPageUrl() }}">Următoare →</a> @endif
    </nav>
@endif
