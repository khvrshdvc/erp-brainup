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
            ['name' => 'Uchqun', 'min_xp' => 0],
            ['name' => 'Mash’al', 'min_xp' => 500],
            ['name' => 'Kometa', 'min_xp' => 1500],
            ['name' => 'Daho', 'min_xp' => 3000],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(['min_xp' => $level['min_xp']], $level);
        }

        $rules = [
            ['code' => 'attendance_present', 'title' => 'Darsga kelish', 'points' => 10],
            ['code' => 'attendance_late', 'title' => 'Kechikib kelish', 'points' => 5],
            ['code' => 'streak_7', 'title' => '7 darslik seriya', 'points' => 30],
            ['code' => 'assignment_on_time', 'title' => 'Vazifani o‘z vaqtida topshirish', 'points' => 20],
            ['code' => 'assignment_high_score', 'title' => 'Vazifadan 90–100 ball', 'points' => 50],
        ];

        foreach ($rules as $rule) {
            XpRule::firstOrCreate(['code' => $rule['code']], $rule);
        }

        $badges = [
            ['code' => 'first_step', 'title' => 'Yo‘l boshi', 'icon' => '🧭', 'description' => 'Birinchi darsga qatnashdingiz'],
            ['code' => 'streak_7', 'title' => 'Olov yurak', 'icon' => '🔥', 'description' => 'Ketma-ket 7 ta darsga qatnashdingiz'],
            ['code' => 'streak_30', 'title' => 'Po‘lat iroda', 'icon' => '🛡️', 'description' => 'Ketma-ket 30 ta darsga qatnashdingiz'],
            ['code' => 'first_assignment', 'title' => 'Ilk qalam', 'icon' => '✒️', 'description' => 'Birinchi vazifangizni topshirdingiz'],
            ['code' => 'excellent', 'title' => 'Oltin aql', 'icon' => '🧠', 'description' => 'Vazifadan kamida 90% ball oldingiz'],
            ['code' => 'ten_assignments', 'title' => 'Vazifa ovchisi', 'icon' => '🏹', 'description' => '10 ta vazifa topshirdingiz'],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
