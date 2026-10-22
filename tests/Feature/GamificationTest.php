<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Enums\XpReason;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Level;
use App\Models\Setting;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\XpTransaction;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationSeeder::class);

        Setting::put('attendance_edit_days', 365);
    }

    private function makeGroup(): array
    {
        $branch = Branch::create(['name' => 'Test filial']);

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);

        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $group = StudyGroup::create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'name' => 'Test guruh',
        ]);
        $group->students()->attach($student->id);

        return [$teacher, $student, $group];
    }

    private function mark(User $teacher, StudyGroup $group, User $student, string $status, int $daysAgo = 0): void
    {
        $this->actingAs($teacher)->post(route('attendance.store', $group), [
            'lesson_date' => now()->subDays($daysAgo)->toDateString(),
            'records' => [$student->id => $status],
        ])->assertRedirect();
    }

    public function test_present_gives_xp_and_absent_revokes_it(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        $this->mark($teacher, $group, $student, 'present');
        $this->assertSame(10, $student->fresh()->xp);

        $this->mark($teacher, $group, $student, 'present');
        $this->assertSame(10, $student->fresh()->xp);

        $this->mark($teacher, $group, $student, 'absent');
        $this->assertSame(0, $student->fresh()->xp);
    }

    public function test_late_gives_reduced_xp(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        $this->mark($teacher, $group, $student, 'late');

        $this->assertSame(5, $student->fresh()->xp);
    }

    public function test_seven_lesson_streak_gives_bonus_and_badges(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        foreach (range(6, 0) as $daysAgo) {
            $this->mark($teacher, $group, $student, 'present', $daysAgo);
        }

        $student = $student->fresh();

        $this->assertSame(7 * 10 + 30, $student->xp);
        $this->assertSame(7, $student->streak);
        $this->assertEqualsCanonicalizing(
            ['first_step', 'streak_7'],
            $student->badges()->pluck('code')->all()
        );
    }

    public function test_editing_a_past_day_to_absent_removes_the_later_streak_bonus(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        foreach (range(6, 0) as $daysAgo) {
            $this->mark($teacher, $group, $student, 'present', $daysAgo);
        }

        $this->assertSame(7 * 10 + 30, $student->fresh()->xp);

        $this->mark($teacher, $group, $student, 'absent', 4);

        $student = $student->fresh();

        $this->assertSame(6 * 10, $student->xp);
        $this->assertSame(4, $student->streak);

        $this->mark($teacher, $group, $student, 'present', 4);

        $this->assertSame(7 * 10 + 30, $student->fresh()->xp);
    }

    public function test_two_lessons_on_one_day_count_as_one_streak_day(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        $second = StudyGroup::create([
            'branch_id' => $group->branch_id,
            'teacher_id' => $teacher->id,
            'name' => 'Ikkinchi guruh',
        ]);
        $second->students()->attach($student->id);

        foreach (range(6, 1) as $daysAgo) {
            $this->mark($teacher, $group, $student, 'present', $daysAgo);
        }

        $this->mark($teacher, $group, $student, 'present');
        $this->mark($teacher, $second, $student, 'present');

        $student = $student->fresh();

        $this->assertSame(7, $student->streak);
        $this->assertSame(8 * 10 + 30, $student->xp);
        $this->assertSame(1, XpTransaction::where('user_id', $student->id)
            ->where('reason', XpReason::StreakBonus->value)
            ->where('points', '>', 0)
            ->count());
    }

    public function test_a_day_with_one_absence_and_one_attended_lesson_keeps_the_streak(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        $second = StudyGroup::create([
            'branch_id' => $group->branch_id,
            'teacher_id' => $teacher->id,
            'name' => 'Ikkinchi guruh',
        ]);
        $second->students()->attach($student->id);

        $this->mark($teacher, $group, $student, 'present', 2);
        $this->mark($teacher, $group, $student, 'absent', 1);
        $this->mark($teacher, $second, $student, 'present', 1);
        $this->mark($teacher, $group, $student, 'present');

        $this->assertSame(3, $student->fresh()->streak);
    }

    public function test_absence_breaks_the_streak(): void
    {
        [$teacher, $student, $group] = $this->makeGroup();

        $this->mark($teacher, $group, $student, 'present', 3);
        $this->mark($teacher, $group, $student, 'present', 2);
        $this->mark($teacher, $group, $student, 'absent', 1);
        $this->mark($teacher, $group, $student, 'present', 0);

        $this->assertSame(1, $student->fresh()->streak);
    }

    public function test_level_is_chosen_by_xp(): void
    {
        $this->assertSame('Boshlovchi', Level::forXp(499)->name);
        $this->assertSame('Izlanuvchi', Level::forXp(500)->name);
    }

    public function test_rebuild_command_recalculates_xp(): void
    {
        [, $student, $group] = $this->makeGroup();

        Attendance::create([
            'branch_id' => $group->branch_id,
            'study_group_id' => $group->id,
            'student_id' => $student->id,
            'lesson_date' => now()->toDateString(),
            'status' => AttendanceStatus::Present,
        ]);

        $this->artisan('gamification:rebuild')->assertSuccessful();

        $this->assertSame(10, $student->fresh()->xp);
    }

    public function test_student_can_open_achievements_page(): void
    {
        [, $student] = $this->makeGroup();

        $this->actingAs($student)->get(route('student.achievements'))->assertOk();
        $this->actingAs($student)->get(route('student.achievements', ['scope' => 'all']))->assertOk();
    }

    public function test_only_super_admin_opens_gamification_settings(): void
    {
        [$teacher] = $this->makeGroup();

        $this->actingAs($teacher)->get(route('admin.gamification.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.gamification.index'))->assertOk();
    }
}
