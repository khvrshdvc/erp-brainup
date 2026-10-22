<x-guest-layout>
    <h1 class="auth-title">{{ __('Confirm Password') }}</h1>
    <p class="auth-sub">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="stack" novalidate>
        @csrf

        <div class="field">
            <label for="password">{{ __('Parol') }}</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                @class(['input', 'is-invalid' => $errors->has('password')])
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            <x-field-error name="password" id="password-error" />
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Confirm') }}</button>
    </form>
</x-guest-layout>
