<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\GradeSubmissionRequest;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;

class SubmissionController extends Controller
{
    public function update(GradeSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $data = $request->validated();
        $assignment = $submission->assignment;

        $grading = $data['action'] === 'grade';

        $submission->update([
            'status' => $grading ? SubmissionStatus::Graded : SubmissionStatus::Returned,
            'score' => $grading ? ($data['score'] ?? null) : null,
            'feedback' => $data['feedback'] ?? null,
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        event(new SubmissionUpdated($submission));

        return redirect()
            ->route('teacher.assignments.show', [$assignment, 'student' => $submission->student_id])
            ->with('status', $grading ? 'Topshiriq tekshirildi' : 'Topshiriq qayta topshirishga qaytarildi');
    }
}
