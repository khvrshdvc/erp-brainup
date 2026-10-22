<x-guest-layout>
    <h1 class="auth-title">{{ __('Tizimga kirish') }}</h1>
    <p class="auth-sub">{{ __('Email va parolingizni kiriting') }}</p>

    <x-auth-session-status class="alert alert-ok" style="margin-bottom: 16px" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="stack">
        @csrf

        <div class="field">
            <label for="email">{{ __('Email') }}</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}"
                required autofocus autocomplete="username"
                @if ($errors->has('email')) style="border-color: #B91C1C" @endif>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password">{{ __('Parol') }}</label>
            <input id="password" class="input" type="password" name="password"
                required autocomplete="current-password"
                @if ($errors->has('password')) style="border-color: #B91C1C" @endif>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="auth-row">
            <label class="check">
                <input type="checkbox" name="remember"> {{ __('Eslab qolish') }}
            </label>

            @if (Route::has('password.request'))
                <a class="link" href="{{ route('password.request') }}" style="font-size: 13px">{{ __('Parolni unutdingizmi?') }}</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Kirish') }}</button>
    </form>
</x-guest-layout>
