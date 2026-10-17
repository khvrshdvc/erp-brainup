<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\CurrentBranch;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CurrentBranch $current): View
    {
        $branchId = $current->id();
        $branch = $branchId ? Branch::find($branchId) : null;

        $students = User::students()->when($branch, fn ($query) => $query->inBranch($branch->id))->count();
        $teachers = $branch ? $branch->teachers()->count() : User::teachers()->count();
        $groups = StudyGroup::count();

        return view('admin.dashboard', [
            'branch' => $branch,
            'students' => $students,
            'teachers' => $teachers,
            'groups' => $groups,
        ]);
    }
}
