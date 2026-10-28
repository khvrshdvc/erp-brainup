<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\User;
use App\Services\CredentialGenerator;
use App\Support\IssuedCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherController extends Controller
{
    use ResolvesBranches;

    public function index(Request $request): View
    {
        $scopeIds = $this->currentBranchId() ? [$this->currentBranchId()] : $this->availableBranchIds();
        $term = trim((string) $request->query('q'));

        $teachers = User::query()
            ->teachers()
            ->with('branches')
            ->withCount('teachingGroups')
            ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $scopeIds))
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('users.name', 'like', "%{$term}%")
                ->orWhere('users.email', 'like', "%{$term}%")
                ->orWhere('users.phone', 'like', "%{$term}%")))
            ->orderBy('users.name')
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.create', $this->formData());
    }

    public function store(TeacherRequest $request, CredentialGenerator $credentials): RedirectResponse
    {
        $data = $request->validated();

        // Left empty, the login and password are generated here.
        $email = $data['email'] ?? $credentials->login(Role::Teacher, $data['name'])
            ?? throw ValidationException::withMessages(['email' => __('Ismdan login yaratib bo‘lmadi, emailni qo‘lda kiriting.')]);
        $password = $data['password'] ?? $credentials->password(Role::Teacher);

        $teacher = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $password,
            'must_change_password' => true,
            'role' => Role::Teacher,
            'status' => UserStatus::Active,
        ]);

        $teacher->branches()->sync($data['branches']);

        IssuedCredentials::flash($teacher, $password);

        return redirect()->route('admin.teachers.index')->with('status', __('O‘qituvchi qo‘shildi'));
    }

    public function edit(User $teacher): View
    {
        Gate::authorize('manageTeacher', $teacher);

        return view('admin.teachers.edit', [
            'teacher' => $teacher->load('branches'),
            'canEditCredentials' => Gate::allows('manageTeacherCredentials', $teacher),
            ...$this->formData(),
        ]);
    }

    public function update(TeacherRequest $request, User $teacher): RedirectResponse
    {
        $data = $request->validated();

        $teacher->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            ...(isset($data['email']) ? ['email' => $data['email']] : []),
        ]);

        if (! empty($data['password'])) {
            $teacher->password = $data['password'];
            $teacher->must_change_password = true;
        }

        $teacher->save();

        $managed = $this->availableBranchIds();
        $untouched = $teacher->branches()->pluck('branches.id')->reject(fn ($id) => in_array($id, $managed, true));

        $teacher->branches()->sync($untouched->merge($data['branches'])->unique()->values()->all());

        return redirect()->route('admin.teachers.index')->with('status', __('O‘qituvchi yangilandi'));
    }

    public function resetPassword(User $teacher, CredentialGenerator $credentials): RedirectResponse
    {
        Gate::authorize('manageTeacher', $teacher);
        // Same rule as TeacherRequest: a teacher shared with other branches keeps their password.
        Gate::authorize('manageTeacherCredentials', $teacher);

        $password = $credentials->password(Role::Teacher);

        $teacher->update(['password' => $password, 'must_change_password' => true]);

        IssuedCredentials::flash($teacher, $password);

        return back()->with('status', __('Yangi parol yaratildi'));
    }

    public function toggleStatus(User $teacher): RedirectResponse
    {
        Gate::authorize('manageTeacher', $teacher);

        $teacher->update([
            'status' => $teacher->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return back()->with('status', __('Holat o‘zgartirildi'));
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
        ];
    }
}
