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
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(GamificationSeeder::class);
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

        return [$branch, $teacher, $student, $group];
    }

    private function createAssignment(User $teacher, StudyGroup $group, string $action = 'publish'): Assignment
    {
        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'title' => 'Writing Task 2',
            'study_group_id' => $group->id,
            'description' => 'Insho yozing',
            'max_score' => 100,
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'action' => $action,
        ])->assertRedirect();

        return Assignment::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function submit(User $student, Assignment $assignment, string $name = 'essay.pdf')
    {
        return $this->actingAs($student)->post(route('student.assignments.submit.store', $assignment), [
            'file' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
            'body' => 'Tayyor',
        ]);
    }

    public function test_draft_is_hidden_from_students_until_published(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();

        $assignment = $this->createAssignment($teacher, $group, 'draft');

        $this->assertSame(AssignmentStatus::Draft, $assignment->status);
        $this->actingAs($student)->get(route('student.assignments.show', $assignment))->assertNotFound();

        $this->actingAs($teacher)->post(route('teacher.assignments.publish', $assignment))->assertRedirect();

        $this->actingAs($student)->get(route('student.assignments.show', $assignment))->assertOk();
    }

    public function test_student_submits_on_time_and_gets_xp(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);

        $this->submit($student, $assignment)->assertRedirect();

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertFalse($submission->is_late);
        $this->assertSame(20, $student->fresh()->xp);
        $this->assertSame(1, $submission->attachments()->count());
    }

    public function test_resubmitting_replaces_the_file(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);

        $this->submit($student, $assignment, 'first.pdf');
        $this->submit($student, $assignment, 'second.pdf');

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(1, Submission::withoutGlobalScopes()->count());
        $this->assertSame(1, $submission->attachments()->count());
        $this->assertSame('second.pdf', $submission->attachments()->first()->original_name);
    }

    public function test_teacher_grades_and_high_score_gives_bonus_xp_and_badges(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($teacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 95,
            'feedback' => 'Zo‘r',
        ])->assertRedirect();

        $student = $student->fresh();

        $this->assertSame(SubmissionStatus::Graded, $submission->fresh()->status);
        $this->assertSame(20 + 50, $student->xp);
        $this->assertEqualsCanonicalizing(
            ['first_assignment', 'excellent'],
            $student->badges()->pluck('code')->all()
        );
    }

    public function test_graded_submission_cannot_be_resubmitted(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($teacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 70,
        ]);

        $this->submit($student, $assignment)->assertForbidden();
    }

    public function test_returning_requires_feedback_and_allows_resubmission(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($teacher)
            ->put(route('teacher.submissions.update', $submission), ['action' => 'return'])
            ->assertSessionHasErrors('feedback');

        $this->actingAs($teacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'return',
            'feedback' => 'Qayta yozing',
        ])->assertRedirect();

        $this->assertSame(SubmissionStatus::Returned, $submission->fresh()->status);

        $this->submit($student, $assignment)->assertRedirect();

        $this->assertSame(SubmissionStatus::Submitted, $submission->fresh()->status);
    }

    public function test_other_teacher_cannot_grade(): void
    {
        [$branch, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $other = User::factory()->create(['role' => Role::Teacher]);
        $other->branches()->attach($branch->id);

        $this->actingAs($other)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 50,
        ])->assertForbidden();
    }

    public function test_changing_group_teacher_moves_open_assignments_and_access(): void
    {
        [$branch, $oldTeacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($oldTeacher, $group);
        $closed = $this->createAssignment($oldTeacher, $group);
        $closed->update(['status' => AssignmentStatus::Closed]);
        $this->submit($student, $assignment);

        $submission = Submission::withoutGlobalScopes()->where('assignment_id', $assignment->id)->firstOrFail();
        $attachment = $submission->attachments()->firstOrFail();

        $newTeacher = User::factory()->create(['role' => Role::Teacher]);
        $newTeacher->branches()->attach($branch->id);
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->put(route('admin.groups.update', $group), [
            'name' => $group->name,
            'capacity' => 20,
            'status' => 'active',
            'teacher_id' => $newTeacher->id,
        ])->assertRedirect();

        $this->assertSame($newTeacher->id, $assignment->fresh()->teacher_id);
        $this->assertSame($oldTeacher->id, $closed->fresh()->teacher_id);

        $this->actingAs($oldTeacher)->get(route('attachments.download', $attachment))->assertForbidden();
        $this->actingAs($oldTeacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 50,
        ])->assertForbidden();

        $this->actingAs($newTeacher)->get(route('attachments.download', $attachment))->assertOk();
        $this->actingAs($newTeacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 50,
        ])->assertRedirect();

        $this->assertSame(SubmissionStatus::Graded, $submission->fresh()->status);
    }

    public function test_assignment_group_cannot_be_changed_on_edit(): void
    {
        [$branch, $teacher, , $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);

        $other = StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'Boshqa guruh']);

        $this->actingAs($teacher)->put(route('teacher.assignments.update', $assignment), [
            'title' => 'Yangi nom',
            'study_group_id' => $other->id,
            'description' => 'Insho yozing',
            'max_score' => 100,
            'due_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'action' => 'publish',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $assignment->refresh();

        $this->assertSame('Yangi nom', $assignment->title);
        $this->assertSame($group->id, $assignment->study_group_id);
    }

    public function test_student_outside_group_cannot_open_assignment(): void
    {
        [$branch, $teacher, , $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);

        $stranger = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $this->actingAs($stranger)->get(route('student.assignments.show', $assignment))->assertNotFound();
    }

    public function test_attachment_download_is_limited_to_owner_and_teacher(): void
    {
        [$branch, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $attachment = Submission::withoutGlobalScopes()->firstOrFail()->attachments()->firstOrFail();

        $this->actingAs($student)->get(route('attachments.download', $attachment))->assertOk();
        $this->actingAs($teacher)->get(route('attachments.download', $attachment))->assertOk();

        $stranger = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $this->actingAs($stranger)->get(route('attachments.download', $attachment))->assertForbidden();
    }

    public function test_pages_open_for_both_roles(): void
    {
        [, $teacher, $student, $group] = $this->makeGroup();
        $assignment = $this->createAssignment($teacher, $group);
        $this->submit($student, $assignment);

        $this->actingAs($teacher)->get(route('teacher.assignments.index'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.assignments.create'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.assignments.show', $assignment))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.assignments.edit', $assignment))->assertOk();

        $this->actingAs($student)->get(route('student.assignments.index'))->assertOk();
        $this->actingAs($student)->get(route('student.assignments.submit', $assignment))->assertOk();
        $this->actingAs($student)->get(route('student.submissions.index'))->assertOk();
    }
}
