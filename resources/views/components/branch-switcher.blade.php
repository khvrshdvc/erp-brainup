@php
    $currentName = $current ? $branches->firstWhere('id', $current)?->name : null;
    $label = $currentName ?? __('Barcha filiallar');
@endphp

<form method="POST" action="{{ route('branch.switch') }}" class="lang-switch branch-switch"
    x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    @csrf

    <button type="button" class="btn branch-btn" @click="open = ! open" aria-haspopup="listbox" :aria-expanded="open.toString()"
        aria-label="{{ __('Filial') }}: {{ $label }}" title="{{ __('Filial') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
        </svg>
        <span>{{ $label }}</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <div class="lang-menu branch-menu card" x-show="open" x-cloak role="listbox" aria-label="{{ __('Filial') }}">
        @if ($canSeeAll)
            <button type="submit" name="branch_id" value="all" role="option" aria-selected="{{ $current ? 'false' : 'true' }}" @class(['on' => ! $current])>
                {{ __('Barcha filiallar') }}
                <svg class="check-mark" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </button>
        @endif
        @foreach ($branches as $branch)
            <button type="submit" name="branch_id" value="{{ $branch->id }}" role="option" aria-selected="{{ $current === $branch->id ? 'true' : 'false' }}" @class(['on' => $current === $branch->id])>
                {{ $branch->name }}
                <svg class="check-mark" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </button>
        @endforeach
    </div>
</form>
