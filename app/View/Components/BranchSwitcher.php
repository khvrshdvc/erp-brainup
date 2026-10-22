<?php

namespace App\View\Components;

use App\Models\Branch;
use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class BranchSwitcher extends Component
{
    public Collection $branches;

    public int $current;

    public bool $canSeeAll;

    public function __construct(CurrentBranch $currentBranch)
    {
        $user = auth()->user();

        $this->branches = Branch::whereIn('id', $user->availableBranchIds())->orderBy('name')->get();
        $this->current = (int) $currentBranch->id();
        $this->canSeeAll = $user->isSuperAdmin();
    }

    public function shouldRender(): bool
    {
        return $this->canSeeAll || $this->branches->count() > 1;
    }

    public function render(): View
    {
        return view('components.branch-switcher');
    }
}
