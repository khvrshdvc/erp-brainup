<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use App\Models\User;
use App\Services\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const LIMIT = 10;

    private const MIN_TERM_LENGTH = 2;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $term = trim((string) $request->query('q'));
        $branchId = app(CurrentBranch::class)->id() ?: null;
        $scopeIds = $branchId ? [$branchId] : $user->availableBranchIds();
        $isAdmin = $user->isSuperAdmin() || $user->isBranchAdmin();

        $students = $teachers = $groups = collect();

        if (mb_strlen($term) >= self::MIN_TERM_LENGTH) {
            // "%" and "_" typed by the user must match themselves, not every row.
            $pattern = '%'.addcslashes($term, '\\%_').'%';

            $like = fn ($query) => $query->where(fn ($where) => $where
                ->where('users.name', 'like', $pattern)
                ->orWhere('users.email', 'like', $pattern)
                ->orWhere('users.phone', 'like', $pattern));

            $groups = StudyGroup::query()
                ->with(['course', 'teacher'])
                ->withCount('students')
                ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $user->id))
                ->where('name', 'like', $pattern)
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get();

            if ($isAdmin) {
                $students = User::students()
                    ->tap($like)
                    ->whereIn('users.branch_id', $scopeIds)
                    ->orderBy('users.name')
                    ->limit(self::LIMIT)
                    ->get();

                $teachers = User::teachers()
                    ->tap($like)
                    ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $scopeIds))
                    ->orderBy('users.name')
                    ->limit(self::LIMIT)
                    ->get();
            }
        }

        return view('search.index', compact('term', 'students', 'teachers', 'groups', 'isAdmin'));
    }
}
