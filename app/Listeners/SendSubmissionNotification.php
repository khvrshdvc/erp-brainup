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
            $teacher = $assignment->teacher;

            if ($teacher) {
                // A resubmission replaces the earlier "submitted" notice instead of piling up next to it.
                $notification = new SubmissionReceived($submission);

                $teacher->notifications()
                    ->where('type', SubmissionReceived::class)
                    ->where('data->url', $notification->toArray($teacher)['url'])
                    ->delete();

                $teacher->notify($notification);
            }

            return;
        }

        $submission->student?->notify(new SubmissionReviewed($submission));
    }
}
