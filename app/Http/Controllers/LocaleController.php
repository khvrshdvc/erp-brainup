<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('locales.available')))],
        ]);

        Cookie::queue(config('locales.cookie'), $data['locale'], 60 * 24 * 365);

        $request->user()?->forceFill(['locale' => $data['locale']])->saveQuietly();

        return back();
    }
}
