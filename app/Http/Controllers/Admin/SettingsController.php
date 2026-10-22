<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CenterSettingsRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const CENTER_KEYS = ['center_name', 'center_phone', 'center_address', 'center_hours', 'academic_year'];

    public function edit(Request $request): View
    {
        $center = collect(self::CENTER_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key, '')])
            ->all();

        $center['attendance_edit_days'] = Setting::attendanceEditDays();
        $center['submission_file_retention_days'] = Setting::submissionFileRetentionDays();

        return view('admin.settings.edit', [
            'center' => $center,
            'user' => $request->user(),
            'canEditCenter' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function updateCenter(CenterSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::put($key, $value ?? '');
        }

        return back()->with('status', 'O‘zgarishlar saqlandi');
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('status', 'Profil yangilandi');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('status', 'Parol o‘zgartirildi');
    }
}
