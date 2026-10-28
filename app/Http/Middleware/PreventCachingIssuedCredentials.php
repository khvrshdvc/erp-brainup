<?php

namespace App\Http\Middleware;

use App\Support\IssuedCredentials;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page showing a freshly issued password must not come back from the browser cache (Back button).
 */
class PreventCachingIssuedCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession() && $request->session()->has(IssuedCredentials::SESSION_KEY)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
