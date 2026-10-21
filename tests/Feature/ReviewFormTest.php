<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFormTest extends TestCase
{
    use RefreshDatabase;

    private function setupSubmission(SubmissionStatus $status, ?int $score = null, ?string $feedback = null): array
    {
        $branch = Branch::create(['name' => 'Tekshirish filiali']);

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);

        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $group = StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'Guruh']);
        $group->students()->attach($student->id);

        $assignment = Assignment::create([
            'branch_id' => $branch->id,
            'study_group_id' => $group->id,
            'teacher_id' => $teacher->id,
            'title' => 'Essay',
            'description' => 'Yozing',
            'max_score' => 100,
            'due_at' => now()->addDay(),
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
        ]);

        $submission = Submission::create([
            'branch_id' => $branch->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => $status,
            'submitted_at' => now(),
            'score' => $score,
            'feedback' => $feedback,
            'graded_by' => $status === SubmissionStatus::Submitted ? null : $teacher->id,
            'graded_at' => $status === SubmissionStatus::Submitted ? null : now(),
        ]);

        return [$teacher, $student, $assignment, $submission];
    }

    public function test_pending_submission_shows_the_grading_form(): void
    {
        [$teacher, , $assignment] = $this->setupSubmission(SubmissionStatus::Submitted);

        $this->actingAs($teacher)->get(route('teacher.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Tekshirildi deb saqlash')
            ->assertSee('Qayta topshirishga qaytarish');
    }

    public function test_graded_submission_shows_result_instead_of_form(): void
    {
        [$teacher, $student, $assignment] = $this->setupSubmission(SubmissionStatus::Graded, 95, 'Zo‘r ish');

        $url = route('teacher.assignments.show', [$assignment, 'student' => $student->id]);

        $this->actingAs($teacher)->get($url)
            ->assertOk()
            ->assertSee('95 / 100')
            ->assertSee('Zo‘r ish')
            ->assertSee('Bahoni o‘zgartirish')
            ->assertDontSee('Tekshirildi deb saqlash')
            ->assertDontSee('Qayta topshirishga qaytarish');

        $this->actingAs($teacher)->get($url.'&edit=1')
            ->assertOk()
            ->assertSee('Tekshirildi deb saqlash')
            ->assertSee('Bekor qilish');
    }

    public function test_returned_submission_waits_for_the_student(): void
    {
        [$teacher, $student, $assignment] = $this->setupSubmission(SubmissionStatus::Returned, null, 'Qayta yozing');

        $this->actingAs($teacher)->get(route('teacher.assignments.show', [$assignment, 'student' => $student->id]))
            ->assertOk()
            ->assertSee('qayta topshirishi kutilmoqda')
            ->assertSee('Qayta yozing')
            ->assertDontSee('Tekshirildi deb saqlash');
    }

    public function test_after_grading_the_form_is_gone(): void
    {
        [$teacher, , $assignment, $submission] = $this->setupSubmission(SubmissionStatus::Submitted);

        $this->actingAs($teacher)
            ->followingRedirects()
            ->put(route('teacher.submissions.update', $submission), ['action' => 'grade', 'score' => 90, 'feedback' => 'Yaxshi'])
            ->assertOk()
            ->assertSee('90 / 100')
            ->assertDontSee('Tekshirildi deb saqlash');
    }
}
