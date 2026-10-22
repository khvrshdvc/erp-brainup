<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Events\AttendanceMarked;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        $groups = StudyGroup::query()
            ->when(
                $user->isTeacher(),
                fn ($query) => $query->where('teacher_id', $user->id)
            )
            ->when(
                $user->isBranchAdmin(),
                fn ($query) => $query->where('branch_id', $user->branch_id)
            )
            ->orderBy('name');

        $group = $groups->first();

        if (! $group) {
            return redirect()
                ->route('groups.index')
                ->with('status', __('Sizga tegishli guruh topilmadi.'));
        }

        return redirect()->route('attendance.create', $group);
    }

    public function create(
        Request $request,
        StudyGroup $studyGroup
    ): View {
        $this->authorizeGroup($request, $studyGroup);

        $request->validate([
            'date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ]);

        $date = ($request->date('date') ?? today())->toDateString();

        $user = $request->user();

        $groups = StudyGroup::query()
            ->when(
                $user->isTeacher(),
                fn ($query) => $query->where('teacher_id', $user->id)
            )
            ->when(
                $user->isBranchAdmin(),
                fn ($query) => $query->where('branch_id', $user->branch_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $students = $studyGroup->students()
            ->orderBy('users.name')
            ->get();

        $existing = Attendance::query()
            ->where('study_group_id', $studyGroup->id)
            ->where('lesson_date', $date)
            ->pluck('status', 'student_id')
            ->map(
                fn ($status) => $status instanceof AttendanceStatus
                    ? $status->value
                    : $status
            )
            ->all();

        return view('attendance.create', compact(
            'studyGroup',
            'groups',
            'students',
            'existing',
            'date'
        ));
    }

    public function store(
        StoreAttendanceRequest $request,
        StudyGroup $studyGroup
    ): RedirectResponse {
        $this->authorizeGroup($request, $studyGroup);

        $data = $request->validated();

        $date = $data['lesson_date'];

        $studentIds = $studyGroup->students()
            ->pluck('users.id')
            ->all();

        DB::transaction(function () use (
            $data,
            $date,
            $studyGroup,
            $request,
            $studentIds
        ) {
            foreach ($data['records'] as $studentId => $status) {
                $studentId = (int) $studentId;

                abort_unless(
                    in_array($studentId, $studentIds, true),
                    422,
                    __('Ushbu o‘quvchi tanlangan guruhga tegishli emas.')
                );

                $attendance = Attendance::updateOrCreate(
                    [
                        'study_group_id' => $studyGroup->id,
                        'student_id' => $studentId,
                        'lesson_date' => $date,
                    ],
                    [
                        'branch_id' => $studyGroup->branch_id,
                        'status' => AttendanceStatus::from($status),
                        'marked_by' => $request->user()->id,
                    ]
                );

                DB::afterCommit(
                    fn () => event(new AttendanceMarked($attendance))
                );
            }
        });

        return redirect()
            ->route('attendance.create', [
                $studyGroup,
                'date' => $date,
            ])
            ->with('status', __('Davomat saqlandi.'));
    }

    private function authorizeGroup(
        Request $request,
        StudyGroup $group
    ): void {
        $user = $request->user();

        $isAuthorized = $user->isSuperAdmin()
            || (
                $user->isBranchAdmin()
                && $user->branch_id === $group->branch_id
            )
            || (
                $user->isTeacher()
                && $group->teacher_id === $user->id
            );

        abort_unless($isAuthorized, 403);
    }
}
