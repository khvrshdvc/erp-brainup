<?php

namespace App\Http\Controllers\Student;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();

        $groupIds = $student->studyGroups()->pluck('study_groups.id');

        $assignments = Assignment::query()
            ->visibleToStudents()
            ->whereIn('study_group_id', $groupIds)
            ->with(['teacher', 'studyGroup'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->get();

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        $stateOf = function (Assignment $assignment) use ($submissions): string {
            $status = $submissions->get($assignment->id)?->status;

            return match ($status) {
                SubmissionStatus::Submitted => 'review',
                SubmissionStatus::Graded => 'done',
                default => 'todo',
            };
        };

        $tab = in_array($request->query('tab'), ['todo', 'review', 'done'], true) ? $request->query('tab') : 'all';

        $counts = [
            'all' => $assignments->count(),
            'todo' => $assignments->filter(fn ($a) => $stateOf($a) === 'todo')->count(),
            'review' => $assignments->filter(fn ($a) => $stateOf($a) === 'review')->count(),
            'done' => $assignments->filter(fn ($a) => $stateOf($a) === 'done')->count(),
        ];

        return view('student.assignments.index', [
            'assignments' => $tab === 'all' ? $assignments : $assignments->filter(fn ($a) => $stateOf($a) === $tab),
            'submissions' => $submissions,
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, Assignment $assignment): View
    {
        $student = $request->user();

        Gate::authorize('view', $assignment);

        $assignment->load(['teacher', 'studyGroup', 'attachments']);

        $submission = Submission::with('attachments')
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        return view('student.assignments.show', compact('assignment', 'submission'));
    }

    public function create(Request $request, Assignment $assignment): View
    {
        Gate::authorize('submit', $assignment);

        $submission = Submission::with('attachments')
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        $assignment->load(['teacher', 'studyGroup']);

        return view('student.assignments.submit', compact('assignment', 'submission'));
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment, AttachmentService $attachments): RedirectResponse
    {
        $student = $request->user();
        $existing = $request->existingSubmission();

        $submission = Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'branch_id' => $assignment->branch_id,
                'body' => $request->validated('body'),
                'status' => SubmissionStatus::Submitted,
                'is_late' => $existing?->is_late ?? $assignment->isOverdue(),
                'submitted_at' => now(),
                'score' => null,
            ]
        );

        if ($file = $request->file('file')) {
            $attachments->replace($submission, $file, $student, 'submissions/'.$assignment->id);
        }

        event(new SubmissionUpdated($submission));

        return redirect()
            ->route('student.assignments.show', $assignment)
            ->with('status', 'Vazifa yuborildi');
    }
}
