@props(['type' => 'neutral'])

<span {{ $attributes->class(['badge', $type]) }}>
    <span class="dot"></span>{{ $slot }}
</span>
