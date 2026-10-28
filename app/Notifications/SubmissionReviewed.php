<?php

namespace App\Notifications;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SubmissionReviewed extends Notification
{
    public function __construct(private Submission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $assignment = $this->submission->assignment;
        $graded = $this->submission->status === SubmissionStatus::Graded;

        $body = $graded
            ? $assignment->title.($this->submission->score !== null ? ': '.$this->submission->score.' / '.$assignment->max_score : '')
            : $assignment->title.($this->submission->feedback ? ': '.Str::limit($this->submission->feedback, 80) : '');

        return [
            'title' => $graded ? 'Vazifa tekshirildi' : 'Qayta topshirish kerak',
            'body' => $body,
            'url' => route('student.assignments.show', $assignment, false),
        ];
    }
}
