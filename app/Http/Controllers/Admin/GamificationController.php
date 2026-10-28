<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GamificationRulesRequest;
use App\Models\Badge;
use App\Models\Level;
use App\Models\XpRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(): View
    {
        return view('admin.gamification', [
            'rules' => XpRule::current()->orderBy('id')->get(),
            'levels' => Level::orderBy('min_xp')->get(),
            'badges' => Badge::orderBy('id')->get(),
        ]);
    }

    public function update(GamificationRulesRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (XpRule::current()->get() as $rule) {
            $input = $data['rules'][$rule->id] ?? null;

            if ($input === null) {
                continue;
            }

            $rule->update([
                'points' => $input['points'],
                'is_active' => (bool) ($input['is_active'] ?? false),
            ]);
        }

        return back()->with('status', __('Qoidalar saqlandi'));
    }
}
