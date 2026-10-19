<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\XpReason;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\Level;
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

        $this->record($student, $attendance, XpReason::Attendance, $points);

        $streakAtLesson = $this->streakUpTo($student->id, $attendance->lesson_date);

        $bonus = $attendance->isAttended() && $streakAtLesson > 0 && $streakAtLesson % 7 === 0
            ? XpRule::pointsFor('streak_7')
            : 0;

        $this->record($student, $attendance, XpReason::StreakBonus, $bonus);

        $this->refresh($student);
    }

    private function record(User $student, Attendance $attendance, XpReason $reason, int $points): void
    {
        XpTransaction::updateOrCreate(
            [
                'user_id' => $student->id,
                'reason' => $reason->value,
                'source_id' => $attendance->id,
            ],
            [
                'branch_id' => $attendance->branch_id,
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

        $sync = Badge::whereIn('code', $codes)->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['awarded_at' => now()]])
            ->all();

        $student->badges()->syncWithoutDetaching($sync);
    }
}
