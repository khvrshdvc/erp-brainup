@php
    $editing = isset($teacher) && $teacher;
    $canEditCredentials = $canEditCredentials ?? true;
    $selected = collect(old('branches', $editing ? $teacher->branches->pluck('id')->all() : [$defaultBranchId]))
        ->filter()->map(fn ($id) => (int) $id)->all();
@endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}"
    @unless ($editing)
        x-data="credentialFields({ url: @js(route('admin.credentials.generate')), role: 'teacher', needName: @js(__('Login yaratish uchun avval ismni kiriting.')) })"
    @endunless>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('Shaxsiy ma‘lumotlar') }}</h2>

        <div class="form-grid">
            <div class="field">
                <label for="name">{{ __('To‘liq ism *') }}</label>
                <input id="name" class="input" type="text" name="name" maxlength="120" required
                    value="{{ old('name', $teacher->name ?? '') }}">
            </div>

            @if ($canEditCredentials)
                <div class="field">
                    <label for="email">{{ $editing ? __('Email *') : __('Email (login)') }}</label>
                    <div style="display: flex; gap: 8px">
                        <input id="email" class="input" style="flex: 1; min-width: 0" type="email" name="email" maxlength="255" autocomplete="off"
                            value="{{ old('email', $teacher->email ?? '') }}" {{ $editing ? 'required' : '' }}>
                        @unless ($editing)
                            <button type="button" class="btn" @click="fill('email')">{{ __('Yaratish') }}</button>
                        @endunless
                    </div>
                    @unless ($editing)
                        <div class="field-hint" style="color: var(--err)" x-show="error" x-text="error" x-cloak></div>
                        <div class="field-hint">{{ __('Bo‘sh qoldirsangiz, avtomatik yaratiladi') }}</div>
                    @endunless
                </div>
            @endif

            <div class="field">
                <label for="phone">{{ __('Telefon') }}</label>
                <input id="phone" class="input" type="text" name="phone" maxlength="20" placeholder="+998 90 123 45 67"
                    value="{{ old('phone', $teacher->phone ?? '') }}">
            </div>

            @if ($canEditCredentials)
                <div class="field">
                    <label for="password">{{ __('Parol') }}</label>
                    <div style="display: flex; gap: 8px">
                        <input id="password" class="input" style="flex: 1; min-width: 0" type="password" name="password" minlength="8" autocomplete="new-password">
                        @unless ($editing)
                            <button type="button" class="btn" @click="fill('password')">{{ __('Yaratish') }}</button>
                        @endunless
                    </div>
                    <div class="field-hint">{{ $editing ? __('O‘zgartirmasangiz, bo‘sh qoldiring') : __('Bo‘sh qoldirsangiz, avtomatik yaratiladi') }}</div>
                </div>
            @endif
        </div>

        @unless ($canEditCredentials)
            <div class="field-hint">{{ __('Bu o‘qituvchi boshqa filiallarda ham ishlaydi, shuning uchun email va parolni faqat super admin o‘zgartira oladi.') }}</div>
        @endunless
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('Filiallar *') }}</h2>
        <div class="field-hint">{{ __('O‘qituvchi bir nechta filialda dars bera oladi') }}</div>

        <div class="check-row">
            @foreach ($branches as $branch)
                <label class="check">
                    <input type="checkbox" name="branches[]" value="{{ $branch->id }}" @checked(in_array($branch->id, $selected, true))>
                    {{ $branch->name }}
                </label>
            @endforeach
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
    </div>
</form>
