<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $groups = $teacher->teachingGroups()->withCount('students')->orderBy('name')->get();

        $students = User::students()
            ->whereHas('studyGroups', fn ($query) => $query->where('study_groups.teacher_id', $teacher->id))
            ->count();

        return view('teacher.dashboard', compact('groups', 'students'));
    }
}
