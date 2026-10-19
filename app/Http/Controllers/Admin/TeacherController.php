<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), [], $this->attributes());

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
        $this->authorizeTeacher($teacher);

        return view('admin.teachers.edit', [
            'teacher' => $teacher->load('branches'),
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);

        $data = $request->validate($this->rules($teacher), [], $this->attributes());

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
        $this->authorizeTeacher($teacher);

        $teacher->update([
            'status' => $teacher->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return back()->with('status', 'Holat o‘zgartirildi');
    }

    private function authorizeTeacher(User $teacher): void
    {
        $branchIds = $teacher->branches()->pluck('branches.id')->all();

        abort_unless(
            $teacher->isTeacher() && array_intersect($branchIds, $this->availableBranchIds()) !== [],
            404
        );
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
        ];
    }

    private function rules(?User $teacher = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($teacher)],
            'password' => $teacher ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => ['integer', Rule::in($this->availableBranchIds())],
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'ism',
            'email' => 'email',
            'phone' => 'telefon',
            'password' => 'parol',
            'branches' => 'filiallar',
        ];
    }
}
