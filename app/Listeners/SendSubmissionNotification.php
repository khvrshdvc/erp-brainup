<?php

namespace App\Listeners;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionUpdated;
use App\Notifications\SubmissionReceived;
use App\Notifications\SubmissionReviewed;

class SendSubmissionNotification
{
    public function handle(SubmissionUpdated $event): void
    {
        $submission = $event->submission->loadMissing(['assignment', 'student']);

        $assignment = $submission->assignment;

        if (! $assignment) {
            return;
        }

        if ($submission->status === SubmissionStatus::Submitted) {
            $assignment->teacher?->notify(new SubmissionReceived($submission));

            return;
        }

        $submission->student?->notify(new SubmissionReviewed($submission));
    }
}
