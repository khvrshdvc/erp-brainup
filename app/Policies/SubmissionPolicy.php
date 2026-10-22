<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Only the current teacher of the assignment's group grades its submissions.
     */
    public function grade(User $user, Submission $submission): bool
    {
        return $submission->assignment?->isTaughtBy($user) ?? false;
    }
}
