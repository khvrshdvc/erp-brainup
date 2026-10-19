<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load(['branch', 'branches', 'level']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user)],
        ], [], [
            'name' => 'ism',
            'email' => 'email',
            'phone' => 'telefon',
        ]);

        $user->fill($data)->save();

        return back()->with('status', 'Profil yangilandi');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'current_password' => 'joriy parol',
            'password' => 'yangi parol',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Parol o‘zgartirildi');
    }
}
