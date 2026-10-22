<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Admins manage students of the branches they have access to.
     */
    public function manageStudent(User $admin, User $student): Response
    {
        return $student->isStudent() && in_array($student->branch_id, $admin->availableBranchIds(), true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Admins manage teachers who work in at least one of their branches.
     */
    public function manageTeacher(User $admin, User $teacher): Response
    {
        $shared = $teacher->branches()->whereIn('branches.id', $admin->availableBranchIds())->exists();

        return $teacher->isTeacher() && $shared ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Only the super admin edits a branch admin's details.
     */
    public function manageBranchAdmin(User $actor, User $admin): Response
    {
        return $actor->isSuperAdmin() && $admin->isBranchAdmin() ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Email and password give full control of an account, so a branch admin may only change them
     * when every branch the teacher works in is one of theirs.
     */
    public function manageTeacherCredentials(User $admin, User $teacher): bool
    {
        if ($admin->isSuperAdmin()) {
            return true;
        }

        $outside = $teacher->branches()->whereNotIn('branches.id', $admin->availableBranchIds())->exists();

        return ! $outside;
    }

    /**
     * Students' personal data and passwords are managed by admins only.
     */
    public function updateProfile(User $user, User $profile): bool
    {
        return $user->is($profile) && ! $user->isStudent();
    }
}
