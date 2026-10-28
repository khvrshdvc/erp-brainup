<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

/**
 * A login and password an admin has just issued, shown once on the next page (<x-issued-credentials>).
 *
 * The password is never stored in plain text: the database keeps the hash, and the one-time
 * session flash is encrypted, so neither the session store nor Telescope sees it.
 */
class IssuedCredentials
{
    public const SESSION_KEY = 'issued_credentials';

    public static function flash(User $user, string $password): void
    {
        $user->loadMissing(['branch', 'branches']);

        $branch = $user->isTeacher()
            ? $user->branches->pluck('name')->implode(', ')
            : $user->branch?->name;

        session()->flash(self::SESSION_KEY, Crypt::encrypt([
            'name' => $user->name,
            'branch' => $branch,
            'site' => rtrim((string) config('app.url'), '/'),
            'login' => $user->email,
            'password' => $password,
        ]));
    }

    /**
     * @return array{name: string, branch: ?string, site: string, login: string, password: string}|null
     */
    public static function current(): ?array
    {
        $payload = session(self::SESSION_KEY);

        return is_string($payload) ? Crypt::decrypt($payload) : null;
    }
}
