<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const CENTER_KEYS = ['center_name', 'center_phone', 'center_address', 'center_hours', 'academic_year'];

    public function edit(Request $request): View
    {
        $center = collect(self::CENTER_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key, '')])
            ->all();

        return view('admin.settings.edit', [
            'center' => $center,
            'user' => $request->user(),
            'canEditCenter' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function updateCenter(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'center_name' => ['required', 'string', 'max:120'],
            'center_phone' => ['nullable', 'string', 'max:30'],
            'center_address' => ['nullable', 'string', 'max:255'],
            'center_hours' => ['nullable', 'string', 'max:120'],
            'academic_year' => ['nullable', 'string', 'max:20'],
        ], [], [
            'center_name' => 'markaz nomi',
            'center_phone' => 'telefon',
            'center_address' => 'manzil',
            'center_hours' => 'ish vaqti',
            'academic_year' => 'o‘quv yili',
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, $value ?? '');
        }

        return back()->with('status', 'O‘zgarishlar saqlandi');
    }

    public function updateProfile(Request $request): RedirectResponse
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
