<x-app-layout crumb="Do‘kon / Buyurtmalarim" title="Buyurtmalarim"
    subtitle="Buyurtmani filial administratoridan olasiz. Kutilayotgan buyurtmani bekor qilsangiz, BrainCoin qaytariladi.">

    @include('student.shop._wallet')

    <section class="card">
        @forelse ($orders as $order)
            <div class="order-row">
                <span class="order-thumb">
                    @if ($order->item?->image_path)
                        <img src="{{ route('shop.items.image', $order->item) }}" alt="" loading="lazy">
                    @else
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7"/></svg>
                    @endif
                </span>

                <span class="order-body">
                    <b>{{ $order->item?->name ?? 'O‘chirilgan mahsulot' }}</b>
                    <span class="card-sub">
                        {{ \App\Support\UzDate::dayMonth($order->created_at) }}, {{ $order->created_at->format('H:i') }}
                        @if ($order->note)
                            · {{ $order->note }}
                        @endif
                    </span>
                </span>

                <x-coin :amount="$order->price" />
                <x-badge :type="$order->status->badge()">{{ $order->status->label() }}</x-badge>

                @can('cancelOwn', $order)
                    <form method="POST" action="{{ route('student.shop.orders.cancel', $order) }}" x-data="{ ask: false }" class="order-cancel">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-sm" x-show="! ask" @click="ask = true">Bekor qilish</button>
                        <button type="submit" class="btn btn-sm" x-show="ask" x-cloak style="color: var(--err); border-color: var(--err)">Ha, bekor qilish</button>
                    </form>
                @endcan
            </div>
        @empty
            <div class="t-empty">
                Hali buyurtma yo‘q. <a class="link" href="{{ route('student.shop.index') }}">Do‘konga o‘tish</a>
            </div>
        @endforelse
    </section>
</x-app-layout>
