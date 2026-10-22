<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranchWithGroup(string $name, ?User $teacher = null): array
    {
        $branch = Branch::create(['name' => $name]);
        $teacher ??= User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);

        $group = StudyGroup::create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'name' => $name.' guruhi',
        ]);

        return [$branch, $teacher, $group];
    }

    private function makeAssignment(Branch $branch, StudyGroup $group, User $teacher, string $title, AssignmentStatus $status = AssignmentStatus::Published): Assignment
    {
        return Assignment::create([
            'branch_id' => $branch->id,
            'study_group_id' => $group->id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_at' => now()->addDay(),
            'status' => $status,
            'published_at' => now(),
        ]);
    }

    public function test_branch_admin_sees_only_own_branch_assignments(): void
    {
        [$first, $teacherA, $groupA] = $this->makeBranchWithGroup('Birinchi');
        [$second, $teacherB, $groupB] = $this->makeBranchWithGroup('Ikkinchi');

        $this->makeAssignment($first, $groupA, $teacherA, 'Birinchi filial vazifasi');
        $foreign = $this->makeAssignment($second, $groupB, $teacherB, 'Ikkinchi filial vazifasi');

        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $first->id]);

        $this->actingAs($admin)->get(route('admin.assignments.index'))
            ->assertOk()
            ->assertSee('Birinchi filial vazifasi')
            ->assertDontSee('Ikkinchi filial vazifasi');

        $this->actingAs($admin)->get(route('admin.assignments.show', $foreign))->assertNotFound();
    }

    public function test_reports_show_attendance_percentage_and_export(): void
    {
        [$branch, , $group] = $this->makeBranchWithGroup('Hisobot');

        foreach ([AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Absent] as $index => $status) {
            $student = User::factory()->create([
                'role' => Role::Student,
                'branch_id' => $branch->id,
                'name' => 'Talaba '.$index,
            ]);
            $group->students()->attach($student->id);

            Attendance::create([
                'branch_id' => $branch->id,
                'study_group_id' => $group->id,
                'student_id' => $student->id,
                'lesson_date' => today(),
                'status' => $status,
            ]);
        }

        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        foreach (['30d', '6m', 'year'] as $period) {
            $this->actingAs($admin)->get(route('admin.reports.index', ['period' => $period]))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertSee('75%');

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('Talaba 0', $response->streamedContent());
    }

    public function test_export_neutralises_spreadsheet_formulas(): void
    {
        [$branch, , $group] = $this->makeBranchWithGroup('Formula');

        $student = User::factory()->create([
            'role' => Role::Student,
            'branch_id' => $branch->id,
            'name' => '=HYPERLINK("http://evil.test","bosing")',
        ]);

        Attendance::create([
            'branch_id' => $branch->id,
            'study_group_id' => $group->id,
            'student_id' => $student->id,
            'lesson_date' => today(),
            'status' => AttendanceStatus::Present,
        ]);

        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $csv = $this->actingAs($admin)->get(route('admin.reports.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
    }

    public function test_teacher_can_close_and_reopen_assignment(): void
    {
        [$branch, $teacher, $group] = $this->makeBranchWithGroup('Yopish');
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);
        $group->students()->attach($student->id);

        $assignment = $this->makeAssignment($branch, $group, $teacher, 'Yopiladigan vazifa');

        $this->actingAs($teacher)->post(route('teacher.assignments.close', $assignment))->assertRedirect();
        $this->assertSame(AssignmentStatus::Closed, $assignment->fresh()->status);

        $this->actingAs($student)->get(route('student.assignments.submit', $assignment))->assertForbidden();

        $this->actingAs($teacher)->post(route('teacher.assignments.reopen', $assignment))->assertRedirect();
        $this->assertSame(AssignmentStatus::Published, $assignment->fresh()->status);

        $this->actingAs($student)->get(route('student.assignments.submit', $assignment))->assertOk();
    }

    public function test_other_teacher_cannot_close_assignment(): void
    {
        [$branch, $teacher, $group] = $this->makeBranchWithGroup('Egasi');
        $assignment = $this->makeAssignment($branch, $group, $teacher, 'Boshqaniki');

        $other = User::factory()->create(['role' => Role::Teacher]);
        $other->branches()->attach($branch->id);

        $this->actingAs($other)->post(route('teacher.assignments.close', $assignment))->assertForbidden();
    }

    public function test_only_super_admin_updates_center_settings(): void
    {
        $branch = Branch::create(['name' => 'Sozlama']);
        $payload = ['center_name' => 'Brainup', 'academic_year' => '2026–2027'];

        $branchAdmin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);

        $this->actingAs($branchAdmin)->put(route('admin.settings.center'), $payload)->assertForbidden();

        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superAdmin)->put(route('admin.settings.center'), $payload)->assertRedirect();

        $this->assertSame('Brainup', Setting::get('center_name'));
        $this->assertSame('2026–2027', Setting::get('academic_year'));
    }

    public function test_super_admin_sets_the_attendance_edit_window(): void
    {
        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $payload = ['center_name' => 'Brainup', 'attendance_edit_days' => 7];

        $this->assertSame(3, Setting::attendanceEditDays());

        $this->actingAs($superAdmin)->put(route('admin.settings.center'), $payload)->assertRedirect();

        $this->assertSame(7, Setting::attendanceEditDays());

        $this->actingAs($superAdmin)
            ->put(route('admin.settings.center'), [...$payload, 'attendance_edit_days' => -1])
            ->assertSessionHasErrors('attendance_edit_days');
    }

    public function test_profile_and_password_updates(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'password' => 'oldpassword1']);

        $this->actingAs($admin)->put(route('admin.settings.profile'), [
            'name' => 'Yangi Ism',
            'email' => $admin->email,
        ])->assertRedirect();

        $this->assertSame('Yangi Ism', $admin->fresh()->name);

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'oldpassword1',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newpassword1', $admin->fresh()->password));
    }

    public function test_settings_and_report_pages_open(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.assignments.index'))->assertOk();
    }
}
