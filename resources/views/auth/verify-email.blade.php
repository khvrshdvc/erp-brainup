<x-guest-layout>
    <p class="auth-sub">{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status style="margin-bottom: 16px" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="stack">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Resend Verification Email') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-block">{{ __('Log Out') }}</button>
        </form>
    </div>
</x-guest-layout>
