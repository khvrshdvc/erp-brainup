<?php

namespace App\Services;

class CurrentBranch
{
    private array $resolved = [];

    public function id(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (array_key_exists($user->id, $this->resolved)) {
            return $this->resolved[$user->id];
        }

        $available = $user->availableBranchIds();
        $selected = (int) session('branch_id');

        if ($user->isSuperAdmin()) {
            // 0 is an explicit "all branches" choice; with nothing chosen yet, default to the first branch.
            $result = match (true) {
                session()->has('branch_id') && $selected === 0 => null,
                in_array($selected, $available, true) => $selected,
                default => $available ? min($available) : null,
            };
        } elseif (in_array($selected, $available, true)) {
            $result = $selected;
        } else {
            $result = $available[0] ?? 0;
        }

        return $this->resolved[$user->id] = $result;
    }

    public function forget(): void
    {
        $this->resolved = [];
    }
}
