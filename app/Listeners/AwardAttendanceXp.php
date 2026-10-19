<?php

namespace App\Listeners;

use App\Events\AttendanceMarked;
use App\Services\GamificationService;

class AwardAttendanceXp
{
    public function __construct(private GamificationService $gamification)
    {
    }

    public function handle(AttendanceMarked $event): void
    {
        $this->gamification->syncAttendance($event->attendance);
    }
}
