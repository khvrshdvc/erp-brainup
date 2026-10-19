<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AssignmentStatus;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Models\Assignment;
use App\Models\StudyGroup;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');

        $assignments = Assignment::query()
            ->where('teacher_id', $user->id)
            ->with(['studyGroup' => fn ($query) => $query->withCount('students')])
            ->withCount([
                'submissions',
                'submissions as pending_count' => fn ($query) => $query->where('status', SubmissionStatus::Submitted->value),
            ])
            ->when($request->filled('group'), fn ($query) => $query->where('study_group_id', (int) $request->query('group')))
            ->when(in_array($status, AssignmentStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('teacher.assignments.index', [
            'assignments' => $assignments,
            'groups' => $this->groups($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('teacher.assignments.create', ['groups' => $this->groups($request)]);
    }

    public function store(AssignmentRequest $request, AttachmentService $attachments): RedirectResponse
    {
        $data = $request->validated();
        $group = StudyGroup::findOrFail($data['study_group_id']);
        $publish = $data['action'] === 'publish';

        $assignment = Assignment::create([
            'branch_id' => $group->branch_id,
            'study_group_id' => $group->id,
            'teacher_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'max_score' => $data['max_score'],
            'due_at' => $data['due_at'],
            'status' => $publish ? AssignmentStatus::Published : AssignmentStatus::Draft,
            'published_at' => $publish ? now() : null,
        ]);

        if ($file = $request->file('material')) {
            $attachments->replace($assignment, $file, $request->user(), 'assignments/'.$assignment->id);
        }

        return redirect()
            ->route('teacher.assignments.show', $assignment)
            ->with('status', $publish ? 'Vazifa e‘lon qilindi' : 'Qoralama saqlandi');
    }

    public function show(Request $request, Assignment $assignment): View
    {
        $this->authorizeOwner($request, $assignment);

        $assignment->load(['studyGroup', 'attachments']);

        $students = $assignment->studyGroup->students()->orderBy('users.name')->get();

        $submissions = $assignment->submissions()->with('attachments')->get()->keyBy('student_id');

        $selectedId = $request->integer('student')
            ?: $submissions->sortBy(fn ($submission) => $submission->status === SubmissionStatus::Submitted ? 0 : 1)->first()?->student_id;

        $selected = $selectedId ? $submissions->get($selectedId) : null;

        return view('teacher.assignments.show', [
            'assignment' => $assignment,
            'students' => $students,
            'submissions' => $submissions,
            'selected' => $selected,
            'selectedStudent' => $selected ? $students->firstWhere('id', $selected->student_id) : null,
            'counts' => [
                'total' => $students->count(),
                'submitted' => $submissions->count(),
                'graded' => $submissions->where('status', SubmissionStatus::Graded)->count(),
                'pending' => $submissions->where('status', SubmissionStatus::Submitted)->count(),
                'waiting' => max(0, $students->count() - $submissions->count()),
            ],
        ]);
    }

    public function edit(Request $request, Assignment $assignment): View
    {
        $this->authorizeOwner($request, $assignment);

        return view('teacher.assignments.edit', [
            'assignment' => $assignment->load(['attachments', 'studyGroup']),
            'groups' => $this->groups($request),
        ]);
    }

    public function update(AssignmentRequest $request, Assignment $assignment, AttachmentService $attachments): RedirectResponse
    {
        $this->authorizeOwner($request, $assignment);

        $data = $request->validated();
        $publish = $data['action'] === 'publish' && $assignment->isDraft();

        $assignment->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'max_score' => $data['max_score'],
            'due_at' => $data['due_at'],
            ...($publish ? ['status' => AssignmentStatus::Published, 'published_at' => now()] : []),
        ]);

        if ($file = $request->file('material')) {
            $attachments->replace($assignment, $file, $request->user(), 'assignments/'.$assignment->id);
        }

        return redirect()
            ->route('teacher.assignments.show', $assignment)
            ->with('status', $publish ? 'Vazifa e‘lon qilindi' : 'Vazifa saqlandi');
    }

    public function publish(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeOwner($request, $assignment);

        abort_unless($assignment->isDraft(), 422);

        $assignment->update(['status' => AssignmentStatus::Published, 'published_at' => now()]);

        return back()->with('status', 'Vazifa e‘lon qilindi');
    }

    public function close(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeOwner($request, $assignment);

        abort_unless($assignment->status === AssignmentStatus::Published, 422);

        $assignment->update(['status' => AssignmentStatus::Closed]);

        return back()->with('status', 'Vazifa yopildi, yangi topshiriq qabul qilinmaydi');
    }

    public function reopen(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeOwner($request, $assignment);

        abort_unless($assignment->isClosed(), 422);

        $assignment->update(['status' => AssignmentStatus::Published]);

        return back()->with('status', 'Vazifa qayta ochildi');
    }

    private function groups(Request $request)
    {
        return $request->user()->teachingGroups()->active()->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeOwner(Request $request, Assignment $assignment): void
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);
    }
}
