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

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

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

        return [$branch, $teacher, $student, $group];
    }

    private function payload(User $student, string $status): array
    {
        return [
            'lesson_date' => now()->toDateString(),
            'records' => [$student->id => $status],
        ];
    }

    public function test_teacher_can_mark_attendance(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();

        $this->actingAs($teacher)
            ->post(route('attendance.store', $group), $this->payload($student, 'present'))
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'study_group_id' => $group->id,
            'status' => AttendanceStatus::Present->value,
        ]);
    }

    public function test_marking_twice_updates_the_same_record(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();

        $this->actingAs($teacher)->post(route('attendance.store', $group), $this->payload($student, 'present'));
        $this->actingAs($teacher)->post(route('attendance.store', $group), $this->payload($student, 'absent'));

        $this->assertSame(1, Attendance::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => AttendanceStatus::Absent->value,
        ]);
    }

    public function test_other_teacher_cannot_mark_attendance(): void
    {
        [$branch, , $student, $group] = $this->makeGroup();

        $other = User::factory()->create(['role' => Role::Teacher]);
        $other->branches()->attach($branch->id);

        $this->actingAs($other)
            ->post(route('attendance.store', $group), $this->payload($student, 'present'))
            ->assertForbidden();
    }

    public function test_teacher_cannot_open_group_of_another_branch(): void
    {
        [, $teacher, , ] = $this->makeGroup();

        $otherBranch = Branch::create(['name' => 'Boshqa filial']);
        $foreign = StudyGroup::create(['branch_id' => $otherBranch->id, 'name' => 'Begona guruh']);

        $this->actingAs($teacher)
            ->get(route('attendance.create', $foreign))
            ->assertNotFound();
    }

    public function test_student_cannot_open_attendance_form(): void
    {
        [, , $student, $group] = $this->makeGroup();

        $this->actingAs($student)
            ->get(route('attendance.create', $group))
            ->assertForbidden();
    }

    public function test_student_outside_group_is_rejected(): void
    {
        [$branch, $teacher, , $group] = $this->makeGroup();

        $stranger = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $this->actingAs($teacher)
            ->post(route('attendance.store', $group), $this->payload($stranger, 'present'))
            ->assertStatus(422);
    }
}
