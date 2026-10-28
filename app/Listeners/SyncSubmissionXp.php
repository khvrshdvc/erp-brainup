<?php

namespace App\Listeners;

use App\Events\SubmissionUpdated;
use App\Services\GamificationService;

class SyncSubmissionXp
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(SubmissionUpdated $event): void
    {
        $this->gamification->syncSubmission($event->submission);
    }
}
