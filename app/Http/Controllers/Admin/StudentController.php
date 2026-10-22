<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\Attendance;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

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
        Gate::authorize('manageStudent', $student);

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
        Gate::authorize('manageStudent', $student);

        return view('admin.students.edit', ['student' => $student, ...$this->formData()]);
    }

    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        $data = $request->validated();

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
        Gate::authorize('manageStudent', $student);

        $student->update([
            'status' => $student->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return back()->with('status', 'Holat o‘zgartirildi');
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
            'groups' => StudyGroup::orderBy('name')->get(['id', 'name', 'branch_id']),
        ];
    }
}
