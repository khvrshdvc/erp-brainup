<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFileRetentionTest extends TestCase
{
    use RefreshDatabase;

    private const EXPIRED_NOTE = 'Fayl saqlash muddati tugagani uchun o‘chirilgan';

    private Branch $branch;

    private User $teacher;

    private StudyGroup $group;

    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->branch = Branch::create(['name' => 'Test filial']);
        $this->teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->teacher->branches()->attach($this->branch->id);
        $this->group = StudyGroup::create(['branch_id' => $this->branch->id, 'teacher_id' => $this->teacher->id, 'name' => 'Guruh']);
        $this->assignment = Assignment::create([
            'branch_id' => $this->branch->id,
            'study_group_id' => $this->group->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Essay',
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_at' => now()->addDay(),
            'status' => AssignmentStatus::Published,
            'published_at' => now()->subDays(300),
        ]);
    }

    private function student(): User
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id]);
        $this->group->students()->attach($student->id);

        return $student;
    }

    private function submission(User $student, SubmissionStatus $status, ?int $gradedDaysAgo, bool $withFile = true, int $size = 1048576): Submission
    {
        $submission = Submission::create([
            'branch_id' => $this->branch->id,
            'assignment_id' => $this->assignment->id,
            'student_id' => $student->id,
            'status' => $status,
            'is_late' => false,
            'submitted_at' => now()->subDays(400),
            'score' => $status === SubmissionStatus::Graded ? 85 : null,
            'feedback' => $status === SubmissionStatus::Graded ? 'Yaxshi ish' : null,
            'graded_by' => $this->teacher->id,
            'graded_at' => $gradedDaysAgo === null ? null : now()->subDays($gradedDaysAgo),
        ]);

        if ($withFile) {
            $path = 'submissions/'.$submission->id.'.pdf';
            Storage::disk('local')->put($path, 'pdf');

            $submission->attachments()->create([
                'uploaded_by' => $student->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => 'essay.pdf',
                'mime_type' => 'application/pdf',
                'size' => $size,
            ]);
        }

        return $submission;
    }

    public function test_retention_defaults_to_180_days(): void
    {
        $this->assertSame(180, Setting::submissionFileRetentionDays());

        Setting::put('submission_file_retention_days', 30);
        $this->assertSame(30, Setting::submissionFileRetentionDays());
    }

    public function test_old_graded_files_are_deleted_but_the_submission_is_kept(): void
    {
        $old = $this->submission($this->student(), SubmissionStatus::Graded, 181);
        $recent = $this->submission($this->student(), SubmissionStatus::Graded, 179);
        $pending = $this->submission($this->student(), SubmissionStatus::Submitted, null);
        $returned = $this->submission($this->student(), SubmissionStatus::Returned, 400);

        $oldPath = $old->attachments->first()->path;

        $this->artisan('submissions:prune-files')
            ->expectsOutput('O‘chirildi: 1 ta fayl, 1.00 MB bo‘shatildi')
            ->assertSuccessful();

        $this->assertSame(0, $old->attachments()->count());
        Storage::disk('local')->assertMissing($oldPath);

        $this->assertSame(1, $recent->attachments()->count());
        $this->assertSame(1, $pending->attachments()->count());
        $this->assertSame(1, $returned->attachments()->count());

        $old->refresh();
        $this->assertSame(SubmissionStatus::Graded, $old->status);
        $this->assertSame(85, $old->score);
        $this->assertSame('Yaxshi ish', $old->feedback);
    }

    public function test_zero_keeps_files_forever(): void
    {
        Setting::put('submission_file_retention_days', 0);
        $submission = $this->submission($this->student(), SubmissionStatus::Graded, 2000);

        $this->artisan('submissions:prune-files')
            ->expectsOutput('Fayllarni saqlash muddati cheklanmagan (0), hech narsa o‘chirilmadi.')
            ->assertSuccessful();

        $this->assertSame(1, $submission->attachments()->count());
    }

    public function test_the_period_follows_the_setting(): void
    {
        Setting::put('submission_file_retention_days', 30);

        $old = $this->submission($this->student(), SubmissionStatus::Graded, 31);
        $recent = $this->submission($this->student(), SubmissionStatus::Graded, 29);

        $this->artisan('submissions:prune-files')->assertSuccessful();

        $this->assertSame(0, $old->attachments()->count());
        $this->assertSame(1, $recent->attachments()->count());
    }

    public function test_it_processes_more_than_one_chunk_and_reports_totals(): void
    {
        $student = $this->student();
        $submission = $this->submission($student, SubmissionStatus::Graded, 365, withFile: false);

        $now = now()->toDateTimeString();
        DB::table('attachments')->insert(collect(range(1, 450))->map(fn (int $i) => [
            'attachable_type' => Submission::class,
            'attachable_id' => $submission->id,
            'uploaded_by' => $student->id,
            'disk' => 'local',
            'path' => "submissions/missing-{$i}.pdf",
            'original_name' => "file-{$i}.pdf",
            'mime_type' => 'application/pdf',
            'size' => 524288,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        $this->artisan('submissions:prune-files')
            ->expectsOutput('O‘chirildi: 450 ta fayl, 225.00 MB bo‘shatildi')
            ->assertSuccessful();

        $this->assertSame(0, Attachment::count());
    }

    public function test_it_does_not_touch_assignment_materials(): void
    {
        $material = $this->assignment->attachments()->create([
            'uploaded_by' => $this->teacher->id,
            'disk' => 'local',
            'path' => 'assignments/material.pdf',
            'original_name' => 'material.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $this->artisan('submissions:prune-files')->assertSuccessful();

        $this->assertTrue($material->fresh()->exists);
    }

    public function test_pruning_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('submissions:prune-files')
            ->assertSuccessful();
    }

    public function test_student_and_teacher_see_why_the_file_is_gone(): void
    {
        $student = $this->student();
        $submission = $this->submission($student, SubmissionStatus::Graded, 200);

        $this->actingAs($student)->get(route('student.assignments.show', $this->assignment))
            ->assertOk()
            ->assertSee('essay.pdf')
            ->assertDontSee(self::EXPIRED_NOTE);

        $this->artisan('submissions:prune-files')->assertSuccessful();

        $this->actingAs($student)->get(route('student.assignments.show', $this->assignment))
            ->assertOk()
            ->assertSee(self::EXPIRED_NOTE)
            ->assertSee('Yaxshi ish')
            ->assertSee('85 / 100');

        $this->actingAs($student)->get(route('student.submissions.index'))
            ->assertOk()
            ->assertSee(self::EXPIRED_NOTE);

        $this->actingAs($this->teacher)->get(route('teacher.assignments.show', [$this->assignment, 'student' => $student->id]))
            ->assertOk()
            ->assertSee(self::EXPIRED_NOTE)
            ->assertDontSee('Fayl yo‘q');
    }

    public function test_super_admin_edits_the_retention_period_in_settings(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('name="submission_file_retention_days"', false)
            ->assertSee('value="180"', false);

        $this->actingAs($admin)->put(route('admin.settings.center'), ['center_name' => 'Brainup', 'submission_file_retention_days' => 90])
            ->assertRedirect();
        $this->assertSame(90, Setting::submissionFileRetentionDays());

        $this->actingAs($admin)->put(route('admin.settings.center'), ['center_name' => 'Brainup', 'submission_file_retention_days' => 0])
            ->assertSessionHasNoErrors();
        $this->assertSame(0, Setting::submissionFileRetentionDays());

        $this->actingAs($admin)->put(route('admin.settings.center'), ['center_name' => 'Brainup', 'submission_file_retention_days' => -5])
            ->assertSessionHasErrors('submission_file_retention_days');

        $branchAdmin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $this->branch->id]);
        $this->actingAs($branchAdmin)->put(route('admin.settings.center'), ['center_name' => 'Brainup', 'submission_file_retention_days' => 1])
            ->assertForbidden();
    }
}
