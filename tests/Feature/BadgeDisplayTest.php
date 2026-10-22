<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Badge;
use App\Models\Branch;
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
                'first_step' => ['Dars boshlandi', '🚀'],
                'streak_7' => ['Olovli hafta', '🔥'],
                'streak_30' => ['Temir intizom', '⚡'],
                'first_assignment' => ['Birinchi topshiriq', '✏️'],
                'excellent' => ['A‘lo natija', '🏆'],
                'ten_assignments' => ['Mehnatkash', '📚'],
            ],
            Badge::orderBy('id')->get()->mapWithKeys(fn (Badge $badge) => [$badge->code => [$badge->title, $badge->icon]])->all()
        );

        $this->assertSame(0, Badge::whereNull('description')->count());
    }

    public function test_seeder_and_migration_rename_badges_of_an_existing_database(): void
    {
        DB::table('badges')->where('code', 'excellent')->update(['title' => 'A‘lochi', 'icon' => '🎯', 'description' => null]);
        DB::table('badges')->where('code', 'streak_7')->update(['title' => '7 dars seriya', 'description' => null]);

        $this->seed(GamificationSeeder::class);

        $this->assertSame('A‘lo natija', Badge::where('code', 'excellent')->value('title'));

        DB::table('badges')->where('code', 'excellent')->update(['title' => 'A‘lochi', 'icon' => '🎯', 'description' => null]);

        (require database_path('migrations/2026_10_23_000002_rename_badges.php'))->up();

        $excellent = Badge::where('code', 'excellent')->firstOrFail();
        $this->assertSame(['A‘lo natija', '🏆'], [$excellent->title, $excellent->icon]);
        $this->assertSame('Vazifadan kamida 90% ball oldingiz', $excellent->description);
        $this->assertSame(6, Badge::count());
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

        $response->assertSee('Dars boshlandi')
            ->assertSee('Birinchi darsga qatnashdingiz')
            ->assertSee('✓ Olingan · 05.10.2026')
            ->assertSee('🔒 Qulflangan')
            ->assertSee('Bronza')
            ->assertSee('Oltin')
            ->assertSee('Temir intizom');

        $html = $response->getContent();
        $this->assertSame(5, preg_match_all('/class="achv \w+ locked"/', $html));
        $this->assertSame(6, preg_match_all('/class="achv \w+( locked)?"/', $html));
    }

    public function test_admin_gamification_page_lists_the_medals_without_status(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.gamification.index'))
            ->assertOk()
            ->assertSee('Olovli hafta')
            ->assertSee('Ketma-ket 7 ta darsga qatnashdingiz')
            ->assertDontSee('Qulflangan')
            ->assertDontSee('Olingan');
    }
}
