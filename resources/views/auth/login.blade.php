<x-guest-layout>
    <h1 class="auth-title">{{ __('Tizimga kirish') }}</h1>
    <p class="auth-sub">{{ __('Email va parolingizni kiriting') }}</p>

    <x-auth-session-status style="margin-bottom: 16px" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="stack" novalidate>
        @csrf

        <div class="field">
            <label for="email">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                required autofocus autocomplete="username"
                @class(['input', 'is-invalid' => $errors->has('email')])
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            <x-field-error name="email" id="email-error" />
        </div>

        <div class="field">
            <label for="password">{{ __('Parol') }}</label>
            <input id="password" type="password" name="password"
                required autocomplete="current-password"
                @class(['input', 'is-invalid' => $errors->has('password')])
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            <x-field-error name="password" id="password-error" />
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Kirish') }}</button>
    </form>
</x-guest-layout>
