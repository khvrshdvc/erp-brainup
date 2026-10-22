<button type="button" {{ $attributes->class(['iconbtn', 'theme-toggle']) }} data-theme-toggle onclick="ErpTheme.toggle()"
    data-to-dark="{{ __('Tungi rejimga o‘tish') }}" data-to-light="{{ __('Kunduzgi rejimga o‘tish') }}"
    title="{{ __('Tungi rejimga o‘tish') }}" aria-label="{{ __('Tungi rejimga o‘tish') }}" aria-pressed="false">
    <svg class="moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>
    </svg>
    <svg class="sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
    </svg>
</button>
