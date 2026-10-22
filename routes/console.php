<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Read notifications are only history after two months; unread ones are never touched.
Schedule::command('notifications:prune --days=60')->daily()->withoutOverlapping();

// Graded work keeps its score and feedback, but the uploaded file is dropped after the retention period.
Schedule::command('submissions:prune-files')->daily()->withoutOverlapping();
