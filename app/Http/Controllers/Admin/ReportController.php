<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesBranches;

    public function index(Request $request): View
    {
        [$period, $from, $to] = $this->period($request);

        [$attTotal, $attAttended, $attPct] = $this->attendanceTotals($from, $to);

        $avgGrade = Submission::query()
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->where('submissions.status', SubmissionStatus::Graded->value)
            ->whereNotNull('submissions.score')
            ->where('assignments.max_score', '>', 0)
            ->whereBetween('submissions.graded_at', [$from, $to])
            ->selectRaw('avg(submissions.score * 100.0 / assignments.max_score) as avg_pct')
            ->first()?->avg_pct;

        $submittedInPeriod = Submission::query()->whereBetween('submitted_at', [$from, $to]);
        $submittedTotal = (clone $submittedInPeriod)->count();
        $onTime = (clone $submittedInPeriod)->where('is_late', false)->count();

        return view('admin.reports.index', [
            'period' => $period,
            'kpis' => [
                'attendance' => $attPct,
                'attendanceNote' => __(':count ta yozuv', ['count' => $attTotal]),
                'newStudents' => $this->studentsQuery()->whereBetween('users.created_at', [$from, $to])->count(),
                'grade' => $avgGrade === null ? null : (int) round($avgGrade),
                'onTime' => $submittedTotal ? (int) round($onTime / $submittedTotal * 100) : null,
                'submitted' => $submittedTotal,
            ],
            'months' => $this->newStudentsByMonth(),
            'weeks' => $this->weeklyAttendance(),
            'courses' => $this->courses(),
            'statusRows' => $this->assignmentStatus($from, $to),
            'branches' => $this->branchComparison($from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [, $from, $to] = $this->period($request);

        $rows = Attendance::query()
            ->whereBetween('lesson_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $students = $this->studentsQuery()
            ->with('branch')
            ->whereIn('users.id', $rows->keys())
            ->orderBy('users.name')
            ->get();

        return response()->streamDownload(function () use ($students, $rows) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map('__', ['O‘quvchi', 'Filial', 'Keldi', 'Kechikdi', 'Kelmadi', 'Sababli', 'Jami', 'Davomat %']), ',', '"', '');

            foreach ($students as $student) {
                $counts = $rows->get($student->id)->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);

                $total = $counts->sum();
                $attended = collect(AttendanceStatus::attendedValues())->sum(fn ($value) => $counts->get($value, 0));

                fputcsv($out, [
                    $this->csvSafe($student->name),
                    $this->csvSafe($student->branch?->name),
                    $counts->get('present', 0),
                    $counts->get('late', 0),
                    $counts->get('absent', 0),
                    $counts->get('excused', 0),
                    $total,
                    $total ? round($attended / $total * 100) : 0,
                ], ',', '"', '');
            }

            fclose($out);
        }, 'davomat-hisoboti-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Spreadsheets run cells starting with = + - @ as formulas, so neutralise them with a leading quote.
     */
    private function csvSafe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    private function period(Request $request): array
    {
        $period = in_array($request->query('period'), ['30d', '6m', 'year'], true) ? $request->query('period') : '30d';

        $from = match ($period) {
            '6m' => now()->subMonths(6)->startOfDay(),
            'year' => Carbon::create(now()->month >= 9 ? now()->year : now()->year - 1, 9, 1)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$period, $from, now()->endOfDay()];
    }

    private function studentsQuery()
    {
        $branchId = $this->currentBranchId();

        return User::query()->students()->when(
            $branchId,
            fn ($query) => $query->inBranch($branchId),
            fn ($query) => $query->whereIn('users.branch_id', $this->availableBranchIds())
        );
    }

    private function attendanceTotals(Carbon $from, Carbon $to): array
    {
        $rows = Attendance::query()
            ->whereBetween('lesson_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        $total = (int) $rows->sum('total');
        $attended = (int) $rows->filter(fn ($row) => $row->status->isAttended())->sum('total');

        return [$total, $attended, $total ? (int) round($attended / $total * 100) : null];
    }

    private function newStudentsByMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $created = $this->studentsQuery()->where('users.created_at', '>=', $start)->get(['users.created_at']);

        $counts = collect(range(0, 5))->map(function (int $i) use ($start, $created) {
            $month = $start->copy()->addMonths($i);

            return [
                'label' => trans('dates.months_short')[$month->month - 1],
                'count' => $created->filter(fn ($user) => $user->created_at->isSameMonth($month))->count(),
            ];
        });

        $max = max(1, $counts->max('count'));

        return $counts->map(fn ($item) => $item + ['height' => $item['count'] ? (int) round($item['count'] / $max * 130) : 4])->all();
    }

    private function weeklyAttendance(): array
    {
        $start = now()->startOfWeek()->subWeeks(7);

        $rows = Attendance::query()
            ->where('lesson_date', '>=', $start->toDateString())
            ->selectRaw('lesson_date, status, count(*) as total')
            ->groupBy('lesson_date', 'status')
            ->get();

        return collect(range(0, 7))->map(function (int $i) use ($start, $rows) {
            $weekStart = $start->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

            $subset = $rows->filter(fn ($row) => $row->lesson_date->between($weekStart, $weekEnd));
            $total = (int) $subset->sum('total');
            $attended = (int) $subset->filter(fn ($row) => $row->status->isAttended())->sum('total');
            $pct = $total ? (int) round($attended / $total * 100) : 0;

            return [
                'label' => $weekStart->format('d.m'),
                'pct' => $pct,
                'has' => $total > 0,
                'height' => $total ? (int) round($pct * 1.3) : 4,
            ];
        })->all();
    }

    private function courses(): array
    {
        $groups = StudyGroup::query()->with('course')->withCount('students')->get();

        $rows = $groups
            ->groupBy(fn ($group) => __($group->course?->name ?? 'Kurs tanlanmagan'))
            ->map(fn ($items, $name) => [
                'name' => $name,
                'groups' => $items->count(),
                'students' => (int) $items->sum('students_count'),
            ])
            ->sortByDesc('students')
            ->values();

        $max = max(1, $rows->max('students') ?? 1);

        return $rows->map(fn ($row) => $row + ['pct' => (int) round($row['students'] / $max * 100)])->all();
    }

    private function assignmentStatus(Carbon $from, Carbon $to): array
    {
        $assignments = Assignment::query()
            ->visibleToStudents()
            ->whereBetween('created_at', [$from, $to])
            ->with(['studyGroup' => fn ($query) => $query->withCount('students')])
            ->withCount([
                'submissions as graded_count' => fn ($query) => $query->where('status', SubmissionStatus::Graded->value),
                'submissions as pending_count' => fn ($query) => $query->where('status', SubmissionStatus::Submitted->value),
                'submissions as returned_count' => fn ($query) => $query->where('status', SubmissionStatus::Returned->value),
            ])
            ->get();

        $expected = (int) $assignments->sum(fn ($assignment) => $assignment->studyGroup?->students_count ?? 0);
        $graded = (int) $assignments->sum('graded_count');
        $pending = (int) $assignments->sum('pending_count');
        $returned = (int) $assignments->sum('returned_count');
        $missing = max(0, $expected - $graded - $pending - $returned);

        $pct = fn (int $count) => $expected ? (int) round($count / $expected * 100) : 0;

        return [
            'expected' => $expected,
            'rows' => [
                ['label' => __('Tekshirildi'), 'badge' => 'ok', 'count' => $graded, 'pct' => $pct($graded)],
                ['label' => __('Tekshirilmoqda'), 'badge' => 'warn', 'count' => $pending, 'pct' => $pct($pending)],
                ['label' => __('Qayta topshirish kerak'), 'badge' => 'err', 'count' => $returned, 'pct' => $pct($returned)],
                ['label' => __('Topshirilmagan'), 'badge' => 'neutral', 'count' => $missing, 'pct' => $pct($missing)],
            ],
        ];
    }

    private function branchComparison(Carbon $from, Carbon $to): ?array
    {
        if (! auth()->user()->isSuperAdmin() || $this->currentBranchId()) {
            return null;
        }

        $rows = Attendance::withoutGlobalScopes()
            ->whereBetween('lesson_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('branch_id, status, count(*) as total')
            ->groupBy('branch_id', 'status')
            ->get()
            ->groupBy('branch_id');

        return Branch::query()->withCount('students')->orderBy('name')->get()->map(function (Branch $branch) use ($rows) {
            $subset = $rows->get($branch->id, collect());
            $total = (int) $subset->sum('total');
            $attended = (int) $subset->filter(fn ($row) => $row->status->isAttended())->sum('total');

            return [
                'name' => $branch->name,
                'students' => $branch->students_count,
                'pct' => $total ? (int) round($attended / $total * 100) : null,
            ];
        })->all();
    }
}
