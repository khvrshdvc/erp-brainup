<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\SubmissionStatus;
use App\Enums\XpReason;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\Level;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpRule;
use App\Models\XpTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GamificationService
{
    /**
     * $cascade re-syncs the streak bonuses of the student's later lessons too, since an edit
     * here can create or break streaks that end after this date. Bulk rebuilds pass false:
     * they visit every attendance themselves and each bonus is computed from the full history.
     */
    public function syncAttendance(Attendance $attendance, bool $cascade = true): void
    {
        $student = User::findOrFail($attendance->student_id);

        $points = match ($attendance->status) {
            AttendanceStatus::Present => XpRule::pointsFor('attendance_present'),
            AttendanceStatus::Late => XpRule::pointsFor('attendance_late'),
            default => 0,
        };

        $this->record($student->id, $attendance->branch_id, XpReason::Attendance, $attendance->id, $points);

        $this->syncStreakBonuses($student->id, $attendance->lesson_date, $cascade);

        $this->refresh($student);
    }

    public function syncSubmission(Submission $submission): void
    {
        $student = User::findOrFail($submission->student_id);

        $assignment = Assignment::withoutGlobalScopes()->withTrashed()->find($submission->assignment_id);

        $onTime = $submission->is_late ? 0 : XpRule::pointsFor('assignment_on_time');

        $this->record($student->id, $submission->branch_id, XpReason::Assignment, $submission->id, $onTime);

        $highScore = 0;

        if (
            $assignment
            && $submission->status === SubmissionStatus::Graded
            && $submission->score !== null
            && $assignment->max_score > 0
            && $submission->score / $assignment->max_score >= 0.9
        ) {
            $highScore = XpRule::pointsFor('assignment_high_score');
        }

        $this->record($student->id, $submission->branch_id, XpReason::AssignmentScore, $submission->id, $highScore);

        $this->refresh($student);
    }

    private function record(int $studentId, int $branchId, XpReason $reason, int $sourceId, int $points): void
    {
        XpTransaction::updateOrCreate(
            [
                'user_id' => $studentId,
                'reason' => $reason->value,
                'source_id' => $sourceId,
            ],
            [
                'branch_id' => $branchId,
                'points' => $points,
            ]
        );
    }

    /**
     * Streak bonuses for the lessons on $from (and, when cascading, every later day).
     *
     * A lesson day counts once however many lessons the student had that day, so the bonus
     * is stored on the day's first attended attendance and the others get 0.
     */
    private function syncStreakBonuses(int $studentId, CarbonInterface $from, bool $cascade): void
    {
        $days = $this->attendanceDays($studentId);
        $streaks = $this->dayStreaks($days);
        $fromDate = $from->toDateString();
        $points = XpRule::pointsFor('streak_7');

        foreach ($days as $date => $rows) {
            if ($date < $fromDate || ($date > $fromDate && ! $cascade)) {
                continue;
            }

            $bonusRowId = $streaks[$date] > 0 && $streaks[$date] % 7 === 0
                ? $rows->first(fn (Attendance $row) => $row->isAttended())?->id
                : null;

            foreach ($rows as $row) {
                $this->record($studentId, $row->branch_id, XpReason::StreakBonus, $row->id, $row->id === $bonusRowId ? $points : 0);
            }
        }
    }

    /**
     * The student's attendances grouped by lesson date (oldest day first).
     */
    private function attendanceDays(int $studentId): Collection
    {
        return Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->orderBy('lesson_date')
            ->orderBy('id')
            ->get(['id', 'branch_id', 'lesson_date', 'status'])
            ->groupBy(fn (Attendance $row) => $row->lesson_date->toDateString());
    }

    /**
     * Streak length on each lesson day: a day is attended if at least one lesson that day was,
     * and a day with only absences resets the streak.
     *
     * @return array<string, int>
     */
    private function dayStreaks(Collection $days): array
    {
        $streak = 0;
        $streaks = [];

        foreach ($days as $date => $rows) {
            $streak = $rows->contains(fn (Attendance $row) => $row->isAttended()) ? $streak + 1 : 0;
            $streaks[$date] = $streak;
        }

        return $streaks;
    }

    private function refresh(User $student): void
    {
        $xp = (int) XpTransaction::where('user_id', $student->id)->sum('points');

        $level = Level::forXp($xp);

        $streak = (int) (last($this->dayStreaks($this->attendanceDays($student->id))) ?: 0);

        $student->forceFill([
            'xp' => $xp,
            'level_id' => $level?->id,
            'streak' => $streak,
        ])->save();

        $this->awardBadges($student);
    }

    private function awardBadges(User $student): void
    {
        $attended = Attendance::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->attended()
            ->count();

        $submitted = Submission::withoutGlobalScopes()->where('student_id', $student->id)->count();

        $excellent = Submission::withoutGlobalScopes()
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->where('submissions.student_id', $student->id)
            ->where('submissions.status', SubmissionStatus::Graded->value)
            ->whereNotNull('submissions.score')
            ->whereRaw('submissions.score >= assignments.max_score * 0.9')
            ->exists();

        $codes = [];

        if ($attended >= 1) {
            $codes[] = 'first_step';
        }

        if ($student->streak >= 7) {
            $codes[] = 'streak_7';
        }

        if ($student->streak >= 30) {
            $codes[] = 'streak_30';
        }

        if ($submitted >= 1) {
            $codes[] = 'first_assignment';
        }

        if ($submitted >= 10) {
            $codes[] = 'ten_assignments';
        }

        if ($excellent) {
            $codes[] = 'excellent';
        }

        $sync = Badge::whereIn('code', $codes)->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['awarded_at' => now()]])
            ->all();

        $student->badges()->syncWithoutDetaching($sync);
    }
}
