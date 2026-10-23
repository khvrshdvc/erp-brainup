<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Notifications\Events\NotificationSent;

class ForgetUnreadNotificationCount
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel === 'database' && $event->notifiable instanceof User) {
            User::forgetUnreadNotificationsCount($event->notifiable->id);
        }
    }
}
