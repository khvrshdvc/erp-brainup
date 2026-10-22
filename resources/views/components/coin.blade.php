@props(['amount', 'size' => 'md'])

<span {{ $attributes->class(['coin', 'coin-'.$size]) }}>
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="#F59E0B"/>
        <circle cx="12" cy="12" r="7.2" fill="none" stroke="#FCD34D" stroke-width="1.4"/>
        <path d="M10 8h2.6a1.9 1.9 0 010 3.8H10zm0 3.8h3a2 2 0 010 4H10zM10 7v10" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <b>{{ number_format((int) $amount, 0, '', ' ') }}</b>
</span>
