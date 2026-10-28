<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\UserStatus;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpRule;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AssertsValidation;
use Tests\TestCase;

/**
 * Every form of the application, one by one: bad input must come back to the form with a message
 * for exactly the wrong fields, shown on the page, in the user's language.
 */
class FormValidationTest extends TestCase
{
    use AssertsValidation;
    use RefreshDatabase;

    private Branch $branch;

    private Branch $other;

    private User $superAdmin;

    private User $admin;

    private User $teacher;

    private User $student;

    private StudyGroup $group;

    private Assignment $assignment;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(GamificationSeeder::class);

        $this->branch = Branch::create(['name' => 'Main branch']);
        $this->other = Branch::create(['name' => 'Other branch']);

        $this->superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $this->admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $this->branch->id]);
        $this->teacher = User::factory()->create(['role' => Role::Teacher, 'email' => 'teacher@test.uz', 'phone' => '+998901112233']);
        $this->teacher->branches()->attach($this->branch->id);
        $this->student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id, 'email' => 'student@test.uz']);

        $this->group = StudyGroup::create([
            'branch_id' => $this->branch->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'English-1',
            'capacity' => 10,
        ]);
        $this->group->students()->attach($this->student->id);

        $this->assignment = Assignment::create([
            'branch_id' => $this->branch->id,
            'study_group_id' => $this->group->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Essay',
            'description' => 'Write.',
            'max_score' => 50,
            'due_at' => now()->addDays(3),
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
        ]);

        $this->submission = Submission::create([
            'branch_id' => $this->branch->id,
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'status' => SubmissionStatus::Submitted,
            'is_late' => false,
            'submitted_at' => now(),
        ]);
    }

    private function validGroup(array $override = []): array
    {
        return [
            'name' => 'New group',
            'branch_id' => $this->branch->id,
            'capacity' => 12,
            'status' => 'active',
            ...$override,
        ];
    }

    // ---------------------------------------------------------------- branches

    public function test_branch_form(): void
    {
        $form = route('admin.branches.create');
        $store = route('admin.branches.store');

        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [], ['name']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, ['name' => str_repeat('a', 121)], ['name']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, ['name' => 'Main branch'], ['name']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, ['name' => 'Fine', 'address' => str_repeat('a', 256), 'manager_name' => str_repeat('a', 121)], ['address', 'manager_name']);

        $this->assertInvalid($this->superAdmin, 'put', route('admin.branches.update', $this->other), route('admin.branches.edit', $this->other), ['name' => 'Main branch'], ['name']);
    }

    // ------------------------------------------------------------------ groups

    public function test_group_form(): void
    {
        $form = route('admin.groups.create');
        $store = route('admin.groups.store');

        $this->assertInvalid($this->admin, 'post', $store, $form, [], ['name', 'branch_id', 'capacity', 'status']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['name' => 'English-1']), ['name']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['capacity' => 0]), ['capacity']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['capacity' => 101]), ['capacity']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['capacity' => 'many']), ['capacity']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['status' => 'weird']), ['status']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['branch_id' => $this->other->id]), ['branch_id']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['course_id' => 9999]), ['course_id']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['teacher_id' => 9999]), ['teacher_id']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['starts_on' => '2026-05-10', 'ends_on' => '2026-05-01']), ['ends_on']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['starts_on' => 'tomorrow-ish']), ['starts_on']);
        $this->assertInvalid($this->admin, 'post', $store, $form, $this->validGroup(['schedule_days' => ['funday'], 'schedule_time' => '25:99']), ['schedule_days.0', 'schedule_time']);

        $edit = route('admin.groups.edit', $this->group);
        $this->assertInvalid($this->admin, 'put', route('admin.groups.update', $this->group), $edit, ['name' => 'English-1', 'capacity' => 0, 'status' => 'active'], ['capacity']);
    }

    public function test_adding_a_student_to_a_group(): void
    {
        $show = route('admin.groups.show', $this->group);
        $add = route('admin.groups.students.add', $this->group);
        $inactive = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id, 'status' => UserStatus::Inactive]);
        $foreign = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->other->id]);

        $this->assertInvalid($this->admin, 'post', $add, $show, [], ['student_id']);
        $this->assertInvalid($this->admin, 'post', $add, $show, ['student_id' => 99999], ['student_id']);
        $this->assertInvalid($this->admin, 'post', $add, $show, ['student_id' => $inactive->id], ['student_id']);
        $this->assertInvalid($this->admin, 'post', $add, $show, ['student_id' => $foreign->id], ['student_id']);
        $this->assertInvalid($this->admin, 'post', $add, $show, ['student_id' => $this->teacher->id], ['student_id']);
    }

    // ---------------------------------------------------------------- students

    public function test_student_form(): void
    {
        $form = route('admin.students.create');
        $store = route('admin.students.store');
        $valid = ['name' => 'Ali Valiyev', 'email' => 'ali@test.uz', 'password' => 'password123', 'branch_id' => $this->branch->id];

        // Email and password may be left empty: they are generated (IssuedCredentialsTest).
        $this->assertInvalid($this->admin, 'post', $store, $form, [], ['name', 'branch_id']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'email' => 'not-an-email'], ['email']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'email' => 'student@test.uz'], ['email']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'password' => 'short'], ['password']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'phone' => '+998901112233'], ['phone']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'phone' => str_repeat('9', 21)], ['phone']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'name' => str_repeat('a', 121)], ['name']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'birth_date' => now()->addDay()->toDateString()], ['birth_date']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'birth_date' => 'yesterday-ish'], ['birth_date']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'branch_id' => $this->other->id], ['branch_id']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'group_id' => 99999], ['group_id']);

        $this->assertInvalid($this->admin, 'put', route('admin.students.update', $this->student), route('admin.students.edit', $this->student), ['name' => 'X', 'email' => 'teacher@test.uz'], ['email']);
    }

    // ---------------------------------------------------------------- teachers

    public function test_teacher_form(): void
    {
        $form = route('admin.teachers.create');
        $store = route('admin.teachers.store');
        $valid = ['name' => 'Ustoz Ustozov', 'email' => 'new-teacher@test.uz', 'password' => 'password123', 'branches' => [$this->branch->id]];

        $this->assertInvalid($this->admin, 'post', $store, $form, [], ['name', 'branches']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'branches' => []], ['branches']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'branches' => [$this->other->id]], ['branches.0']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'email' => 'teacher@test.uz'], ['email']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'email' => 'broken'], ['email']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'password' => '1234567'], ['password']);
        $this->assertInvalid($this->admin, 'post', $store, $form, [...$valid, 'phone' => '+998901112233'], ['phone']);

        $edit = route('admin.teachers.edit', $this->teacher);
        $this->assertInvalid($this->admin, 'put', route('admin.teachers.update', $this->teacher), $edit, ['name' => '', 'email' => 'student@test.uz', 'branches' => [$this->branch->id]], ['name', 'email']);
    }

    // -------------------------------------------------------------- shop items

    public function test_shop_item_form(): void
    {
        $form = route('admin.shop-items.create');
        $store = route('admin.shop-items.store');
        $valid = ['name' => 'Notebook', 'price' => 100];

        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [], ['name', 'price']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'price' => 0], ['price']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'price' => 'cheap'], ['price']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'price' => 12.5], ['price']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'price' => 1000001], ['price']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'stock' => -1], ['stock']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'description' => str_repeat('a', 501)], ['description']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], ['image']);
        $this->assertInvalid($this->superAdmin, 'post', $store, $form, [...$valid, 'image' => UploadedFile::fake()->image('big.jpg')->size(3000)], ['image']);
    }

    // ---------------------------------------------------------------- settings

    public function test_center_settings_form(): void
    {
        $form = route('admin.settings.edit');
        $center = route('admin.settings.center');

        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => ''], ['center_name']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'attendance_edit_days' => -1], ['attendance_edit_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'attendance_edit_days' => 366], ['attendance_edit_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'attendance_edit_days' => 'a lot'], ['attendance_edit_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'attendance_edit_days' => ''], ['attendance_edit_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'submission_file_retention_days' => -5], ['submission_file_retention_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => 'Brainup', 'submission_file_retention_days' => 3651], ['submission_file_retention_days']);
        $this->assertInvalid($this->superAdmin, 'put', $center, $form, ['center_name' => str_repeat('a', 121), 'center_phone' => str_repeat('1', 31)], ['center_name', 'center_phone']);
    }

    public function test_admin_profile_and_password_forms(): void
    {
        $form = route('admin.settings.edit');

        $this->assertInvalid($this->admin, 'put', route('admin.settings.profile'), $form, [], ['name', 'email']);
        $this->assertInvalid($this->admin, 'put', route('admin.settings.profile'), $form, ['name' => 'A', 'email' => 'teacher@test.uz'], ['email']);

        $password = route('admin.settings.password');
        $this->assertInvalid($this->admin, 'put', $password, $form, [], ['current_password', 'password']);
        $this->assertInvalid($this->admin, 'put', $password, $form, ['current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'], ['current_password']);
        $this->assertInvalid($this->admin, 'put', $password, $form, ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'], ['password']);
        $this->assertInvalid($this->admin, 'put', $password, $form, ['current_password' => 'password', 'password' => 'new-password-1', 'password_confirmation' => 'other-password'], ['password']);
    }

    public function test_gamification_rules_form(): void
    {
        $form = route('admin.gamification.index');
        $update = route('admin.gamification.update');
        $rule = XpRule::firstOrFail();

        $this->assertInvalid($this->superAdmin, 'put', $update, $form, [], ['rules']);
        $this->assertInvalid($this->superAdmin, 'put', $update, $form, ['rules' => [$rule->id => ['points' => -1]]], ["rules.{$rule->id}.points"]);
        $this->assertInvalid($this->superAdmin, 'put', $update, $form, ['rules' => [$rule->id => ['points' => 1001]]], ["rules.{$rule->id}.points"]);
        $this->assertInvalid($this->superAdmin, 'put', $update, $form, ['rules' => [$rule->id => ['points' => 'lots']]], ["rules.{$rule->id}.points"]);
        $this->assertInvalid($this->superAdmin, 'put', $update, $form, ['rules' => [$rule->id => ['points' => '']]], ["rules.{$rule->id}.points"]);
    }

    // --------------------------------------------------------------- profile

    public function test_teacher_profile_and_password_forms(): void
    {
        $form = route('profile.edit');

        $this->assertInvalid($this->teacher, 'patch', route('profile.update'), $form, [], ['name', 'email']);
        $this->assertInvalid($this->teacher, 'patch', route('profile.update'), $form, ['name' => 'T', 'email' => 'student@test.uz'], ['email']);
        $this->assertInvalid($this->teacher, 'patch', route('profile.update'), $form, ['name' => 'T', 'email' => 'teacher@test.uz', 'phone' => str_repeat('1', 21)], ['phone']);

        $this->assertInvalid($this->teacher, 'put', route('profile.password'), $form, [], ['current_password', 'password']);
        $this->assertInvalid($this->teacher, 'put', route('profile.password'), $form, ['current_password' => 'nope', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'], ['current_password']);
    }

    // ---------------------------------------------------------------- teacher

    public function test_attendance_form(): void
    {
        $form = route('attendance.create', $this->group);
        $store = route('attendance.store', $this->group);
        $records = [$this->student->id => 'present'];

        $this->assertInvalid($this->teacher, 'post', $store, $form, [], ['lesson_date', 'records']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, ['lesson_date' => today()->toDateString(), 'records' => []], ['records']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, ['lesson_date' => today()->toDateString(), 'records' => [$this->student->id => 'sleeping']], ["records.{$this->student->id}"]);
        $this->assertInvalid($this->teacher, 'post', $store, $form, ['lesson_date' => today()->addDay()->toDateString(), 'records' => $records], ['lesson_date']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, ['lesson_date' => today()->subDays(30)->toDateString(), 'records' => $records], ['lesson_date']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, ['lesson_date' => 'someday', 'records' => $records], ['lesson_date']);
    }

    public function test_assignment_form(): void
    {
        $form = route('teacher.assignments.create');
        $store = route('teacher.assignments.store');
        $valid = [
            'title' => 'Homework', 'study_group_id' => $this->group->id, 'description' => 'Do it.', 'max_score' => 100,
            'due_at' => now()->addDays(2)->format('Y-m-d H:i'), 'action' => 'publish',
        ];

        $this->assertInvalid($this->teacher, 'post', $store, $form, [], ['title', 'study_group_id', 'description', 'max_score', 'due_at', 'action']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'title' => str_repeat('a', 161)], ['title']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'description' => str_repeat('a', 2001)], ['description']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'max_score' => 0], ['max_score']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'max_score' => 1001], ['max_score']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'max_score' => 'full'], ['max_score']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'due_at' => now()->subDay()->format('Y-m-d H:i')], ['due_at']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'due_at' => 'next week'], ['due_at']);
        // The form sends the deadline as a date plus a time.
        unset($valid['due_at']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'due_date' => 'next week', 'due_time' => '18:00'], ['due_at']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'due_date' => now()->addDay()->toDateString(), 'due_time' => '99:99'], ['due_at']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'due_date' => now()->subDay()->toDateString(), 'due_time' => '18:00'], ['due_at']);
        $valid['due_at'] = now()->addDays(2)->format('Y-m-d H:i');
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'action' => 'burn'], ['action']);

        $foreignGroup = StudyGroup::create(['branch_id' => $this->branch->id, 'name' => 'Not mine']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'study_group_id' => $foreignGroup->id], ['study_group_id']);

        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'material' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], ['material']);
        $this->assertInvalid($this->teacher, 'post', $store, $form, [...$valid, 'material' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf')], ['material']);
    }

    public function test_assignment_edit_form(): void
    {
        $edit = route('teacher.assignments.edit', $this->assignment);
        $update = route('teacher.assignments.update', $this->assignment);

        $this->assertInvalid($this->teacher, 'put', $update, $edit, [], ['title', 'description', 'max_score', 'due_at', 'action']);
        $this->assertInvalid($this->teacher, 'put', $update, $edit, ['title' => 'T', 'description' => 'D', 'max_score' => 5000, 'due_at' => now()->addDay()->format('Y-m-d H:i'), 'action' => 'publish'], ['max_score']);
    }

    public function test_grading_form(): void
    {
        $show = route('teacher.assignments.show', [$this->assignment, 'student' => $this->student->id]);
        $update = route('teacher.submissions.update', $this->submission);

        $this->assertInvalid($this->teacher, 'put', $update, $show, [], ['action']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'explode'], ['action']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'grade', 'score' => 51], ['score']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'grade', 'score' => -1], ['score']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'grade', 'score' => 'A+'], ['score']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'grade', 'score' => 40, 'feedback' => str_repeat('a', 2001)], ['feedback']);
        $this->assertInvalid($this->teacher, 'put', $update, $show, ['action' => 'return'], ['feedback']);
    }

    // ---------------------------------------------------------------- student

    public function test_student_submission_form(): void
    {
        $form = route('student.assignments.submit', $this->assignment);
        $store = route('student.assignments.submit.store', $this->assignment);
        $this->submission->delete();

        $this->assertInvalid($this->student, 'post', $store, $form, [], ['files']);
        $this->assertInvalid($this->student, 'post', $store, $form, ['files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]], ['files.0']);
        $this->assertInvalid($this->student, 'post', $store, $form, ['files' => [UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf')]], ['files.0']);
        $this->assertInvalid($this->student, 'post', $store, $form, ['files' => [UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf')], 'body' => str_repeat('a', 501)], ['body']);
    }

    // ------------------------------------------------------------------ locale

    public function test_language_form(): void
    {
        $this->assertInvalid($this->student, 'post', route('locale.switch'), route('student.dashboard'), ['locale' => 'klingon'], ['locale']);
        $this->assertInvalid($this->student, 'post', route('locale.switch'), route('student.dashboard'), [], ['locale']);
    }

    public function test_attendance_page_ignores_a_bad_date_instead_of_bouncing(): void
    {
        $today = today()->toDateString();

        foreach (['garbage', today()->addDay()->toDateString(), '2026-02-30', '2026-13-01', '', '2026-10-2'] as $date) {
            $this->actingAs($this->teacher)->get(route('attendance.create', [$this->group, 'date' => $date]))
                ->assertOk()
                ->assertViewHas('date', $today);
        }

        $this->actingAs($this->teacher)->get(route('attendance.create', [$this->group, 'date' => today()->subDays(2)->toDateString()]))
            ->assertOk()
            ->assertViewHas('date', today()->subDays(2)->toDateString());
    }
}
