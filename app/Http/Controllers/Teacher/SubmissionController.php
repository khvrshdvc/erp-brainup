<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubmissionController extends Controller
{
    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $assignment = $submission->assignment;

        abort_unless($assignment && $assignment->teacher_id === $request->user()->id, 403);

        $data = $request->validate([
            'action' => ['required', Rule::in(['grade', 'return'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf($request->input('action') === 'return'),
            ],
        ], [], [
            'score' => 'baho',
            'feedback' => 'fikr-mulohaza',
        ]);

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
