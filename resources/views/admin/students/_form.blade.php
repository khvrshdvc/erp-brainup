@php $editing = isset($student) && $student; @endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}"
    @unless ($editing)
        x-data="credentialFields({ url: @js(route('admin.credentials.generate')), role: 'student', needName: @js(__('Login yaratish uchun avval ism yoki telefonni kiriting.')) })"
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
                    value="{{ old('name', $student->name ?? '') }}">
            </div>

            <div class="field">
                <label for="email">{{ $editing ? __('Email *') : __('Email (login)') }}</label>
                <div style="display: flex; gap: 8px">
                    <input id="email" class="input" style="flex: 1; min-width: 0" type="email" name="email" maxlength="255" autocomplete="off"
                        value="{{ old('email', $student->email ?? '') }}" {{ $editing ? 'required' : '' }}>
                    @unless ($editing)
                        <button type="button" class="btn" @click="fill('email')">{{ __('Yaratish') }}</button>
                    @endunless
                </div>
                @unless ($editing)
                    <div class="field-hint" style="color: var(--err)" x-show="error" x-text="error" x-cloak></div>
                    <div class="field-hint">{{ __('Bo‘sh qoldirsangiz, avtomatik yaratiladi') }}</div>
                @endunless
            </div>

            <div class="field">
                <label for="phone">{{ __('Telefon') }}</label>
                <input id="phone" class="input" type="text" name="phone" maxlength="20" placeholder="+998 90 123 45 67"
                    value="{{ old('phone', $student->phone ?? '') }}">
            </div>

            <div class="field">
                <label for="birth_date">{{ __('Tug‘ilgan sana') }}</label>
                <x-date-input name="birth_date" :max="today()->subDay()->format('Y-m-d')"
                    :value="old('birth_date', optional($student->birth_date ?? null)->format('Y-m-d'))" />
                <x-field-error name="birth_date" />
            </div>

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
        </div>
    </section>

    @unless ($editing)
        <section class="card card-pad stack">
            <h2 class="card-title">{{ __('Filial va guruh') }}</h2>

            <div class="form-grid">
                @if ($branches->count() > 1)
                    <div class="field">
                        <label for="branch_id">{{ __('Filial *') }}</label>
                        <select id="branch_id" name="branch_id" class="select" required>
                            <option value="">{{ __('Filialni tanlang') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $defaultBranchId) === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                @endif

                <div class="field">
                    <label for="group_id">{{ __('Guruh') }}</label>
                    <select id="group_id" name="group_id" class="select">
                        <option value="">{{ __('Keyinroq biriktiriladi') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <div class="field-hint">{{ __('Guruh tanlangan filialniki bo‘lishi kerak') }}</div>
                </div>
            </div>
        </section>
    @endunless

    <div class="form-actions">
        <a href="{{ $editing ? route('admin.students.show', $student) : route('admin.students.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
    </div>
</form>
