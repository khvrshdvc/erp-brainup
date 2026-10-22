<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEVELS = [
        // min_xp => [new name, previous name]
        0 => ['Uchqun', 'Boshlovchi'],
        500 => ['Mash’al', 'Izlanuvchi'],
        1500 => ['Kometa', 'Bilimdon'],
        3000 => ['Daho', 'Ustoz'],
    ];

    private const BADGES = [
        // code => [[new title, new icon], [previous title, previous icon]]
        'first_step' => [['Yo‘l boshi', '🧭'], ['Dars boshlandi', '🚀']],
        'streak_7' => [['Olov yurak', '🔥'], ['Olovli hafta', '🔥']],
        'streak_30' => [['Po‘lat iroda', '🛡️'], ['Temir intizom', '⚡']],
        'first_assignment' => [['Ilk qalam', '✒️'], ['Birinchi topshiriq', '✏️']],
        'excellent' => [['Oltin aql', '🧠'], ['A‘lo natija', '🏆']],
        'ten_assignments' => [['Vazifa ovchisi', '🏹'], ['Mehnatkash', '📚']],
    ];

    public function up(): void
    {
        $this->apply(0);
    }

    public function down(): void
    {
        $this->apply(1);
    }

    /**
     * Seeders only create missing rows, so databases seeded earlier get the new names here.
     */
    private function apply(int $version): void
    {
        foreach (self::LEVELS as $minXp => $names) {
            DB::table('levels')->where('min_xp', $minXp)->update(['name' => $names[$version], 'updated_at' => now()]);
        }

        foreach (self::BADGES as $code => $variants) {
            [$title, $icon] = $variants[$version];

            DB::table('badges')->where('code', $code)->update(['title' => $title, 'icon' => $icon, 'updated_at' => now()]);
        }
    }
};
