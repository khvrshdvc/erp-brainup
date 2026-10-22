<x-guest-layout>
    <h1 class="auth-title">{{ __('Parolni unutdingizmi?') }}</h1>
    <p class="auth-sub">{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}</p>

    <x-auth-session-status style="margin-bottom: 16px" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="stack" novalidate>
        @csrf

        <div class="field">
            <label for="email">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                @class(['input', 'is-invalid' => $errors->has('email')])
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            <x-field-error name="email" id="email-error" />
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Email Password Reset Link') }}</button>

        <a class="link" href="{{ route('login') }}" style="font-size: 13px; text-align: center">← {{ __('Kirish') }}</a>
    </form>
</x-guest-layout>
