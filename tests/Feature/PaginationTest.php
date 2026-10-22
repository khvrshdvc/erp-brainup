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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(?Branch $branch = null): array
    {
        $branch ??= Branch::create(['name' => 'Test filial']);

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);

        $group = StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'Guruh '.$branch->id]);

        return [$branch, $teacher, $group];
    }

    private function makeAssignments(Branch $branch, StudyGroup $group, User $teacher, int $count): Collection
    {
        return collect(range(1, $count))->map(fn (int $i) => Assignment::create([
            'branch_id' => $branch->id,
            'study_group_id' => $group->id,
            'teacher_id' => $teacher->id,
            'title' => 'Vazifa '.$i,
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_at' => now()->addDay(),
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
            'created_at' => now()->subMinutes($count - $i),
        ]));
    }

    private function student(Branch $branch, array $attributes = []): User
    {
        return User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id, ...$attributes]);
    }

    public function test_teacher_assignments_are_paginated_by_20(): void
    {
        [$branch, $teacher, $group] = $this->makeGroup();
        $this->makeAssignments($branch, $group, $teacher, 25);

        $response = $this->actingAs($teacher)->get(route('teacher.assignments.index'))->assertOk();

        $this->assertCount(20, $response->viewData('assignments'));
        $this->assertSame(25, $response->viewData('assignments')->total());
        $response->assertSee('Sahifa 1 / 2', false);

        $this->assertCount(5, $this->actingAs($teacher)->get(route('teacher.assignments.index', ['page' => 2]))->viewData('assignments'));
    }

    public function test_student_submissions_are_paginated_and_summary_covers_all_pages(): void
    {
        [$branch, $teacher, $group] = $this->makeGroup();
        $student = $this->student($branch);
        $group->students()->attach($student->id);

        foreach ($this->makeAssignments($branch, $group, $teacher, 25) as $index => $assignment) {
            $graded = $index < 10;

            Submission::create([
                'branch_id' => $branch->id,
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'status' => $graded ? SubmissionStatus::Graded : SubmissionStatus::Submitted,
                'is_late' => false,
                'submitted_at' => now()->subMinutes(30 - $index),
                'score' => $graded ? 80 : null,
            ]);
        }

        $response = $this->actingAs($student)->get(route('student.submissions.index'))->assertOk();

        $this->assertCount(20, $response->viewData('submissions'));
        $this->assertSame(25, $response->viewData('total'));
        $this->assertSame(10, $response->viewData('gradedCount'));
        $this->assertSame(15, $response->viewData('pendingCount'));
        $this->assertSame(80, $response->viewData('average'));

        $this->assertCount(5, $this->actingAs($student)->get(route('student.submissions.index', ['page' => 2]))->viewData('submissions'));
    }

    public function test_group_candidates_search_returns_active_students_not_in_the_group(): void
    {
        [$branch, , $group] = $this->makeGroup();
        $other = Branch::create(['name' => 'Boshqa filial']);

        $member = $this->student($branch, ['name' => 'Aziz Guruhda']);
        $group->students()->attach($member->id);
        $this->student($branch, ['name' => 'Aziz Nofaol', 'status' => UserStatus::Inactive]);
        $this->student($other, ['name' => 'Aziz Boshqa filial']);
        $this->student($branch, ['name' => 'Bobur Topiladi']);
        $this->student($branch, ['name' => 'Aziz Topiladi']);

        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.groups.candidates', ['studyGroup' => $group, 'q' => 'aziz']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Aziz Topiladi');

        $this->actingAs($admin)
            ->getJson(route('admin.groups.candidates', $group))
            ->assertJsonCount(2);
    }

    public function test_group_candidates_search_is_limited_to_20_results(): void
    {
        [$branch, , $group] = $this->makeGroup();

        User::factory()->count(25)->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.groups.candidates', $group))
            ->assertJsonCount(20);
    }

    public function test_group_candidates_search_is_closed_to_other_branches_and_teachers(): void
    {
        [$branch, $teacher, $group] = $this->makeGroup();
        $other = Branch::create(['name' => 'Boshqa filial']);

        $stranger = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $other->id]);

        $this->actingAs($stranger)->getJson(route('admin.groups.candidates', $group))->assertNotFound();
        $this->actingAs($teacher)->getJson(route('admin.groups.candidates', $group))->assertForbidden();
    }

    public function test_group_page_no_longer_lists_every_candidate(): void
    {
        [$branch, , $group] = $this->makeGroup();
        $this->student($branch, ['name' => 'Ro‘yxatda ko‘rinmasin']);

        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);

        $this->actingAs($admin)->get(route('admin.groups.show', $group))
            ->assertOk()
            ->assertDontSee('Ro‘yxatda ko‘rinmasin')
            ->assertSee('admin\/groups\/'.$group->id.'\/candidates', false);
    }
}
