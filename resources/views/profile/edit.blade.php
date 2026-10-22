<x-app-layout crumb="Bosh sahifa / Profil" title="Profil" subtitle="Shaxsiy ma‘lumotlar va parol">
    <section class="card profile-hero">
        <span class="avatar profile-avatar">{{ $user->initials }}</span>

        <div class="profile-who">
            <h2>{{ $user->name }}</h2>
            <div class="profile-tags">
                <x-badge type="info">{{ $user->role->label() }}</x-badge>
                <span class="card-sub">
                    @if ($user->isTeacher())
                        {{ $user->branches->pluck('name')->implode(', ') }}
                    @else
                        {{ $user->branch?->name }}
                    @endif
                </span>
            </div>
        </div>

        @if ($user->isStudent())
            <div class="profile-stats">
                <div><b>{{ $user->level?->name ?? 'Boshlovchi' }}</b><span>Daraja</span></div>
                <div><b>{{ (int) $user->xp }}</b><span>XP</span></div>
                <div><b>{{ (int) $user->streak }}</b><span>Seriya</span></div>
            </div>
        @endif
    </section>

    @if ($user->isStudent())
        @php
            $info = [
                ['Ism va familiya', $user->name, 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8'],
                ['Email', $user->email, 'M4 6h16v12H4zM4 7l8 6 8-6'],
                ['Telefon', $user->phone, 'M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.4 1.8.7 2.7a2 2 0 01-.5 2.1L8 9.8a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.7.7a2 2 0 011.7 2z'],
                ['Tug‘ilgan sana', $user->birth_date ? $user->birth_date->format('d.m.Y').' · '.$user->birth_date->age.' yosh' : null, 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z'],
                ['Filial', $user->branch?->name, 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6'],
                ['Guruhlar', $user->studyGroups->pluck('name')->implode(', '), 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8M21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8'],
                ['Ro‘yxatdan o‘tgan', $user->created_at?->format('d.m.Y'), 'M12 7v5l3 2M12 21a9 9 0 100-18 9 9 0 000 18'],
            ];
        @endphp

        <section class="card">
            <div class="card-head">
                <h2 class="card-title">Shaxsiy ma‘lumotlar</h2>
                <span class="readonly-tag">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 11h14v10H5zM8 11V7a4 4 0 018 0v4"/></svg>
                    Faqat ko‘rish uchun
                </span>
            </div>

            <div class="info-grid">
                @foreach ($info as [$label, $value, $icon])
                    <div class="info-item">
                        <span class="info-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                        </span>
                        <span class="info-text">
                            <small>{{ $label }}</small>
                            <b @class(['muted' => blank($value)])>{{ filled($value) ? $value : 'Kiritilmagan' }}</b>
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="info-note">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16v-4M12 8h.01M12 21a9 9 0 100-18 9 9 0 000 18"/></svg>
                <span>Ma‘lumotlar yoki parolni o‘zgartirish uchun filial administratoriga murojaat qiling.</span>
            </div>
        </section>
    @else
        <form method="POST" action="{{ route('profile.update') }}" class="card card-pad stack">
            @csrf
            @method('PATCH')

            <h2 class="card-title">Shaxsiy ma‘lumotlar</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="name">Ism va familiya *</label>
                    <input id="name" class="input" type="text" name="name" maxlength="120" required value="{{ old('name', $user->name) }}">
                </div>

                <div class="field">
                    <label for="email">Email *</label>
                    <input id="email" class="input" type="email" name="email" maxlength="255" required value="{{ old('email', $user->email) }}">
                </div>

                <div class="field">
                    <label for="phone">Telefon</label>
                    <input id="phone" class="input" type="text" name="phone" maxlength="20" value="{{ old('phone', $user->phone) }}">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="card card-pad stack">
            @csrf
            @method('PUT')

            <h2 class="card-title">Parolni o‘zgartirish</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="current_password">Joriy parol *</label>
                    <input id="current_password" class="input" type="password" name="current_password" required autocomplete="current-password">
                </div>

                <div class="field">
                    <label for="new_password">Yangi parol *</label>
                    <input id="new_password" class="input" type="password" name="password" minlength="8" required autocomplete="new-password">
                    <div class="field-hint">Kamida 8 ta belgi</div>
                </div>

                <div class="field">
                    <label for="password_confirmation">Yangi parolni takrorlang *</label>
                    <input id="password_confirmation" class="input" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Parolni yangilash</button>
            </div>
        </form>
    @endif
</x-app-layout>
