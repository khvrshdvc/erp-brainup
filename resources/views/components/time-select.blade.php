@props([
    'name',
    'id' => null,
    'value' => null,
    'step' => 15,
    'required' => false,
    'placeholder' => null,
])

@php
    $id ??= $name;
    $value = $value ? substr((string) $value, 0, 5) : null;

    // Every time of day in 24-hour format, e.g. 00:00, 00:15 … 23:45.
    $times = [];
    for ($minutes = 0; $minutes < 1440; $minutes += $step) {
        $times[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    // Keep an existing value that isn't on the grid (e.g. 18:07) selectable.
    if ($value && ! in_array($value, $times, true)) {
        $times[] = $value;
        sort($times);
    }
@endphp

<select id="{{ $id }}" name="{{ $name }}" @required($required)
    {{ $attributes->class(['select', 'is-invalid' => $errors->has($name)]) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($times as $time)
        <option value="{{ $time }}" @selected($value === $time)>{{ $time }}</option>
    @endforeach
</select>
