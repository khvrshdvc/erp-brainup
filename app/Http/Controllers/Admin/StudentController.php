<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ResolvesBranches;

    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $term = trim((string) $request->query('q'));

        $students = User::query()
            ->students()
            ->with(['branch', 'level'])
            ->withCount('studyGroups')
            ->when(
                $branchId,
                fn ($query) => $query->inBranch($branchId),
                fn ($query) => $query->whereIn('users.branch_id', $this->availableBranchIds())
            )
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('users.name', 'like', "%{$term}%")
                ->orWhere('users.email', 'like', "%{$term}%")
                ->orWhere('users.phone', 'like', "%{$term}%")))
            ->when(
                $request->filled('group'),
                fn ($query) => $query->whereHas('studyGroups', fn ($group) => $group->where('study_groups.id', (int) $request->query('group')))
            )
            ->when(
                in_array($request->query('status'), UserStatus::values(), true),
                fn ($query) => $query->where('users.status', $request->query('status'))
            )
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'groups' => StudyGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request), [], $this->attributes());

        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => Role::Student,
            'status' => UserStatus::Active,
            'branch_id' => $data['branch_id'],
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        if (! empty($data['group_id'])) {
            $group = StudyGroup::findOrFail($data['group_id']);

            if ($group->hasFreeSeats()) {
                $group->students()->syncWithoutDetaching([$student->id]);
            }
        }

        return redirect()->route('admin.students.show', $student)->with('status', 'O‘quvchi qo‘shildi');
    }

    public function show(User $student): View
    {
        $this->authorizeStudent($student);

        $student->load(['branch', 'level', 'studyGroups.teacher']);

        $attendance = Attendance::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);

        $submissions = $student->submissions()
            ->withoutGlobalScopes()
            ->with('assignment')
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('admin.students.show', compact('student', 'attendance', 'submissions'));
    }

    public function edit(User $student): View
    {
        $this->authorizeStudent($student);

        return view('admin.students.edit', ['student' => $student, ...$this->formData()]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $data = $request->validate($this->rules($request, $student), [], $this->attributes());

        $student->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $student->password = $data['password'];
        }

        $student->save();

        return redirect()->route('admin.students.show', $student)->with('status', 'O‘quvchi yangilandi');
    }

    public function toggleStatus(User $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $student->update([
            'status' => $student->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return back()->with('status', 'Holat o‘zgartirildi');
    }

    private function authorizeStudent(User $student): void
    {
        abort_unless(
            $student->isStudent() && in_array($student->branch_id, $this->availableBranchIds(), true),
            404
        );
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
            'groups' => StudyGroup::orderBy('name')->get(['id', 'name', 'branch_id']),
        ];
    }

    private function rules(Request $request, ?User $student = null): array
    {
        $branchId = $student?->branch_id ?? (int) $request->input('branch_id');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($student)],
            'password' => $student ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];

        if (! $student) {
            $rules['branch_id'] = ['required', 'integer', Rule::in($this->availableBranchIds())];
            $rules['group_id'] = ['nullable', 'integer', Rule::exists('study_groups', 'id')->where('branch_id', $branchId)];
        }

        return $rules;
    }

    private function attributes(): array
    {
        return [
            'name' => 'ism',
            'email' => 'email',
            'phone' => 'telefon',
            'password' => 'parol',
            'birth_date' => 'tug‘ilgan sana',
            'branch_id' => 'filial',
            'group_id' => 'guruh',
        ];
    }
}
