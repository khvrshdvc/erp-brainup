<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentGroupPageTest extends TestCase
{
    use RefreshDatabase;

    private function setupGroup(): array
    {
        $branch = Branch::create(['name' => 'Guruh filiali']);

        $teacher = User::factory()->create(['role' => Role::Teacher, 'name' => 'Malika Karimova']);
        $teacher->branches()->attach($branch->id);

        $group = StudyGroup::create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'name' => 'IELTS kechki',
            'schedule' => ['days' => ['mon', 'wed', 'fri'], 'time' => '18:00'],
        ]);

        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);
        $group->students()->attach($student->id);

        return [$branch, $teacher, $student, $group];
    }

    public function test_student_sees_group_teacher_and_schedule(): void
    {
        [, , $student] = $this->setupGroup();

        $this->actingAs($student)->get(route('student.groups.index'))
            ->assertOk()
            ->assertSee('IELTS kechki')
            ->assertSee('Malika Karimova')
            ->assertSee('Dushanba')
            ->assertSee('18:00');
    }

    public function test_attendance_percent_is_shown(): void
    {
        [$branch, , $student, $group] = $this->setupGroup();

        foreach ([AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Absent] as $index => $status) {
            Attendance::create([
                'branch_id' => $branch->id,
                'study_group_id' => $group->id,
                'student_id' => $student->id,
                'lesson_date' => today()->subDays($index),
                'status' => $status,
            ]);
        }

        $this->actingAs($student)->get(route('student.groups.index'))->assertOk()->assertSee('67%');
    }

    public function test_student_without_group_gets_empty_state(): void
    {
        $branch = Branch::create(['name' => 'Bo‘sh filial']);
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $this->actingAs($student)->get(route('student.groups.index'))
            ->assertOk()
            ->assertSee('Guruhga biriktirilmagan');
    }

    public function test_other_group_in_query_is_ignored(): void
    {
        [$branch, , $student] = $this->setupGroup();

        $foreign = StudyGroup::create(['branch_id' => $branch->id, 'name' => 'Begona guruh']);

        $this->actingAs($student)->get(route('student.groups.index', ['group' => $foreign->id]))
            ->assertOk()
            ->assertDontSee('Begona guruh');
    }

    public function test_teacher_cannot_open_student_group_page(): void
    {
        [, $teacher] = $this->setupGroup();

        $this->actingAs($teacher)->get(route('student.groups.index'))->assertForbidden();
    }
}
