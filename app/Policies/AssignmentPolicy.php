<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    /**
     * Teachers may only view, edit, publish or close their own assignments.
     */
    public function manage(User $user, Assignment $assignment): bool
    {
        return $assignment->teacher_id === $user->id;
    }

    /**
     * Students see published assignments of their own groups; anything else is "not found".
     */
    public function view(User $user, Assignment $assignment): Response
    {
        $visible = ! $assignment->isDraft()
            && $user->studyGroups()->where('study_groups.id', $assignment->study_group_id)->exists();

        return $visible ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * A student can (re)submit while the assignment is open and their work is not graded yet.
     */
    public function submit(User $user, Assignment $assignment): Response
    {
        $view = $this->view($user, $assignment);

        if ($view->denied()) {
            return $view;
        }

        $graded = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first()
            ?->isGraded();

        return $assignment->isClosed() || $graded ? Response::deny() : Response::allow();
    }
}
