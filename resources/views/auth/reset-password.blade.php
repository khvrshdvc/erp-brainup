<x-guest-layout>
    <h1 class="auth-title">{{ __('Reset Password') }}</h1>
    <p class="auth-sub">{{ __('Kamida 8 ta belgi') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="stack" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                @class(['input', 'is-invalid' => $errors->has('email')])
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            <x-field-error name="email" id="email-error" />
        </div>

        <div class="field">
            <label for="password">{{ __('Yangi parol *') }}</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"
                @class(['input', 'is-invalid' => $errors->has('password')])
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            <x-field-error name="password" id="password-error" />
        </div>

        <div class="field">
            <label for="password_confirmation">{{ __('Yangi parolni takrorlang *') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                @class(['input', 'is-invalid' => $errors->has('password_confirmation')])
                @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="password_confirmation-error" @endif>
            <x-field-error name="password_confirmation" id="password_confirmation-error" />
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Reset Password') }}</button>
    </form>
</x-guest-layout>
