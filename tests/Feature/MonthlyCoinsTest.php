<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\XpReason;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpRule;
use App\Models\XpTransaction;
use App\Services\GamificationService;
use Carbon\CarbonImmutable;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coins under the monthly rules (from GamificationService::MONTHLY_RULES_FROM). November 2026 has
 * 13 Mon/Wed/Fri lessons, 12 Tue/Thu/Sat lessons and 25 lessons from Monday to Saturday.
 */
class MonthlyCoinsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationSeeder::class);

        Setting::put('attendance_edit_days', 3);

        $this->travelTo('2026-11-30 18:00:00');

        $this->branch = Branch::create(['name' => 'Test filial']);
        $this->teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->teacher->branches()->attach($this->branch->id);
    }

    private function group(string $name, ?array $days = ['mon', 'wed', 'fri']): StudyGroup
    {
        return StudyGroup::create([
            'branch_id' => $this->branch->id,
            'teacher_id' => $this->teacher->id,
            'name' => $name,
            'schedule' => $days ? ['days' => $days, 'time' => '14:00'] : null,
            'status' => GroupStatus::Active,
        ]);
    }

    private function student(array $groups, string $joinedAt = '2026-10-01'): User
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id]);

        foreach ($groups as $group) {
            $group->students()->attach($student->id, ['joined_at' => $joinedAt.' 09:00:00']);
        }

        return $student;
    }

    /**
     * Marks on the lesson's own evening, as a teacher would: the plan depends on today's date.
     */
    private function mark(StudyGroup $group, User $student, string $date, AttendanceStatus $status = AttendanceStatus::Present): void
    {
        $this->travelTo($date.' 18:00:00');

        $attendance = Attendance::withoutGlobalScopes()->updateOrCreate(
            ['study_group_id' => $group->id, 'student_id' => $student->id, 'lesson_date' => $date],
            ['branch_id' => $group->branch_id, 'status' => $status],
        );

        app(GamificationService::class)->syncAttendance($attendance);
    }

    private function markAll(StudyGroup $group, User $student, array $days, string $from = '2026-11-01'): void
    {
        foreach ($this->dates($days, $from) as $date) {
            $this->mark($group, $student, $date);
        }
    }

    /**
     * @return list<string> the November 2026 dates falling on $days
     */
    private function dates(array $days, string $from = '2026-11-01'): array
    {
        $dates = [];

        for ($day = CarbonImmutable::parse($from); $day->lte('2026-11-30'); $day = $day->addDay()) {
            if (in_array(strtolower($day->format('D')), $days, true)) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }

    public function test_marking_attendance_adds_the_monthly_share_right_away(): void
    {
        $this->travelTo('2026-11-02 15:00:00');

        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        $this->actingAs($this->teacher)->post(route('attendance.store', $group), [
            'lesson_date' => '2026-11-02',
            'records' => [$student->id => 'present'],
        ])->assertRedirect();

        // 1 of 13 lessons: floor(100 / 13).
        $this->assertSame(7, $student->fresh()->xp);
    }

    public function test_students_with_one_two_or_daily_lessons_earn_the_same_for_a_full_month(): void
    {
        $math = $this->group('Matematika', ['mon', 'wed', 'fri']);
        $english = $this->group('Ingliz tili', ['tue', 'thu', 'sat']);
        $daily = $this->group('Intensiv', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);

        $oneSubject = $this->student([$math]);
        $twoSubjects = $this->student([$math, $english]);
        $everyDay = $this->student([$daily]);

        $this->markAll($math, $oneSubject, ['mon', 'wed', 'fri']);
        $this->markAll($math, $twoSubjects, ['mon', 'wed', 'fri']);
        $this->markAll($english, $twoSubjects, ['tue', 'thu', 'sat']);
        $this->markAll($daily, $everyDay, ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);

        foreach ([$oneSubject, $twoSubjects, $everyDay] as $student) {
            $this->assertSame(100 + 30, $student->fresh()->xp);
        }

        // The perfect-month bonus replaces streak bonuses under the monthly rules.
        $this->assertSame(25, $everyDay->fresh()->streak);
        $this->assertFalse(XpTransaction::where('reason', XpReason::StreakBonus->value)->where('points', '>', 0)->exists());
    }

    public function test_the_share_grows_with_each_lesson(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);
        $dates = $this->dates(['mon', 'wed', 'fri']);

        $earned = [];

        foreach ($dates as $date) {
            $this->mark($group, $student, $date);
            $earned[] = $student->fresh()->xp;
        }

        // floor(100 * n / 13) for n = 1..12, then the full pool and the bonus with the last lesson.
        $this->assertSame([7, 15, 23, 30, 38, 46, 53, 61, 69, 76, 84, 92, 130], $earned);
    }

    public function test_late_counts_as_half_a_lesson(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        $this->mark($group, $student, '2026-11-02');
        $this->mark($group, $student, '2026-11-04', AttendanceStatus::Late);

        // 1.5 of 13 lessons.
        $this->assertSame(11, $student->fresh()->xp);
    }

    public function test_an_absence_lowers_the_share_and_loses_the_bonus(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        $this->markAll($group, $student, ['mon', 'wed', 'fri']);
        $this->mark($group, $student, '2026-11-11', AttendanceStatus::Absent);

        // 12 of 13 lessons.
        $this->assertSame(92, $student->fresh()->xp);
    }

    public function test_an_excused_absence_is_left_out_of_the_plan_but_loses_the_bonus(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        $this->markAll($group, $student, ['mon', 'wed', 'fri']);
        $this->mark($group, $student, '2026-11-11', AttendanceStatus::Excused);

        $this->assertSame(100, $student->fresh()->xp);
    }

    public function test_a_lesson_nobody_was_marked_for_counts_as_cancelled_after_the_edit_window(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        foreach (array_diff($this->dates(['mon', 'wed', 'fri']), ['2026-11-09', '2026-11-30']) as $date) {
            $this->mark($group, $student, $date);
        }

        // 9 November has passed the 3-day edit window and is cancelled; today's lesson is still expected.
        $this->assertSame(91, $student->fresh()->xp);

        $this->mark($group, $student, '2026-11-30');

        $this->assertSame(100 + 30, $student->fresh()->xp);
    }

    public function test_a_student_who_joins_mid_month_is_expected_only_from_that_day(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group], joinedAt: '2026-11-16');

        $this->markAll($group, $student, ['mon', 'wed', 'fri'], from: '2026-11-16');

        $this->assertSame(100 + 30, $student->fresh()->xp);
    }

    public function test_a_group_without_a_schedule_is_assumed_to_have_twelve_lessons(): void
    {
        $group = $this->group('Matematika', days: null);
        $student = $this->student([$group]);

        foreach (['2026-11-02', '2026-11-04', '2026-11-06', '2026-11-09', '2026-11-11', '2026-11-13'] as $date) {
            $this->mark($group, $student, $date);
        }

        $this->assertSame(50, $student->fresh()->xp);
    }

    public function test_lessons_before_the_monthly_rules_keep_their_per_lesson_coins(): void
    {
        $group = $this->group('Matematika');
        $student = $this->student([$group]);

        $this->travelTo('2026-10-30 18:00:00');
        $this->mark($group, $student, '2026-10-30');
        $this->assertSame(10, $student->fresh()->xp);

        $this->travelTo('2026-11-02 18:00:00');
        $this->mark($group, $student, '2026-11-02');
        $this->assertSame(10 + 7, $student->fresh()->xp);
    }

    public function test_assignment_coins_are_split_between_the_students_groups(): void
    {
        $math = $this->group('Matematika', ['mon', 'wed', 'fri']);
        $english = $this->group('Ingliz tili', ['tue', 'thu', 'sat']);

        $oneSubject = $this->student([$math]);
        $twoSubjects = $this->student([$math, $english]);

        $assignment = Assignment::create([
            'branch_id' => $this->branch->id,
            'study_group_id' => $math->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Kasrlar',
            'max_score' => 100,
            'status' => AssignmentStatus::Published,
            'published_at' => '2026-11-09 10:00:00',
        ]);

        foreach ([$oneSubject, $twoSubjects] as $student) {
            $submission = Submission::create([
                'branch_id' => $this->branch->id,
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'status' => SubmissionStatus::Graded,
                'is_late' => false,
                'submitted_at' => '2026-11-10 18:00:00',
                'score' => 95,
            ]);

            app(GamificationService::class)->syncSubmission($submission);
        }

        $this->assertSame(20 + 50, $oneSubject->fresh()->xp);
        $this->assertSame(10 + 25, $twoSubjects->fresh()->xp);
    }

    public function test_rebuild_reproduces_the_monthly_coins(): void
    {
        $math = $this->group('Matematika', ['mon', 'wed', 'fri']);
        $english = $this->group('Ingliz tili', ['tue', 'thu', 'sat']);
        $student = $this->student([$math, $english]);

        $this->travelTo('2026-10-30 18:00:00');
        $this->mark($math, $student, '2026-10-30');

        $this->travelTo('2026-11-30 18:00:00');
        $this->markAll($math, $student, ['mon', 'wed', 'fri']);
        $this->mark($english, $student, '2026-11-03');

        $earned = $student->fresh()->xp;

        // October: 10 per lesson; November: 14 of 25 lessons.
        $this->assertSame(10 + 56, $earned);

        $this->artisan('gamification:rebuild', ['--fresh' => true])->assertSuccessful();

        $this->assertSame($earned, $student->fresh()->xp);
    }

    public function test_super_admin_edits_the_monthly_pool_and_no_longer_sees_per_lesson_rules(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.gamification.index'))
            ->assertOk()
            ->assertSee('Oylik davomat fondi')
            ->assertDontSee('7 darslik seriya');

        $rule = XpRule::where('code', 'attendance_month')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.gamification.update'), [
            'rules' => [$rule->id => ['points' => 260, 'is_active' => 1]],
        ])->assertRedirect();

        $this->assertSame(260, XpRule::pointsFor('attendance_month'));

        $group = $this->group('Matematika');
        $student = $this->student([$group]);
        $this->mark($group, $student, '2026-11-02');

        $this->assertSame(20, $student->fresh()->xp);
    }
}
