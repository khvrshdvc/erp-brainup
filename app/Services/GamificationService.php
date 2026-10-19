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
use Carbon\Carbon;
use Carbon\CarbonInterface;

class GamificationService
{
    public function syncAttendance(Attendance $attendance): void
    {
        $student = User::findOrFail($attendance->student_id);

        $points = match ($attendance->status) {
            AttendanceStatus::Present => XpRule::pointsFor('attendance_present'),
            AttendanceStatus::Late => XpRule::pointsFor('attendance_late'),
            default => 0,
        };

        $this->record($student->id, $attendance->branch_id, XpReason::Attendance, $attendance->id, $points);

        $streakAtLesson = $this->streakUpTo($student->id, $attendance->lesson_date);

        $bonus = $attendance->isAttended() && $streakAtLesson > 0 && $streakAtLesson % 7 === 0
            ? XpRule::pointsFor('streak_7')
            : 0;

        $this->record($student->id, $attendance->branch_id, XpReason::StreakBonus, $attendance->id, $bonus);

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

    private function streakUpTo(int $studentId, CarbonInterface $date): int
    {
        $rows = Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->whereDate('lesson_date', '<=', $date)
            ->orderByDesc('lesson_date')
            ->limit(60)
            ->get(['status']);

        $streak = 0;

        foreach ($rows as $row) {
            if (! $row->status->isAttended()) {
                break;
            }

            $streak++;
        }

        return $streak;
    }

    private function refresh(User $student): void
    {
        $xp = (int) XpTransaction::where('user_id', $student->id)->sum('points');

        $level = Level::forXp($xp);

        $latest = Attendance::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->orderByDesc('lesson_date')
            ->value('lesson_date');

        $streak = $latest ? $this->streakUpTo($student->id, Carbon::parse($latest)) : 0;

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
