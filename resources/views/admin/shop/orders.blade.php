@php
    $tabs = [
        'pending' => __('Kutilmoqda'),
        'delivered' => __('Topshirilgan'),
        'cancelled' => __('Bekor qilingan'),
        'all' => __('Barchasi'),
    ];
@endphp

<x-app-layout :crumb="__('Do‘kon') . ' / ' . __('Buyurtmalar')" :title="__('Do‘kon buyurtmalari')"
    :subtitle="__('Mahsulotni o‘quvchiga berganingizdan keyin “Topshirildi” deb belgilang')">
    @if (auth()->user()->isSuperAdmin())
        <x-slot name="actions">
            <a href="{{ route('admin.shop-items.index') }}" class="btn btn-primary">{{ __('Mahsulotlar') }}</a>
        </x-slot>
    @endif

    <nav class="tabs" aria-label="{{ __('Buyurtma holati') }}">
        @foreach ($tabs as $key => $label)
            @php $n = $key === 'all' ? $counts->sum() : (int) $counts->get($key, 0); @endphp
            <a href="{{ route('admin.shop.orders.index', ['status' => $key]) }}" @class(['tab', 'on' => $status === $key])>
                {{ $label }}{{ $n ? ' ('.$n.')' : '' }}
            </a>
        @endforeach
    </nav>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 960px">
                <div class="t-head" style="--cols: 2fr 2fr 1fr 1.3fr 1.2fr 2.4fr">
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Mahsulot') }}</div>
                    <div>{{ __('Narx') }}</div>
                    <div>{{ __('Sana') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($orders as $order)
                    <div class="t-row" style="--cols: 2fr 2fr 1fr 1.3fr 1.2fr 2.4fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $order->student?->initials }}</span>
                            <div>
                                {{ $order->student?->name ?? '—' }}
                                <div class="card-sub" style="font-weight: 400">{{ $order->branch?->name }}</div>
                            </div>
                        </div>
                        <div>{{ $order->item?->name ?? __('O‘chirilgan mahsulot') }}</div>
                        <div><x-coin :amount="$order->price" /></div>
                        <div>
                            {{ $order->created_at->format('d.m.Y, H:i') }}
                            @if ($order->handled_at)
                                <div class="card-sub">{{ $order->handler?->name }} · {{ $order->handled_at->format('d.m.Y') }}</div>
                            @endif
                        </div>
                        <div>
                            <x-badge :type="$order->status->badge()">{{ $order->status->label() }}</x-badge>
                            @if ($order->note)
                                <div class="card-sub">{{ $order->note }}</div>
                            @endif
                        </div>
                        <div class="t-right">
                            @can('handle', $order)
                                <div class="order-actions" x-data="{ cancelling: false }">
                                    <form method="POST" action="{{ route('admin.shop.orders.deliver', $order) }}" x-show="! cancelling">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Topshirildi') }}</button>
                                    </form>
                                    <button type="button" class="btn btn-sm" x-show="! cancelling" @click="cancelling = true">{{ __('Bekor qilish') }}</button>

                                    <form method="POST" action="{{ route('admin.shop.orders.cancel', $order) }}" class="order-cancel-form" x-show="cancelling" x-cloak>
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="note" class="input input-sm" maxlength="255" placeholder="{{ __('Sabab (ixtiyoriy)') }}" style="width: 100%">
                                        <button type="button" class="btn btn-sm" @click="cancelling = false">{{ __('Yo‘q') }}</button>
                                        <button type="submit" class="btn btn-sm" style="color: var(--err); border-color: var(--err)">{{ __('Bekor qilish') }}</button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Buyurtma yo‘q') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$orders" />
</x-app-layout>
