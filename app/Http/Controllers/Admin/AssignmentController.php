<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssignmentStatus;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesBranches;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    use ResolvesBranches;

    public function index(Request $request): View
    {
        $scopeIds = $this->currentBranchId() ? [$this->currentBranchId()] : $this->availableBranchIds();

        $assignments = Assignment::query()
            ->with(['studyGroup' => fn ($query) => $query->withCount('students'), 'teacher'])
            ->withCount([
                'submissions',
                'submissions as pending_count' => fn ($query) => $query->where('status', SubmissionStatus::Submitted->value),
            ])
            ->when($request->filled('group'), fn ($query) => $query->where('study_group_id', (int) $request->query('group')))
            ->when($request->filled('teacher'), fn ($query) => $query->where('teacher_id', (int) $request->query('teacher')))
            ->when(
                in_array($request->query('status'), AssignmentStatus::values(), true),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.assignments.index', [
            'assignments' => $assignments,
            'groups' => StudyGroup::orderBy('name')->get(['id', 'name']),
            'teachers' => User::teachers()
                ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $scopeIds))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Assignment $assignment): View
    {
        $assignment->load(['studyGroup', 'teacher', 'attachments']);

        $submissions = $assignment->submissions()->with('student')->orderBy('submitted_at')->get();

        $total = $assignment->studyGroup?->students()->count() ?? 0;

        return view('admin.assignments.show', [
            'assignment' => $assignment,
            'submissions' => $submissions,
            'total' => $total,
            'pending' => $submissions->where('status', SubmissionStatus::Submitted)->count(),
        ]);
    }
}
