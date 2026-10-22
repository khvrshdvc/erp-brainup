<?php

namespace App\Http\Controllers;

use App\Services\CurrentBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __invoke(Request $request, CurrentBranch $current): RedirectResponse
    {
        $user = $request->user();
        $value = $request->input('branch_id');

        if ($value === 'all') {
            abort_unless($user->isSuperAdmin(), 403);
            session(['branch_id' => 0]);
        } else {
            $branchId = (int) $value;
            abort_unless(in_array($branchId, $user->availableBranchIds(), true), 403);
            session(['branch_id' => $branchId]);
        }

        $current->forget();

        return back();
    }
}
