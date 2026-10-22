<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_groups_and_recent_attendance(): void
    {
        $branch = Branch::create(['name' => 'A']);
        $teacher = User::factory()->teacher()->inBranch($branch)->create(['name' => 'Robiya Yunusova']);
        $student = User::factory()->student()->inBranch($branch)->create();

        $group = StudyGroup::factory()->create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'name' => 'ER-1',
            'schedule' => ['days' => ['mon', 'wed'], 'time' => '14:00'],
        ]);
        $group->students()->attach($student->id);

        foreach ([AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Absent, AttendanceStatus::Present] as $i => $status) {
            Attendance::create([
                'branch_id' => $branch->id,
                'study_group_id' => $group->id,
                'student_id' => $student->id,
                'lesson_date' => now()->subDays($i + 1)->toDateString(),
                'status' => $status,
                'marked_by' => $teacher->id,
            ]);
        }

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('ER-1')
            ->assertSee('Robiya Yunusova')
            ->assertSee('Du, Ch · 14:00')
            ->assertSee('75%')
            ->assertSee('Kechikdi');
    }
}
