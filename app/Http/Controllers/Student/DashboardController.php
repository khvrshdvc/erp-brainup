<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->load('level');

        $groups = $student->studyGroups()->with('teacher')->orderBy('name')->get();

        return view('student.dashboard', compact('student', 'groups'));
    }
}
