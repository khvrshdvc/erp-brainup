@props(['paginator'])

@if ($paginator->hasPages())
    <div class="pager">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm" style="opacity: .45">{{ __('← Oldingi') }}</span>
        @else
            <a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">{{ __('← Oldingi') }}</a>
        @endif

        <span class="card-sub">{{ __('Sahifa :currentpage / :lastpage · jami :total', ['currentpage' => $paginator->currentPage(), 'lastpage' => $paginator->lastPage(), 'total' => $paginator->total()]) }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">{{ __('Keyingi →') }}</a>
        @else
            <span class="btn btn-sm" style="opacity: .45">{{ __('Keyingi →') }}</span>
        @endif
    </div>
@endif
