<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeaderboardService
{
    public const SCOPES = ['group', 'branch', 'all'];

    public function for(User $student, string $scope, int $limit = 10): array
    {
        $query = $this->scoped($student, $scope);

        $top = (clone $query)
            ->ranked()
            ->with(['branch:id,name', 'level:id,name'])
            ->limit($limit)
            ->get();

        $rows = $top->values()->map(fn (User $user, int $index) => [
            'rank' => $index + 1,
            'name' => $user->name,
            'initials' => $user->initials,
            'branch' => $user->branch?->name,
            'level' => __($user->level?->name ?? 'Uchqun'),
            'xp' => $user->xp,
            'me' => $user->id === $student->id,
        ])->all();

        $included = collect($rows)->contains('me', true);

        return [
            'rows' => $rows,
            'included' => $included,
            'myRank' => $included ? collect($rows)->firstWhere('me', true)['rank'] : $this->rankOf($student, $query),
        ];
    }

    private function scoped(User $student, string $scope): Builder
    {
        // Deactivated students have left the center, so they drop out of the ranking.
        $query = User::query()->students()->active();

        return match ($scope) {
            'group' => $query->whereHas(
                'studyGroups',
                fn ($q) => $q->whereIn('study_groups.id', $student->studyGroups()->pluck('study_groups.id'))
            ),
            'all' => $query,
            default => $student->branch_id
                ? $query->inBranch($student->branch_id)
                : $query->whereRaw('1 = 0'),
        };
    }

    private function rankOf(User $student, Builder $query): int
    {
        return (clone $query)->where(function (Builder $q) use ($student) {
            $q->where('users.xp', '>', $student->xp)
                ->orWhere(fn (Builder $same) => $same
                    ->where('users.xp', $student->xp)
                    ->where('users.id', '<', $student->id));
        })->count() + 1;
    }
}
