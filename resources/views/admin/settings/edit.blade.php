<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Sozlamalar')" :title="__('Sozlamalar')" :subtitle="__('Markaz ma‘lumotlari va hisob sozlamalari')">
    <form method="POST" action="{{ route('admin.settings.center') }}" class="card card-pad stack">
        @csrf
        @method('PUT')

        <div class="card-head" style="padding: 0; border: 0">
            <h2 class="card-title">{{ __('O‘quv markazi ma‘lumotlari') }}</h2>
            @unless ($canEditCenter)
                <span class="card-sub">{{ __('Faqat super admin o‘zgartira oladi') }}</span>
            @endunless
        </div>

        <fieldset @disabled(! $canEditCenter) style="border: 0; padding: 0; margin: 0" class="stack">
            <div class="form-grid">
                <div class="field">
                    <label for="center_name">{{ __('Markaz nomi *') }}</label>
                    <input id="center_name" class="input" type="text" name="center_name" maxlength="120" required
                        value="{{ old('center_name', $center['center_name']) }}">
                </div>

                <div class="field">
                    <label for="center_phone">{{ __('Telefon') }}</label>
                    <input id="center_phone" class="input" type="text" name="center_phone" maxlength="30"
                        value="{{ old('center_phone', $center['center_phone']) }}">
                </div>

                <div class="field">
                    <label for="center_address">{{ __('Manzil') }}</label>
                    <input id="center_address" class="input" type="text" name="center_address" maxlength="255"
                        value="{{ old('center_address', $center['center_address']) }}">
                </div>

                <div class="field">
                    <label for="center_hours">{{ __('Ish vaqti') }}</label>
                    <input id="center_hours" class="input" type="text" name="center_hours" maxlength="120"
                        placeholder="{{ __('Du–Sh, 09:00–20:00') }}" value="{{ old('center_hours', $center['center_hours']) }}">
                </div>

                <div class="field">
                    <label for="academic_year">{{ __('Joriy o‘quv yili') }}</label>
                    <input id="academic_year" class="input" type="text" name="academic_year" maxlength="20"
                        placeholder="2026–2027" value="{{ old('academic_year', $center['academic_year']) }}">
                </div>

                <div class="field">
                    <label for="attendance_edit_days">{{ __('Davomatni tahrirlash muddati (kun) *') }}</label>
                    <input id="attendance_edit_days" class="input" type="number" name="attendance_edit_days" min="0" max="365" required
                        value="{{ old('attendance_edit_days', $center['attendance_edit_days']) }}">
                    <div class="field-hint">{{ __('O‘qituvchilar faqat shuncha kun ichidagi davomatni belgilay oladi. Adminlar uchun cheklov yo‘q') }}</div>
                </div>

                <div class="field">
                    <label for="submission_file_retention_days">{{ __('Topshiriq fayllarini saqlash muddati (kun) *') }}</label>
                    <input id="submission_file_retention_days" class="input" type="number" name="submission_file_retention_days" min="0" max="3650" required
                        value="{{ old('submission_file_retention_days', $center['submission_file_retention_days']) }}">
                    <div class="field-hint">{{ __('Tekshirilgan topshiriqlarning fayllari shuncha kundan keyin o‘chiriladi. Ball va fikr saqlanadi. 0 — fayllar doimiy saqlanadi') }}</div>
                </div>
            </div>

            @if ($canEditCenter)
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('Saqlash') }}</button>
                </div>
            @endif
        </fieldset>
    </form>

    @if ($branchSelected)
        @if ($user->isSuperAdmin())
            @include('admin.settings._staff', [
                'title' => __('Filial adminlari'),
                'hint' => __('Tanlangan filial adminlarining ma‘lumotlarini o‘zgartirish'),
                'people' => $branchAdmins,
                'empty' => __('Bu filialda admin yo‘q'),
            ])
        @endif

        @include('admin.settings._staff', [
            'title' => __('Filial o‘qituvchilari'),
            'hint' => __('Tanlangan filial o‘qituvchilarining ma‘lumotlarini o‘zgartirish'),
            'people' => $teachers,
            'empty' => __('Bu filialda o‘qituvchi yo‘q'),
        ])
    @else
        <section class="card card-pad stack">
            <h2 class="card-title">{{ __('Filial adminlari va o‘qituvchilari') }}</h2>
            <div class="card-sub">{{ __('Ularning ma‘lumotlarini ko‘rish va o‘zgartirish uchun yuqoridan bitta filialni tanlang (masalan, Chirchiq filiali).') }}</div>
        </section>
    @endif

    <form method="POST" action="{{ route('admin.settings.profile') }}" class="card card-pad stack">
        @csrf
        @method('PUT')

        <h2 class="card-title">{{ __('Administrator profili') }}</h2>

        <div class="form-grid">
            <div class="field">
                <label for="name">{{ __('Ism va familiya *') }}</label>
                <input id="name" class="input" type="text" name="name" maxlength="120" required value="{{ old('name', $user->name) }}">
            </div>

            <div class="field">
                <label for="email">{{ __('Email *') }}</label>
                <input id="email" class="input" type="email" name="email" maxlength="255" required value="{{ old('email', $user->email) }}">
            </div>

            <div class="field">
                <label for="phone">{{ __('Telefon') }}</label>
                <input id="phone" class="input" type="text" name="phone" maxlength="20" value="{{ old('phone', $user->phone) }}">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Saqlash') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.settings.password') }}" class="card card-pad stack">
        @csrf
        @method('PUT')

        <h2 class="card-title">{{ __('Parolni o‘zgartirish') }}</h2>

        <div class="form-grid">
            <div class="field">
                <label for="current_password">{{ __('Joriy parol *') }}</label>
                <input id="current_password" class="input" type="password" name="current_password" required autocomplete="current-password">
            </div>

            <div class="field">
                <label for="new_password">{{ __('Yangi parol *') }}</label>
                <input id="new_password" class="input" type="password" name="password" minlength="8" required autocomplete="new-password">
                <div class="field-hint">{{ __('Parol kamida 8 ta belgidan iborat bo‘lishi kerak') }}</div>
            </div>

            <div class="field">
                <label for="password_confirmation">{{ __('Yangi parolni takrorlang *') }}</label>
                <input id="password_confirmation" class="input" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Parolni yangilash') }}</button>
        </div>
    </form>
</x-app-layout>
