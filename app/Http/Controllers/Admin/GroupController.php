<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GroupStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    use ResolvesBranches;

    public const DAYS = ['mon' => 'Du', 'tue' => 'Se', 'wed' => 'Ch', 'thu' => 'Pa', 'fri' => 'Ju', 'sat' => 'Sh', 'sun' => 'Ya'];

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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request), [], $this->attributes());

        $group = StudyGroup::create(['branch_id' => $data['branch_id'], ...$this->payload($data)]);

        return redirect()->route('admin.groups.show', $group)->with('status', 'Guruh yaratildi');
    }

    public function show(StudyGroup $studyGroup): View
    {
        $studyGroup->load(['teacher', 'course', 'branch']);

        $students = $studyGroup->students()->with('level')->orderBy('users.name')->get();

        $candidates = User::students()
            ->inBranch($studyGroup->branch_id)
            ->whereNotIn('users.id', $students->pluck('id'))
            ->orderBy('users.name')
            ->get();

        return view('admin.groups.show', [
            'group' => $studyGroup,
            'students' => $students,
            'candidates' => $candidates,
            'days' => self::DAYS,
        ]);
    }

    public function edit(StudyGroup $studyGroup): View
    {
        return view('admin.groups.edit', ['group' => $studyGroup, ...$this->formData()]);
    }

    public function update(Request $request, StudyGroup $studyGroup): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $studyGroup), [], $this->attributes());

        $studyGroup->update($this->payload($data));

        return redirect()->route('admin.groups.show', $studyGroup)->with('status', 'Guruh yangilandi');
    }

    public function addStudent(Request $request, StudyGroup $studyGroup): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'student')->where('branch_id', $studyGroup->branch_id),
            ],
        ], [], ['student_id' => 'o‘quvchi']);

        if (! $studyGroup->hasFreeSeats()) {
            return back()->withErrors(['student_id' => 'Guruhda bo‘sh joy qolmagan']);
        }

        $studyGroup->students()->syncWithoutDetaching([$data['student_id']]);

        return back()->with('status', 'O‘quvchi guruhga qo‘shildi');
    }

    public function removeStudent(StudyGroup $studyGroup, User $student): RedirectResponse
    {
        $studyGroup->students()->detach($student->id);

        return back()->with('status', 'O‘quvchi guruhdan chiqarildi');
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
            'days' => self::DAYS,
        ];
    }

    private function rules(Request $request, ?StudyGroup $group = null): array
    {
        $branchId = $group?->branch_id ?? (int) $request->input('branch_id');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('study_groups', 'name')->where('branch_id', $branchId)->ignore($group),
            ],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('branch_user', 'user_id')->where('branch_id', $branchId)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::enum(GroupStatus::class)],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => [Rule::in(array_keys(self::DAYS))],
            'schedule_time' => ['nullable', 'date_format:H:i'],
        ];

        if (! $group) {
            $rules['branch_id'] = ['required', 'integer', Rule::in($this->availableBranchIds())];
        }

        return $rules;
    }

    private function attributes(): array
    {
        return [
            'name' => 'guruh nomi',
            'branch_id' => 'filial',
            'course_id' => 'kurs',
            'teacher_id' => 'o‘qituvchi',
            'starts_on' => 'boshlanish sanasi',
            'ends_on' => 'tugash sanasi',
            'capacity' => 'sig‘im',
            'status' => 'holat',
            'schedule_time' => 'dars vaqti',
        ];
    }

    private function payload(array $data): array
    {
        $days = array_values($data['schedule_days'] ?? []);
        $time = $data['schedule_time'] ?? null;

        return [
            'name' => $data['name'],
            'course_id' => $data['course_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'capacity' => $data['capacity'],
            'status' => GroupStatus::from($data['status']),
            'schedule' => $days || $time ? ['days' => $days, 'time' => $time] : null,
        ];
    }
}
