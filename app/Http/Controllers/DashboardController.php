<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isSuperAdmin(), $user->isBranchAdmin() => redirect()->route('admin.dashboard'),
            $user->isTeacher() => redirect()->route('teacher.dashboard'),
            default => redirect()->route('student.dashboard'),
        };
    }
}
