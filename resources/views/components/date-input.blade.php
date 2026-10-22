@props([
    'name',
    'id' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    'required' => false,
    'placeholder' => __('Sanani tanlang'),
    'autoSubmit' => false,
    'invalid' => null,
])

@php
    $id ??= $name;
    // Re-shown input after a failed validation may not be a date at all (a hand-made request): show it empty.
    $value = $value ? rescue(fn () => \Illuminate\Support\Carbon::parse($value)->format('Y-m-d'), null, false) : null;
    $invalid ??= $errors->has($name);
@endphp

<div class="datepicker"
    x-data="datePicker({ id: @js($id), value: @js($value), min: @js($min), max: @js($max), autoSubmit: @js((bool) $autoSubmit) })"
    @click.outside="open = false" @keydown.escape.stop="open = false"
    @set-date.window="if ($event.detail.id === id) select($event.detail.value)">

    <input type="hidden" name="{{ $name }}" value="{{ $value }}" :value="value">

    <button type="button" id="{{ $id }}" {{ $attributes->class(['input', 'datepicker-trigger', 'is-invalid' => $invalid]) }}
        @click="toggle()" :aria-expanded="open.toString()" aria-haspopup="dialog">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
        </svg>
        <span :class="{ placeholder: ! value }" x-text="label || @js($placeholder)">
            {{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y') : $placeholder }}
        </span>
    </button>

    <div class="datepicker-pop" x-show="open" x-cloak x-transition.opacity.duration.120ms role="dialog" aria-label="{{ __('Kalendar') }}">
        <div class="datepicker-head">
            <button type="button" class="datepicker-nav" @click="shift(-1)" aria-label="{{ __('Oldingi oy') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div class="datepicker-title">
                <span x-text="monthName"></span>
                <select class="datepicker-year" x-model.number="viewYear" aria-label="{{ __('Yil') }}">
                    <template x-for="year in years" :key="year">
                        <option :value="year" x-text="year" :selected="year === viewYear"></option>
                    </template>
                </select>
            </div>
            <button type="button" class="datepicker-nav" @click="shift(1)" aria-label="{{ __('Keyingi oy') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>

        <div class="datepicker-grid">
            <template x-for="day in weekdays" :key="day"><span class="datepicker-wd" x-text="day"></span></template>
            <template x-for="cell in cells" :key="cell.iso">
                <button type="button" class="datepicker-day" @click="select(cell.iso)" :disabled="cell.disabled"
                    :class="{ out: ! cell.inMonth, today: cell.today, on: cell.iso === value }"
                    :aria-label="cell.aria" x-text="cell.day"></button>
            </template>
        </div>

        <div class="datepicker-foot">
            <button type="button" class="link" @click="select(todayIso)" :disabled="isDisabled(todayIso)">{{ __('Bugun') }}</button>
            @unless ($required)
                <button type="button" class="link" @click="select('')" style="color: var(--muted)">{{ __('Tozalash') }}</button>
            @endunless
        </div>
    </div>
</div>
