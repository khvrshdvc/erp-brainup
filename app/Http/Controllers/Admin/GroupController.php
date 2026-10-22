<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssignmentStatus;
use App\Enums\GroupStatus;
use App\Enums\WeekDay;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddGroupStudentRequest;
use App\Http\Requests\Admin\GroupRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupController extends Controller
{
    use ResolvesBranches;

    public function index(Request $request): View
    {
        $groups = StudyGroup::query()
            ->with(['teacher', 'course', 'branch'])
            ->withCount('students')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->query('q').'%'))
            ->when(
                in_array($request->query('status'), GroupStatus::values(), true),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->orderBy('name')
            ->get();

        return view('admin.groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('admin.groups.create', $this->formData());
    }

    public function store(GroupRequest $request): RedirectResponse
    {
        $group = StudyGroup::create(['branch_id' => $request->validated('branch_id'), ...$request->payload()]);

        return redirect()->route('admin.groups.show', $group)->with('status', __('Guruh yaratildi'));
    }

    public function show(StudyGroup $studyGroup): View
    {
        $studyGroup->load(['teacher', 'course', 'branch']);

        $students = $studyGroup->students()->with('level')->orderBy('users.name')->get();

        return view('admin.groups.show', [
            'group' => $studyGroup,
            'students' => $students,
            'days' => WeekDay::shortLabels(),
        ]);
    }

    /**
     * Search for students that can still be added to the group: active, same branch, not enrolled yet.
     */
    public function candidates(Request $request, StudyGroup $studyGroup): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        $candidates = User::students()
            ->active()
            ->inBranch($studyGroup->branch_id)
            ->whereNotIn('users.id', $studyGroup->students()->select('users.id'))
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('users.name', 'like', "%{$term}%")
                ->orWhere('users.email', 'like', "%{$term}%")
                ->orWhere('users.phone', 'like', "%{$term}%")))
            ->orderBy('users.name')
            ->limit(20)
            ->get(['users.id', 'users.name', 'users.email']);

        return response()->json($candidates);
    }

    public function edit(StudyGroup $studyGroup): View
    {
        return view('admin.groups.edit', ['group' => $studyGroup, ...$this->formData()]);
    }

    public function update(GroupRequest $request, StudyGroup $studyGroup): RedirectResponse
    {
        DB::transaction(function () use ($request, $studyGroup) {
            $studyGroup->update($request->payload());

            // Open work follows the group's teacher; closed assignments keep their original author.
            if ($studyGroup->wasChanged('teacher_id')) {
                Assignment::withoutGlobalScopes()
                    ->where('study_group_id', $studyGroup->id)
                    ->where('status', '!=', AssignmentStatus::Closed->value)
                    ->update(['teacher_id' => $studyGroup->teacher_id]);
            }
        });

        return redirect()->route('admin.groups.show', $studyGroup)->with('status', __('Guruh yangilandi'));
    }

    public function toggleStatus(StudyGroup $studyGroup): RedirectResponse
    {
        $studyGroup->update([
            'status' => $studyGroup->status === GroupStatus::Active ? GroupStatus::Archived : GroupStatus::Active,
        ]);

        return back()->with('status', __('Holat o‘zgartirildi'));
    }

    public function addStudent(AddGroupStudentRequest $request, StudyGroup $studyGroup): RedirectResponse
    {
        $studentId = $request->validated('student_id');

        // Lock the group row so two admins adding students at once cannot exceed the capacity.
        $added = DB::transaction(function () use ($studyGroup, $studentId) {
            $group = StudyGroup::withoutGlobalScopes()->lockForUpdate()->findOrFail($studyGroup->id);

            if (! $group->hasFreeSeats()) {
                return false;
            }

            $group->students()->syncWithoutDetaching([$studentId]);

            return true;
        });

        if (! $added) {
            return back()->withErrors(['student_id' => __('Guruhda bo‘sh joy qolmagan')]);
        }

        return back()->with('status', __('O‘quvchi guruhga qo‘shildi'));
    }

    public function removeStudent(StudyGroup $studyGroup, User $student): RedirectResponse
    {
        $studyGroup->students()->detach($student->id);

        return back()->with('status', __('O‘quvchi guruhdan chiqarildi'));
    }

    private function formData(): array
    {
        return [
            'branches' => $this->availableBranches(),
            'defaultBranchId' => $this->defaultBranchId(),
            'courses' => Course::active()->orderBy('name')->get(),
            'teachers' => User::teachers()
                ->with('branches')
                ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $this->availableBranchIds()))
                ->orderBy('name')
                ->get(),
            'days' => WeekDay::shortLabels(),
        ];
    }
}
