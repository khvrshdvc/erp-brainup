@props(['label', 'value', 'note' => null, 'icon' => 'students'])

@php
    $paths = [
        'students' => 'M2 9l10-5 10 5-10 5-10-5zM6 11.5V16c0 1 3 3 6 3s6-2 6-3v-4.5',
        'teachers' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8',
        'groups' => 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8M21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8',
        'calendar' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
        'xp' => 'M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z',
        'level' => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3',
        'streak' => 'M12 2c1 4 5 5 5 10a5 5 0 01-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 1-9z',
    ];
@endphp

<div class="kpi">
    <div class="kpi-top">
        <span class="kpi-label">{{ $label }}</span>
        <span class="kpi-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="{{ $paths[$icon] ?? $paths['students'] }}"/>
            </svg>
        </span>
    </div>
    <div class="kpi-value">{{ $value }}</div>
    @if ($note)
        <div class="kpi-note">{{ $note }}</div>
    @endif
</div>
