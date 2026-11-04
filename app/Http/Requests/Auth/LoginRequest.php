<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Failed logins one account may take from all addresses together, so a password cannot be
     * guessed by spreading attempts over many IPs. Student passwords are only six digits.
     */
    private const ACCOUNT_MAX_FAILURES = 20;

    private const ACCOUNT_DECAY_SECONDS = 3600;

    /**
     * Failed logins one address may make across all accounts, so one machine cannot try a
     * guess against every (predictable) student email in turn.
     */
    private const IP_MAX_FAILURES = 50;

    private const IP_DECAY_SECONDS = 900;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountThrottleKey(), self::ACCOUNT_DECAY_SECONDS);
            RateLimiter::hit($this->ipThrottleKey(), self::IP_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $limits = [
            $this->throttleKey() => 5,
            $this->accountThrottleKey() => self::ACCOUNT_MAX_FAILURES,
            $this->ipThrottleKey() => self::IP_MAX_FAILURES,
        ];

        $lockedKey = collect($limits)->keys()->first(fn (string $key) => RateLimiter::tooManyAttempts($key, $limits[$key]));

        if ($lockedKey === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($lockedKey);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function accountThrottleKey(): string
    {
        return 'login-account|'.Str::transliterate(Str::lower($this->string('email')));
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }
}
