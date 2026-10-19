<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Level;
use App\Models\XpRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(): View
    {
        return view('admin.gamification', [
            'rules' => XpRule::orderBy('id')->get(),
            'levels' => Level::orderBy('min_xp')->get(),
            'badges' => Badge::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:1000'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ]);

        foreach (XpRule::all() as $rule) {
            $input = $data['rules'][$rule->id] ?? null;

            if ($input === null) {
                continue;
            }

            $rule->update([
                'points' => $input['points'],
                'is_active' => (bool) ($input['is_active'] ?? false),
            ]);
        }

        return back()->with('status', 'Qoidalar saqlandi');
    }
}
