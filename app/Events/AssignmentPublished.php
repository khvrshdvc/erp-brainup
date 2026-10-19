<?php

namespace App\Events;

use App\Models\Assignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Assignment $assignment)
    {
    }
}
