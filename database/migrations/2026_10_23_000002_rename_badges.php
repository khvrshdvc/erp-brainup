<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW = [
        'first_step' => ['Dars boshlandi', '🚀', 'Birinchi darsga qatnashdingiz'],
        'streak_7' => ['Olovli hafta', '🔥', 'Ketma-ket 7 ta darsga qatnashdingiz'],
        'streak_30' => ['Temir intizom', '⚡', 'Ketma-ket 30 ta darsga qatnashdingiz'],
        'first_assignment' => ['Birinchi topshiriq', '✏️', 'Birinchi vazifangizni topshirdingiz'],
        'excellent' => ['A‘lo natija', '🏆', 'Vazifadan kamida 90% ball oldingiz'],
        'ten_assignments' => ['Mehnatkash', '📚', '10 ta vazifa topshirdingiz'],
    ];

    private const OLD = [
        'first_step' => ['Birinchi qadam', '★', null],
        'streak_7' => ['7 dars seriya', '🔥', null],
        'streak_30' => ['30 dars seriya', '⚡', null],
        'first_assignment' => ['Birinchi vazifa', '📝', null],
        'excellent' => ['A‘lochi', '🎯', null],
        'ten_assignments' => ['10 ta vazifa', '📚', null],
    ];

    public function up(): void
    {
        $this->apply(self::NEW);
    }

    public function down(): void
    {
        $this->apply(self::OLD);
    }

    /**
     * Seeders only create missing badges, so already seeded databases get the new texts here.
     */
    private function apply(array $badges): void
    {
        foreach ($badges as $code => [$title, $icon, $description]) {
            DB::table('badges')->where('code', $code)->update([
                'title' => $title,
                'icon' => $icon,
                'description' => $description,
                'updated_at' => now(),
            ]);
        }
    }
};
