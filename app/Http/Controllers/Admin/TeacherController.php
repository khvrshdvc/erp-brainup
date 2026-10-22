<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function store(TeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $teacher = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => Role::Teacher,
            'status' => UserStatus::Active,
        ]);

        $teacher->branches()->sync($data['branches']);

        return redirect()->route('admin.teachers.index')->with('status', 'O‘qituvchi qo‘shildi');
    }

    public function edit(User $teacher): View
    {
        Gate::authorize('manageTeacher', $teacher);

        return view('admin.teachers.edit', [
            'teacher' => $teacher->load('branches'),
            ...$this->formData(),
        ]);
    }

    public function update(TeacherRequest $request, User $teacher): RedirectResponse
    {
        $data = $request->validated();

        $teacher->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $teacher->password = $data['password'];
        }

        $teacher->save();

        $managed = $this->availableBranchIds();
        $untouched = $teacher->branches()->pluck('branches.id')->reject(fn ($id) => in_array($id, $managed, true));

        $teacher->branches()->sync($untouched->merge($data['branches'])->unique()->values()->all());

        return redirect()->route('admin.teachers.index')->with('status', 'O‘qituvchi yangilandi');
    }

    public function toggleStatus(User $teacher): RedirectResponse
    {
        Gate::authorize('manageTeacher', $teacher);

        $teacher->update([
            'status' => $teacher->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return back()->with('status', 'Holat o‘zgartirildi');
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
        ];
    }
}
