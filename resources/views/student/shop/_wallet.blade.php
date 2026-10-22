<section class="card wallet">
    <div class="wallet-main">
        <span class="wallet-label">Hamyonimdagi BrainCoin</span>
        <x-coin :amount="$balance" size="xl" />
    </div>
    <div class="wallet-stats">
        <div><b>{{ number_format($earned, 0, '', ' ') }}</b><span>Jami topilgan</span></div>
        <div><b>{{ $pendingCount }}</b><span>Kutilayotgan buyurtma</span></div>
    </div>
</section>

<nav class="tabs" aria-label="Do‘kon bo‘limlari">
    <a href="{{ route('student.shop.index') }}" @class(['tab', 'on' => request()->routeIs('student.shop.index')])>Mahsulotlar</a>
    <a href="{{ route('student.shop.orders') }}" @class(['tab', 'on' => request()->routeIs('student.shop.orders')])>
        Buyurtmalarim{{ $pendingCount ? ' ('.$pendingCount.')' : '' }}
    </a>
</nav>
