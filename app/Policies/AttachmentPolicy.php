<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
{
    public function download(User $user, Attachment $attachment): Response
    {
        $owner = $attachment->attachable;

        if ($owner === null) {
            return Response::denyAsNotFound();
        }

        $allowed = match (true) {
            $user->hasRole(Role::SuperAdmin, Role::BranchAdmin) => true,
            $owner instanceof Assignment => $owner->isTaughtBy($user)
                || ($user->isStudent() && $user->can('view', $owner)),
            $owner instanceof Submission => $owner->student_id === $user->id
                || $owner->assignment?->isTaughtBy($user),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny();
    }
}
