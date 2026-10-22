@props(['badge', 'earned' => true, 'awardedAt' => null, 'showStatus' => true])

@php
    // Badges earned in the last week get a "New!" tag and a pulsing medal.
    $fresh = $showStatus && $earned && $awardedAt && \Illuminate\Support\Carbon::parse($awardedAt)->gt(now()->subDays(7));
@endphp

<div {{ $attributes->class(['achv', $badge->tier(), 'earned' => $earned, 'locked' => ! $earned, 'fresh' => $fresh]) }}>
    @if ($fresh)
        <span class="achv-new">{{ __('Yangi!') }}</span>
    @endif
    <span class="achv-tier">{{ $badge->tierLabel() }}</span>
    <div class="ico" aria-hidden="true">{{ $badge->icon }}</div>
    <b>{{ __($badge->title) }}</b>
    @if ($badge->description)
        <span class="achv-desc">{{ __($badge->description) }}</span>
    @endif
    @if ($showStatus)
        <small class="achv-status">
            @if ($earned)
                {{ __('✓ Olingan') }}{{ $awardedAt ? ' · '.\Illuminate\Support\Carbon::parse($awardedAt)->format('d.m.Y') : '' }}
            @else
                {{ __('🔒 Qulflangan') }}
            @endif
        </small>
    @endif
</div>
