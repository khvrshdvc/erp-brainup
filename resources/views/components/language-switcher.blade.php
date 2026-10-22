@php
    $locales = config('locales.available');
    $current = app()->getLocale();
@endphp

<div {{ $attributes->class(['lang-switch']) }} x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" class="iconbtn lang-btn" @click="open = ! open" aria-haspopup="true" :aria-expanded="open.toString()"
        title="{{ __('Tilni tanlang') }}" aria-label="{{ __('Tilni tanlang') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/>
        </svg>
        <span>{{ $locales[$current]['short'] ?? strtoupper($current) }}</span>
    </button>

    <form method="POST" action="{{ route('locale.switch') }}" class="lang-menu card" x-show="open" x-cloak>
        @csrf
        @foreach ($locales as $code => $locale)
            <button type="submit" name="locale" value="{{ $code }}" lang="{{ $code }}" @class(['on' => $current === $code])
                @if ($current === $code) aria-current="true" @endif>
                <b>{{ $locale['short'] }}</b> {{ $locale['name'] }}
            </button>
        @endforeach
    </form>
</div>
