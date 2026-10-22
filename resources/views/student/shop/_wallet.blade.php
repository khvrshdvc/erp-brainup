<section class="card wallet">
    <div class="wallet-main">
        <span class="wallet-label">{{ __('Hamyonimdagi') }} <span class="brand-name">{{ __('BrainCoin') }}</span></span>
        <x-coin :amount="$balance" size="xl" count-up />
    </div>
    <div class="wallet-stats">
        <div><b>{{ number_format($earned, 0, '', ' ') }}</b><span>{{ __('Jami topilgan') }}</span></div>
        <div><b>{{ $pendingCount }}</b><span>{{ __('Kutilayotgan buyurtma') }}</span></div>
    </div>
</section>

<nav class="tabs" aria-label="{{ __('Do‘kon bo‘limlari') }}">
    <a href="{{ route('student.shop.index') }}" @class(['tab', 'on' => request()->routeIs('student.shop.index')])>{{ __('Mahsulotlar') }}</a>
    <a href="{{ route('student.shop.orders') }}" @class(['tab', 'on' => request()->routeIs('student.shop.orders')])>
        {{ __('Buyurtmalarim') }}{{ $pendingCount ? ' ('.$pendingCount.')' : '' }}
    </a>
</nav>
