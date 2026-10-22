<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttendanceStatus;
use App\Enums\WeekDay;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();

        $groups = $student->studyGroups()
            ->with(['course', 'teacher', 'branch'])
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $group = $groups->firstWhere('id', (int) $request->query('group')) ?? $groups->first();

        $counts = collect();

        if ($group) {
            $counts = Attendance::query()
                ->where('student_id', $student->id)
                ->where('study_group_id', $group->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);
        }

        $total = (int) $counts->sum();
        $attended = (int) collect(AttendanceStatus::attendedValues())->sum(fn ($value) => $counts->get($value, 0));

        return view('student.groups.index', [
            'groups' => $groups,
            'group' => $group,
            'counts' => $counts,
            'total' => $total,
            'percent' => $total ? (int) round($attended / $total * 100) : null,
            'days' => WeekDay::labels(),
        ]);
    }
}
