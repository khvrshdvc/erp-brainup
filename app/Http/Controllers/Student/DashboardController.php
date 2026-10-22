<?php

namespace App\Http\Controllers\Student;

use App\Enums\WeekDay;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->load('level');

        $groups = $student->studyGroups()->with(['teacher', 'course'])->orderBy('name')->get();

        $attendances = $student->attendances()
            ->with('studyGroup')
            ->latest('lesson_date')
            ->limit(10)
            ->get();

        $attended = $attendances->filter(fn ($attendance) => $attendance->status->isAttended())->count();

        return view('student.dashboard', [
            'student' => $student,
            'balance' => $student->coinBalance(),
            'groups' => $groups,
            'attendances' => $attendances,
            'attended' => $attended,
            'rate' => $attendances->isEmpty() ? null : (int) round($attended / $attendances->count() * 100),
            'days' => WeekDay::shortLabels(),
        ]);
    }
}
