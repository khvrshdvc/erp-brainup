<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\StudyGroup;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionListController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $term = trim((string) $request->query('q'));

        $base = Submission::query()
            ->when($user->isTeacher(), fn ($query) => $query->whereHas(
                'assignment',
                fn ($assignment) => $assignment->where('teacher_id', $user->id)
            ));

        $byStatus = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'total' => (int) $byStatus->sum(),
            'pending' => (int) $byStatus->get(SubmissionStatus::Submitted->value, 0),
            'graded' => (int) $byStatus->get(SubmissionStatus::Graded->value, 0),
            'returned' => (int) $byStatus->get(SubmissionStatus::Returned->value, 0),
        ];

        $submissions = (clone $base)
            ->with(['student', 'assignment.studyGroup'])
            ->when($term !== '', fn ($query) => $query->whereHas(
                'student',
                fn ($student) => $student->where('name', 'like', "%{$term}%")
            ))
            ->when($request->filled('group'), fn ($query) => $query->whereHas(
                'assignment',
                fn ($assignment) => $assignment->where('study_group_id', (int) $request->query('group'))
            ))
            ->when(
                in_array($request->query('status'), SubmissionStatus::values(), true),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        $groups = StudyGroup::query()
            ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('submissions.index', compact('submissions', 'groups', 'counts'));
    }
}
