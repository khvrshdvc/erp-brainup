<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Badge;
use App\Models\Branch;
use App\Models\Level;
use App\Models\User;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BadgeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationSeeder::class);
    }

    private function student(): User
    {
        $branch = Branch::create(['name' => 'Test filial']);

        return User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);
    }

    public function test_badges_have_new_names_icons_and_descriptions(): void
    {
        $this->assertSame(
            [
                'first_step' => ['Yo‘l boshi', '🧭'],
                'streak_7' => ['Olov yurak', '🔥'],
                'streak_30' => ['Po‘lat iroda', '🛡️'],
                'first_assignment' => ['Ilk qalam', '✒️'],
                'excellent' => ['Oltin aql', '🧠'],
                'ten_assignments' => ['Vazifa ovchisi', '🏹'],
            ],
            Badge::orderBy('id')->get()->mapWithKeys(fn (Badge $badge) => [$badge->code => [$badge->title, $badge->icon]])->all()
        );

        $this->assertSame(0, Badge::whereNull('description')->count());
    }

    public function test_seeder_and_migration_rename_badges_and_levels_of_an_existing_database(): void
    {
        // A database seeded with the previous names.
        (require database_path('migrations/2026_10_24_000002_rename_levels_and_badges.php'))->down();
        $this->assertSame('A‘lo natija', Badge::where('code', 'excellent')->value('title'));
        $this->assertSame('Boshlovchi', Level::where('min_xp', 0)->value('name'));

        (require database_path('migrations/2026_10_24_000002_rename_levels_and_badges.php'))->up();

        $excellent = Badge::where('code', 'excellent')->firstOrFail();
        $this->assertSame(['Oltin aql', '🧠'], [$excellent->title, $excellent->icon]);
        $this->assertSame('Vazifadan kamida 90% ball oldingiz', $excellent->description);
        $this->assertSame(['Uchqun', 'Mash’al', 'Kometa', 'Daho'], Level::orderBy('min_xp')->pluck('name')->all());

        // The seeder keeps them too.
        DB::table('badges')->where('code', 'streak_7')->update(['title' => 'Eski nom']);
        $this->seed(GamificationSeeder::class);
        $this->assertSame('Olov yurak', Badge::where('code', 'streak_7')->value('title'));
        $this->assertSame(6, Badge::count());
        $this->assertSame(4, Level::count());
    }

    public function test_tiers_follow_the_difficulty(): void
    {
        $tiers = Badge::all()->mapWithKeys(fn (Badge $badge) => [$badge->code => $badge->tier()]);

        $this->assertSame('bronze', $tiers['first_step']);
        $this->assertSame('bronze', $tiers['first_assignment']);
        $this->assertSame('silver', $tiers['streak_7']);
        $this->assertSame('silver', $tiers['ten_assignments']);
        $this->assertSame('gold', $tiers['streak_30']);
        $this->assertSame('gold', $tiers['excellent']);
    }

    public function test_student_sees_earned_and_locked_medals(): void
    {
        $student = $this->student();
        $badge = Badge::where('code', 'first_step')->firstOrFail();
        $student->badges()->attach($badge->id, ['awarded_at' => '2026-10-05 10:00:00']);

        $response = $this->actingAs($student)->get(route('student.achievements'))->assertOk();

        $response->assertSee('Yo‘l boshi')
            ->assertSee('Birinchi darsga qatnashdingiz')
            ->assertSee('✓ Olingan · 05.10.2026')
            ->assertSee('🔒 Qulflangan')
            ->assertSee('Bronza')
            ->assertSee('Oltin')
            ->assertSee('Po‘lat iroda');

        $html = $response->getContent();
        $this->assertSame(5, preg_match_all('/class="achv \w+ locked"/', $html));
        $this->assertSame(1, preg_match_all('/class="achv \w+ earned"/', $html));
        $this->assertStringContainsString('--i: 0', $html);
        $this->assertStringContainsString('--i: 5', $html);
    }

    public function test_admin_gamification_page_lists_the_medals_without_status(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.gamification.index'))
            ->assertOk()
            ->assertSee('Olov yurak')
            ->assertSee('Ketma-ket 7 ta darsga qatnashdingiz')
            ->assertDontSee('Qulflangan')
            ->assertDontSee('Olingan');
    }

    public function test_a_badge_from_the_last_week_is_marked_new(): void
    {
        $student = $this->student();
        $student->badges()->attach(Badge::where('code', 'first_step')->value('id'), ['awarded_at' => now()->subDays(2)]);
        $student->badges()->attach(Badge::where('code', 'first_assignment')->value('id'), ['awarded_at' => now()->subDays(20)]);

        $html = $this->actingAs($student)->get(route('student.achievements'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<span class="achv-new">Yangi!</span>'));
        $this->assertSame(1, preg_match_all('/class="achv \w+ earned fresh"/', $html));
    }

    public function test_badge_animations_exist_and_respect_reduced_motion(): void
    {
        $css = file_get_contents(public_path('css/erp.css'));

        foreach (['achv-in', 'achv-shine', 'achv-flip', 'achv-glow', 'achv-pulse', 'achv-shake', 'achv-bounce'] as $animation) {
            $this->assertStringContainsString("@keyframes {$animation}", $css);
        }

        $this->assertMatchesRegularExpression('/prefers-reduced-motion: reduce\) \{[^}]*\.achv \.ico::after[^}]*animation: none !important/s', $css);
    }

    public function test_level_names_are_unusual_and_translated(): void
    {
        $this->assertSame(['Uchqun', 'Mash’al', 'Kometa', 'Daho'], Level::orderBy('min_xp')->pluck('name')->all());

        app()->setLocale('en');
        $this->assertSame(['Spark', 'Torch', 'Comet', 'Genius'], Level::orderBy('min_xp')->pluck('name')->map(fn ($n) => __($n))->all());

        app()->setLocale('ru');
        $this->assertSame(['Искра', 'Факел', 'Комета', 'Гений'], Level::orderBy('min_xp')->pluck('name')->map(fn ($n) => __($n))->all());
    }
}
