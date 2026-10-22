<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\GamificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildXpCommand extends Command
{
    protected $signature = 'gamification:rebuild {--branch=} {--fresh}';

    protected $description = 'Davomat va vazifalar bo‘yicha XP, daraja, seriya va nishonlarni qayta hisoblaydi';

    public function handle(GamificationService $gamification): int
    {
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;

        if ($this->option('fresh')) {
            $userIds = User::students()
                ->when($branchId, fn ($query) => $query->inBranch($branchId))
                ->pluck('id');

            XpTransaction::whereIn('user_id', $userIds)->delete();
            DB::table('badge_user')->whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->update(['xp' => 0, 'level_id' => null, 'streak' => 0]);
        }

        $attendances = 0;
        $submissions = 0;

        Attendance::withoutGlobalScopes()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('lesson_date')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($gamification, &$attendances) {
                foreach ($rows as $attendance) {
                    $gamification->syncAttendance($attendance, cascade: false);
                    $attendances++;
                }
            });

        Submission::withoutGlobalScopes()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($gamification, &$submissions) {
                foreach ($rows as $submission) {
                    $gamification->syncSubmission($submission);
                    $submissions++;
                }
            });

        $this->info("Qayta hisoblandi: {$attendances} ta davomat, {$submissions} ta topshiriq");

        return self::SUCCESS;
    }
}
