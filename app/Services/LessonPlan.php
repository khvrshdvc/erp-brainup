<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\GroupStatus;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\StudyGroup;
use App\Support\MonthAttendance;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * How many lessons a student was expected to attend in a month, across all of their groups.
 *
 * A lesson is expected on each scheduled day of an active group the student belongs to (from the
 * day they joined). A scheduled day that nobody in the group was marked for once the attendance
 * edit window has passed is treated as cancelled. Lessons the student was marked for always count,
 * except excused ones, so an excused absence does not lower the share of the monthly coins.
 */
class LessonPlan
{
    /** Groups without schedule days are assumed to meet this often in a full month. */
    public const DEFAULT_MONTHLY_LESSONS = 12;

    public function forMonth(int $studentId, CarbonInterface $month): MonthAttendance
    {
        $start = CarbonImmutable::parse($month)->startOfMonth();
        $end = $start->endOfMonth()->startOfDay();

        $rows = Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->whereBetween('lesson_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('lesson_date')
            ->orderBy('id')
            ->get(['id', 'branch_id', 'study_group_id', 'lesson_date', 'status']);

        $rowsByGroup = $rows->toBase()->groupBy('study_group_id');
        $groups = $this->groupWindows($studentId, $start, $end);
        $marked = $this->markedDates($groups->keys()->all(), $start, $end);
        $open = today()->subDays(Setting::attendanceEditDays())->toDateString();

        $expected = 0;

        foreach ($groups as $groupId => $group) {
            $own = $rowsByGroup->get($groupId, collect());
            $groupMarked = array_filter(
                $marked[$groupId] ?? [],
                fn (string $date) => $date >= $group['from']->toDateString() && $date <= $group['to']->toDateString()
            );

            if ($group['days'] === []) {
                $share = ($group['from']->diffInDays($group['to']) + 1) / $start->daysInMonth;
                $held = array_unique([...$groupMarked, ...$this->dates($own)]);

                $expected += max((int) ceil(self::DEFAULT_MONTHLY_LESSONS * $share), count($held))
                    - $this->excused($own)->count();

                continue;
            }

            $dates = [];

            for ($day = $group['from']; $day->lte($group['to']); $day = $day->addDay()) {
                $date = $day->toDateString();

                if (in_array(strtolower($day->format('D')), $group['days'], true)
                    && (in_array($date, $groupMarked, true) || $date >= $open)) {
                    $dates[$date] = true;
                }
            }

            foreach ($own as $row) {
                $dates[$row->lesson_date->toDateString()] = true;
            }

            foreach ($this->excused($own) as $row) {
                unset($dates[$row->lesson_date->toDateString()]);
            }

            $expected += count($dates);
        }

        // Groups the student has left (or that are no longer active) only count the lessons marked for them.
        foreach ($rowsByGroup->except($groups->keys()->all()) as $own) {
            $expected += $own->count() - $this->excused($own)->count();
        }

        return new MonthAttendance(
            key: (int) $start->format('Ym'),
            expected: max(0, $expected),
            present: $rows->where('status', AttendanceStatus::Present)->count(),
            late: $rows->where('status', AttendanceStatus::Late)->count(),
            missed: $rows->whereIn('status', [AttendanceStatus::Absent, AttendanceStatus::Excused])->count(),
            groups: $groups->keys()->merge($rowsByGroup->keys())->unique()->count(),
            branchId: $rows->last()?->branch_id,
        );
    }

    /**
     * The student's active groups that meet during the month, with the part of the month they count for.
     *
     * @return Collection<int, array{from: CarbonImmutable, to: CarbonImmutable, days: list<string>}>
     */
    private function groupWindows(int $studentId, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return StudyGroup::withoutGlobalScopes()
            ->join('group_student', 'group_student.study_group_id', '=', 'study_groups.id')
            ->where('group_student.student_id', $studentId)
            ->where('study_groups.status', GroupStatus::Active->value)
            ->get(['study_groups.id', 'study_groups.schedule', 'study_groups.starts_on', 'study_groups.ends_on', 'group_student.joined_at'])
            ->mapWithKeys(function (StudyGroup $group) use ($start, $end) {
                $from = collect([$start, CarbonImmutable::parse($group->joined_at)->startOfDay(), $group->starts_on?->toImmutable()])
                    ->filter()->max();
                $to = $group->ends_on && $group->ends_on->lt($end) ? $group->ends_on->toImmutable() : $end;

                return [$group->id => [
                    'from' => $from,
                    'to' => $to,
                    'days' => array_values(array_intersect($group->schedule['days'] ?? [], ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])),
                ]];
            })
            ->filter(fn (array $group) => $group['from']->lte($group['to']));
    }

    /**
     * Days on which anyone in the group was marked, per group.
     *
     * @return array<int, list<string>>
     */
    private function markedDates(array $groupIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($groupIds === []) {
            return [];
        }

        return Attendance::withoutGlobalScopes()
            ->whereIn('study_group_id', $groupIds)
            ->whereBetween('lesson_date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->get(['study_group_id', 'lesson_date'])
            ->groupBy('study_group_id')
            ->map(fn (Collection $rows) => $this->dates($rows))
            ->all();
    }

    private function dates(Collection $rows): array
    {
        return $rows->map(fn (Attendance $row) => $row->lesson_date->toDateString())->unique()->values()->all();
    }

    private function excused(Collection $rows): Collection
    {
        return $rows->where('status', AttendanceStatus::Excused);
    }
}
