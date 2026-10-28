<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $message = match (true) {
            $user === null => null,
            $user->status === UserStatus::Inactive => __('Hisobingiz faol emas. Administratorga murojaat qiling.'),
            $user->hasOnlyInactiveBranches() => __('Filialingiz faol emas. Administratorga murojaat qiling.'),
            default => null,
        };

        if ($message) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
