<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Only the teacher who owns the assignment grades its submissions.
     */
    public function grade(User $user, Submission $submission): bool
    {
        return $submission->assignment?->teacher_id === $user->id;
    }
}
