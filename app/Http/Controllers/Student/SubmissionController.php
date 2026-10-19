<?php

namespace App\Http\Controllers\Student;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $submissions = Submission::query()
            ->where('student_id', $request->user()->id)
            ->with(['assignment', 'attachments'])
            ->latest('submitted_at')
            ->get();

        $graded = $submissions->where('status', SubmissionStatus::Graded);

        $scored = $graded->filter(fn ($s) => $s->score !== null && $s->assignment && $s->assignment->max_score > 0);

        $average = $scored->isEmpty()
            ? null
            : (int) round($scored->avg(fn ($s) => $s->score / $s->assignment->max_score * 100));

        return view('student.submissions.index', [
            'submissions' => $submissions,
            'total' => $submissions->count(),
            'gradedCount' => $graded->count(),
            'pendingCount' => $submissions->where('status', SubmissionStatus::Submitted)->count(),
            'average' => $average,
        ]);
    }
}
