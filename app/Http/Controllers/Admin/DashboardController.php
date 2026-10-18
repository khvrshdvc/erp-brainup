<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\CurrentBranch;
use App\Support\UzDate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CurrentBranch $current): View
    {
        $branchId = $current->id();
        $branch = $branchId ? Branch::find($branchId) : null;

        $students = User::students()->when($branch, fn ($query) => $query->inBranch($branch->id))->count();
        $teachers = $branch ? $branch->teachers()->count() : User::teachers()->count();
        $activeGroups = StudyGroup::active()->count();
        $allGroups = StudyGroup::count();
        $todayMarked = Attendance::whereDate('lesson_date', today())->count();

        $weekStart = now()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(5);

        $rows = Attendance::query()
            ->whereBetween('lesson_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->selectRaw('lesson_date, status, count(*) as total')
            ->groupBy('lesson_date', 'status')
            ->get();

        $perDay = [];

        foreach ($rows as $row) {
            $key = $row->lesson_date->toDateString();
            $perDay[$key]['total'] = ($perDay[$key]['total'] ?? 0) + (int) $row->total;
            $perDay[$key]['attended'] = ($perDay[$key]['attended'] ?? 0) + ($row->status->isAttended() ? (int) $row->total : 0);
        }

        $bars = collect(range(0, 5))->map(function (int $i) use ($weekStart, $perDay) {
            $day = $weekStart->copy()->addDays($i);
            $data = $perDay[$day->toDateString()] ?? ['total' => 0, 'attended' => 0];
            $pct = $data['total'] ? (int) round($data['attended'] / $data['total'] * 100) : 0;

            return [
                'label' => UzDate::shortDay($day),
                'pct' => $pct,
                'height' => (int) round($pct * 1.25),
                'has' => $data['total'] > 0,
            ];
        })->all();

        $weekTotal = (int) $rows->sum('total');

        $summary = collect(AttendanceStatus::cases())->map(function (AttendanceStatus $status) use ($rows, $weekTotal) {
            $count = (int) $rows->filter(fn ($row) => $row->status === $status)->sum('total');

            return [
                'label' => $status->label(),
                'badge' => $status->badge(),
                'pct' => $weekTotal ? (int) round($count / $weekTotal * 100) : 0,
            ];
        })->all();

        $recentGroups = StudyGroup::query()
            ->with('teacher')
            ->withCount('students')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'branch' => $branch,
            'students' => $students,
            'teachers' => $teachers,
            'activeGroups' => $activeGroups,
            'allGroups' => $allGroups,
            'todayMarked' => $todayMarked,
            'weekRange' => UzDate::dayMonth($weekStart).' – '.UzDate::dayMonth($weekEnd),
            'bars' => $bars,
            'summary' => $summary,
            'weekTotal' => $weekTotal,
            'recentGroups' => $recentGroups,
        ]);
    }
}
