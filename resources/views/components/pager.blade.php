@props(['paginator'])

@if ($paginator->hasPages())
    <div class="pager">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm" style="opacity: .45">← Oldingi</span>
        @else
            <a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">← Oldingi</a>
        @endif

        <span class="card-sub">Sahifa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} · jami {{ $paginator->total() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">Keyingi →</a>
        @else
            <span class="btn btn-sm" style="opacity: .45">Keyingi →</span>
        @endif
    </div>
@endif
