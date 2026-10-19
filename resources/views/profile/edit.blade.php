<x-app-layout crumb="Bosh sahifa / Profil" title="Profil" subtitle="Shaxsiy ma‘lumotlar va parol">
    <section class="card card-pad profile-head">
        <span class="avatar avatar-lg">{{ $user->initials }}</span>

        <div class="stack" style="gap: 6px">
            <div style="font-size: 20px; font-weight: 700">{{ $user->name }}</div>
            <div class="page-actions">
                <x-badge type="info">{{ $user->role->label() }}</x-badge>
                <span class="card-sub">
                    @if ($user->isTeacher())
                        {{ $user->branches->pluck('name')->implode(', ') }}
                    @else
                        {{ $user->branch?->name }}
                    @endif
                </span>
            </div>

            @if ($user->isStudent())
                <div class="card-sub">{{ $user->level?->name ?? 'Boshlovchi' }} · {{ $user->xp }} XP · seriya: {{ $user->streak }}</div>
            @endif
        </div>
    </section>

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
</x-app-layout>
