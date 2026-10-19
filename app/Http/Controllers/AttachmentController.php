<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $owner = $attachment->attachable;

        abort_if($owner === null, 404);

        $user = $request->user();

        $allowed = match (true) {
            $user->hasRole(Role::SuperAdmin, Role::BranchAdmin) => true,
            $owner instanceof Assignment => $owner->teacher_id === $user->id
                || (
                    $user->isStudent()
                    && ! $owner->isDraft()
                    && $user->studyGroups()->where('study_groups.id', $owner->study_group_id)->exists()
                ),
            $owner instanceof Submission => $owner->student_id === $user->id
                || $owner->assignment?->teacher_id === $user->id,
            default => false,
        };

        abort_unless($allowed, 403);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
