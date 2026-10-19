<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Notifications\Notification;

class SubmissionReceived extends Notification
{
    public function __construct(private Submission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $assignment = $this->submission->assignment;

        return [
            'title' => 'Yangi topshiriq',
            'body' => $this->submission->student?->name.' «'.$assignment->title.'» vazifasini topshirdi',
            'url' => route('teacher.assignments.show', [$assignment, 'student' => $this->submission->student_id], false),
        ];
    }
}
