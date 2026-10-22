@php
    $editing = isset($item);
@endphp

<form method="POST" enctype="multipart/form-data" class="stack-lg"
    action="{{ $editing ? route('admin.shop-items.update', $item) : route('admin.shop-items.store') }}"
    x-data="oneSubmit" @submit="guard($event)" :aria-busy="submitting">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <div class="field">
            <label for="name">{{ __('Mahsulot nomi *') }}</label>
            <input id="name" type="text" name="name" maxlength="120" required autofocus
                @class(['input', 'is-invalid' => $errors->has('name')])
                placeholder="{{ __('Masalan: BrainUp daftari') }}" value="{{ old('name', $item->name ?? '') }}">
            <x-field-error name="name" />
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="price">@coins(__('Narx (BrainCoin) *'))</label>
                <input id="price" type="number" name="price" min="1" required inputmode="numeric"
                    @class(['input', 'is-invalid' => $errors->has('price')])
                    value="{{ old('price', $item->price ?? '') }}">
                <x-field-error name="price" />
            </div>

            <div class="field">
                <label for="stock">{{ __('Soni') }}</label>
                <input id="stock" type="number" name="stock" min="0" inputmode="numeric"
                    @class(['input', 'is-invalid' => $errors->has('stock')])
                    placeholder="{{ __('Cheklanmagan') }}" value="{{ old('stock', $item->stock ?? '') }}">
                <div class="field-hint">{{ __('Bo‘sh qoldirilsa, cheklanmagan bo‘ladi') }}</div>
                <x-field-error name="stock" />
            </div>
        </div>

        <div class="field">
            <label for="description">{{ __('Tavsif') }}</label>
            <textarea id="description" name="description" maxlength="500" style="min-height: 90px"
                @class(['textarea', 'is-invalid' => $errors->has('description')])
                placeholder="{{ __('Qisqacha: o‘lcham, rang yoki shartlar') }}">{{ old('description', $item->description ?? '') }}</textarea>
            <x-field-error name="description" />
        </div>

        <div class="field">
            <label for="image">{{ __('Rasm') }}</label>
            @if ($editing && $item->image_path)
                <div class="order-row" style="padding: 0 0 8px; border: 0">
                    <span class="order-thumb" style="width: 64px; height: 64px">
                        <img src="{{ route('shop.items.image', $item) }}?v={{ $item->updated_at?->timestamp }}" alt="">
                    </span>
                    <span class="card-sub">{{ __('Yangi rasm tanlasangiz, joriy rasm almashtiriladi') }}</span>
                </div>
            @endif
            <x-file-picker name="image" id="image" :extensions="['jpg', 'jpeg', 'png', 'webp']" :max-mb="2" />
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(session()->hasOldInput() ? old('is_active') : ($item->is_active ?? true))>
            {{ __('Sotuvda (o‘quvchilarga ko‘rinadi)') }}
        </label>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.shop-items.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ $editing ? __('Saqlash') : __('Qo‘shish') }}</button>
    </div>
</form>
