<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = StudyGroup::query()
            ->with(['teacher', 'branch'])
            ->withCount('students')
            ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        return view('groups.index', compact('groups'));
    }
}
