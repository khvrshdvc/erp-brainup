<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchStaffRequest;
use App\Http\Requests\Admin\CenterSettingsRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use ResolvesBranches;

    private const CENTER_KEYS = ['center_name', 'center_phone', 'center_address', 'center_hours', 'academic_year'];

    public function edit(Request $request): View
    {
        $center = collect(self::CENTER_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key, '')])
            ->all();

        $center['attendance_edit_days'] = Setting::attendanceEditDays();
        $center['submission_file_retention_days'] = Setting::submissionFileRetentionDays();

        // Staff details are shown for one chosen branch only; "Barcha filiallar" lists nobody.
        $branchId = $this->currentBranchId();
        $scopeIds = $branchId ? [$branchId] : [];

        $branchAdmins = $request->user()->isSuperAdmin() && $branchId
            ? User::query()->where('role', Role::BranchAdmin->value)->whereIn('branch_id', $scopeIds)->with('branch')->orderBy('name')->get()
            : collect();

        $teachers = $branchId
            ? User::query()->teachers()
                ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $scopeIds))
                ->with('branches')
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.settings.edit', [
            'center' => $center,
            'user' => $request->user(),
            'canEditCenter' => $request->user()->isSuperAdmin(),
            'branchSelected' => $branchId !== null,
            'branchAdmins' => $branchAdmins,
            'teachers' => $teachers,
        ]);
    }

    public function updateCenter(CenterSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::put($key, $value ?? '');
        }

        return back()->with('status', __('O‘zgarishlar saqlandi'));
    }

    public function updateStaff(BranchStaffRequest $request, User $staff): RedirectResponse
    {
        $data = $request->validated();

        $staff->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            ...(isset($data['email']) ? ['email' => $data['email']] : []),
        ]);

        if (! empty($data['password'])) {
            $staff->password = $data['password'];
        }

        $staff->save();

        return redirect(route('admin.settings.edit').'#staff-'.$staff->id)->with('status', __('Ma‘lumotlar saqlandi'));
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('status', __('Profil yangilandi'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('status', __('Parol o‘zgartirildi'));
    }
}
