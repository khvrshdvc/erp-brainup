<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the signed-in user's saved choice, else the "locale" cookie
 * (set from the language button, also for guests), else the application default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookie = $request->cookie(config('locales.cookie'));
        $user = $request->user();

        $locale = self::valid($user?->locale) ?? self::valid($cookie) ?? config('app.locale');

        // A guest's choice follows them into their account the first time they sign in.
        if ($user && $user->locale === null && self::valid($cookie)) {
            $user->forceFill(['locale' => $cookie])->saveQuietly();
        }

        app()->setLocale($locale);

        return $next($request);
    }

    public static function valid(mixed $locale): ?string
    {
        return is_string($locale) && array_key_exists($locale, config('locales.available')) ? $locale : null;
    }
}
