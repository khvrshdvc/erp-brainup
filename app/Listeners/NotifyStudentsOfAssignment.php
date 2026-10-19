<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignment;
use Illuminate\Support\Facades\Notification;

class NotifyStudentsOfAssignment
{
    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->loadMissing('studyGroup');

        $students = $assignment->studyGroup?->students()->get() ?? collect();

        Notification::send($students, new NewAssignment($assignment));
    }
}
