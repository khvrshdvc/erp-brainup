<x-app-layout :crumb="__('Do‘kon') . ' / ' . __('Mahsulotlar')" :title="__('Do‘kon mahsulotlari')" :subtitle="__('O‘quvchilar BrainCoin evaziga oladigan sovg‘alar')">
    <x-slot name="actions">
        <a href="{{ route('admin.shop.orders.index') }}" class="btn">{{ __('Buyurtmalar') }}</a>
        <a href="{{ route('admin.shop-items.create') }}" class="btn btn-primary">{{ __('+ Mahsulot qo‘shish') }}</a>
    </x-slot>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 860px">
                <div class="t-head" style="--cols: 2.8fr 1.1fr 1fr 1.4fr 1.4fr .5fr">
                    <div>{{ __('Mahsulot') }}</div>
                    <div>{{ __('Narx') }}</div>
                    <div>{{ __('Soni') }}</div>
                    <div>{{ __('Buyurtmalar') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($items as $item)
                    <div class="t-row" style="--cols: 2.8fr 1.1fr 1fr 1.4fr 1.4fr .5fr">
                        <div class="t-name">
                            <span class="order-thumb">
                                @if ($item->image_path)
                                    <img src="{{ route('shop.items.image', $item) }}?v={{ $item->updated_at?->timestamp }}" alt="" loading="lazy">
                                @else
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7"/></svg>
                                @endif
                            </span>
                            <div>
                                {{ $item->name }}
                                @if ($item->description)
                                    <div class="card-sub" style="font-weight: 400">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</div>
                                @endif
                            </div>
                        </div>
                        <div><x-coin :amount="$item->price" /></div>
                        <div>{{ $item->hasUnlimitedStock() ? __('Cheklanmagan') : __(':count ta', ['count' => $item->stock]) }}</div>
                        <div>
                            {{ __(':delivered_count topshirilgan', ['delivered_count' => $item->delivered_count]) }}
                            @if ($item->pending_count)
                                <div class="card-sub">{{ __(':pending_count kutilmoqda', ['pending_count' => $item->pending_count]) }}</div>
                            @endif
                        </div>
                        <div>
                            <x-status-switch :action="route('admin.shop-items.status', $item)"
                                :active="$item->is_active"
                                :label="$item->is_active ? __('Sotuvda') : __('Yashirilgan')" />
                        </div>
                        <div class="t-right"><x-edit-button :href="route('admin.shop-items.edit', $item)" /></div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Mahsulot yo‘q. Birinchi sovg‘ani qo‘shing.') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
