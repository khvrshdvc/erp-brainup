<?php

namespace App\Http\Controllers\Student;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = $request->user()->id;

        $submissions = Submission::query()
            ->where('student_id', $studentId)
            ->with(['assignment', 'attachments'])
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        // The summary cards cover every submission, not just the current page.
        $counts = Submission::query()
            ->where('student_id', $studentId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $average = Submission::query()
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->where('submissions.student_id', $studentId)
            ->where('submissions.status', SubmissionStatus::Graded->value)
            ->whereNotNull('submissions.score')
            ->whereNull('assignments.deleted_at')
            ->where('assignments.max_score', '>', 0)
            ->avg(DB::raw('submissions.score * 100.0 / assignments.max_score'));

        return view('student.submissions.index', [
            'submissions' => $submissions,
            'total' => (int) $counts->sum(),
            'gradedCount' => (int) $counts->get(SubmissionStatus::Graded->value, 0),
            'pendingCount' => (int) $counts->get(SubmissionStatus::Submitted->value, 0),
            'average' => $average === null ? null : (int) round($average),
        ]);
    }
}
