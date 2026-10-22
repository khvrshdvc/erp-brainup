<?php

namespace App\Listeners;

use App\Events\AttendanceMarked;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class AwardAttendanceXp implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private GamificationService $gamification) {}

    public function handle(AttendanceMarked $event): void
    {
        $this->gamification->syncAttendance($event->attendance);
    }
}
