<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Do‘kon')" :title="__('Do‘kon')"
    :subtitle="__('Darsga kelib, vazifalarni bajarib BrainCoin to‘plang va sovg‘alarga almashtiring')">

    @include('student.shop._wallet')

    @error('item')
        <div class="alert alert-err">{{ $message }}</div>
    @enderror

    @if ($items->isEmpty())
        <section class="card"><div class="t-empty">{{ __('Hozircha do‘konda mahsulot yo‘q') }}</div></section>
    @else
        <div class="shop-grid">
            @foreach ($items as $item)
                @php
                    $missing = $item->price - $balance;
                    $lockedUntil = $locks->get($item->id);
                @endphp
                <article class="card shop-card">
                    <div class="shop-media">
                        @if ($item->image_path)
                            <img src="{{ route('shop.items.image', $item) }}?v={{ $item->updated_at?->timestamp }}" alt="{{ $item->name }}" loading="lazy">
                        @else
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/>
                            </svg>
                        @endif

                        @if (! $item->hasUnlimitedStock())
                            <span @class(['shop-stock', 'out' => ! $item->inStock()])>
                                {{ $item->inStock() ? __(':count ta qoldi', ['count' => $item->stock]) : __('Tugagan') }}
                            </span>
                        @endif
                    </div>

                    <div class="shop-body">
                        <h3>{{ $item->name }}</h3>
                        @if ($item->description)
                            <p>{{ $item->description }}</p>
                        @endif
                    </div>

                    <div class="shop-foot" x-data="{ ask: false }">
                        <x-coin :amount="$item->price" size="lg" />

                        @if (! $item->inStock())
                            <button type="button" class="btn btn-sm" disabled>{{ __('Tugagan') }}</button>
                        @elseif ($lockedUntil)
                            <span class="shop-missing">{{ __('Yana :date dan keyin olish mumkin', ['date' => \App\Support\UzDate::dayMonth($lockedUntil)]) }}</span>
                        @elseif ($missing > 0)
                            <span class="shop-missing">{{ __('Yana :missing kerak', ['missing' => number_format($missing, 0, '', ' ')]) }}</span>
                        @else
                            <button type="button" class="btn btn-primary btn-sm" x-show="! ask" @click="ask = true">{{ __('Sotib olish') }}</button>

                            <form method="POST" action="{{ route('student.shop.buy', $item) }}" class="shop-confirm"
                                x-show="ask" x-cloak x-data="oneSubmit" @submit="guard($event)" :aria-busy="submitting">
                                @csrf
                                <button type="button" class="btn btn-sm" @click="ask = false">{{ __('Yo‘q') }}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Tasdiqlash') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-app-layout>
