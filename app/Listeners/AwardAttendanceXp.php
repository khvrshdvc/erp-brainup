<?php

namespace App\Listeners;

use App\Events\AttendanceMarked;
use App\Services\GamificationService;

/**
 * Runs inline, not on the queue: hosting without a running worker would otherwise
 * never award attendance XP. AttendanceController fires the event after commit.
 */
class AwardAttendanceXp
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(AttendanceMarked $event): void
    {
        $this->gamification->syncAttendance($event->attendance);
    }
}
