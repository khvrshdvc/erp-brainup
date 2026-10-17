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
            $result = in_array($selected, $available, true) ? $selected : null;
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
