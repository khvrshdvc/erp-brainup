<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Notifications\Notification;

class NewAssignment extends Notification
{
    public function __construct(private Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Yangi vazifa',
            'body' => $this->assignment->title.' · muddat: '.$this->assignment->due_at->format('d.m.Y, H:i'),
            'url' => route('student.assignments.show', $this->assignment, false),
        ];
    }
}
