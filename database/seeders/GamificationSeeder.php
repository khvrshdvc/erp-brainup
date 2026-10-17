<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Level;
use App\Models\XpRule;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Boshlovchi', 'min_xp' => 0],
            ['name' => 'Izlanuvchi', 'min_xp' => 500],
            ['name' => 'Bilimdon', 'min_xp' => 1500],
            ['name' => 'Ustoz', 'min_xp' => 3000],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(['min_xp' => $level['min_xp']], $level);
        }

        $rules = [
            ['code' => 'attendance_present', 'title' => 'Darsga kelish', 'points' => 10],
            ['code' => 'attendance_late', 'title' => 'Kechikib kelish', 'points' => 5],
            ['code' => 'streak_7', 'title' => '7 darslik seriya', 'points' => 30],
        ];

        foreach ($rules as $rule) {
            XpRule::firstOrCreate(['code' => $rule['code']], $rule);
        }

        $badges = [
            ['code' => 'first_step', 'title' => 'Birinchi qadam', 'icon' => '★'],
            ['code' => 'streak_7', 'title' => '7 dars seriya', 'icon' => '🔥'],
            ['code' => 'streak_30', 'title' => '30 dars seriya', 'icon' => '⚡'],
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
