<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Level;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $board): View
    {
        $student = $request->user()->load(['level', 'badges']);

        $scope = in_array($request->query('scope'), LeaderboardService::SCOPES, true)
            ? $request->query('scope')
            : 'branch';

        $levels = Level::orderBy('min_xp')->get();
        $levelNumber = max(1, $levels->where('min_xp', '<=', $student->xp)->count());
        $next = $levels->first(fn (Level $level) => $level->min_xp > $student->xp);
        $floor = $student->level?->min_xp ?? 0;

        $progress = $next
            ? (int) round(($student->xp - $floor) / max(1, $next->min_xp - $floor) * 100)
            : 100;

        $earned = $student->badges->keyBy('id');

        $badges = Badge::orderBy('id')->get()->map(fn (Badge $badge) => [
            'badge' => $badge,
            'earned' => $earned->has($badge->id),
        ]);

        return view('student.achievements', [
            'student' => $student,
            'levelNumber' => $levelNumber,
            'next' => $next,
            'progress' => $progress,
            'badges' => $badges,
            'scope' => $scope,
            'leaderboard' => $board->for($student, $scope),
        ]);
    }
}
