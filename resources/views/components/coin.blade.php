@props(['amount' => null, 'size' => 'md', 'countUp' => false])

{{-- The BrainCoin coin, optionally followed by an amount. $countUp makes the number run up from 0 on load. --}}
<span {{ $attributes->class(['coin', 'coin-'.$size]) }}>
    <x-coin-mark />
    @if ($amount !== null)
        <b{!! $countUp ? ' data-count-up="'.(int) $amount.'"' : '' !!}>{{ number_format((int) $amount, 0, '', ' ') }}</b>
    @endif
</span>
