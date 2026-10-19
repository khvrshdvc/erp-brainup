<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Branch;
use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Collection;

trait ResolvesBranches
{
    protected function availableBranchIds(): array
    {
        return auth()->user()->availableBranchIds();
    }

    protected function availableBranches(): Collection
    {
        return Branch::whereIn('id', $this->availableBranchIds())->orderBy('name')->get();
    }

    protected function currentBranchId(): ?int
    {
        return app(CurrentBranch::class)->id() ?: null;
    }

    protected function defaultBranchId(): ?int
    {
        $current = $this->currentBranchId();

        if ($current) {
            return $current;
        }

        $ids = $this->availableBranchIds();

        return count($ids) === 1 ? $ids[0] : null;
    }
}
