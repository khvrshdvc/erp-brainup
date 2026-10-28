<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff whose password was issued by an admin must replace it before using anything but the profile page.
 */
class EnsurePasswordIsChanged
{
    private const OPEN_ROUTES = ['profile.edit', 'profile.password', 'password.update', 'logout', 'locale.switch'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Students cannot change their own password (UserPolicy::updateProfile), so they are never held here.
        if ($user && $user->must_change_password && ! $user->isStudent() && ! $request->routeIs(...self::OPEN_ROUTES)) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
