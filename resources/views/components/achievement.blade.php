@props(['badge', 'earned' => true, 'awardedAt' => null, 'showStatus' => true])

<div {{ $attributes->class(['achv', $badge->tier(), 'locked' => ! $earned]) }}>
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
