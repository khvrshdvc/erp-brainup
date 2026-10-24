<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UzbekValidationTest extends TestCase
{
    use RefreshDatabase;

    private function setupGroup(): array
    {
        $branch = Branch::create(['name' => 'Test filial']);

        $teacher = User::factory()->teacher()->inBranch($branch)->create();
        $teacher->branches()->attach($branch->id);

        $student = User::factory()->student()->inBranch($branch)->create();

        $group = StudyGroup::factory()->create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'name' => 'Test guruh',
        ]);
        $group->students()->attach($student->id);

        return [$teacher, $student, $group];
    }

    public function test_assignment_form_errors_are_in_uzbek(): void
    {
        [$teacher, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'title' => '',
            'study_group_id' => $group->id,
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'action' => 'publish',
        ])->assertSessionHasErrors([
            'title' => 'Vazifa nomi kiritilishi shart.',
            'due_at' => 'Topshirish muddati hozirgi vaqtdan keyin bo‘lishi kerak.',
        ]);
    }

    public function test_submission_requires_a_file_with_an_uzbek_message(): void
    {
        [$teacher, $student, $group] = $this->setupGroup();

        $assignment = Assignment::create([
            'branch_id' => $group->branch_id,
            'study_group_id' => $group->id,
            'teacher_id' => $teacher->id,
            'title' => 'Uy vazifasi',
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_at' => now()->addDay(),
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($student)->get(route('student.assignments.submit', $assignment))
            ->assertOk()
            ->assertSee('qoldi');

        $this->actingAs($student)->post(route('student.assignments.submit.store', $assignment), [
            'body' => 'Izoh',
        ])->assertSessionHasErrors(['files' => 'Vazifa faylini biriktiring.']);

        $this->actingAs($student)->post(route('student.assignments.submit.store', $assignment), [
            'files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ])->assertSessionHasErrors(['files.0' => 'Faqat PDF, DOC, DOCX, JPG yoki PNG fayl yuklash mumkin.']);
    }

    public function test_deadline_is_sent_as_date_and_24_hour_time(): void
    {
        [$teacher, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->get(route('teacher.assignments.create'))
            ->assertOk()
            ->assertSee('<option value="23:45"', false)
            ->assertDontSee('type="datetime-local"', false);

        $date = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'title' => 'Uy vazifasi',
            'study_group_id' => $group->id,
            'description' => 'Tavsif',
            'max_score' => 100,
            'due_date' => $date,
            'due_time' => '21:30',
            'action' => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertSame($date.' 21:30', Assignment::firstOrFail()->due_at->format('Y-m-d H:i'));
    }

    public function test_app_runs_on_tashkent_time(): void
    {
        $this->assertSame('Asia/Tashkent', config('app.timezone'));
        $this->assertSame('+05:00', now()->format('P'));
    }

    public function test_date_fields_use_the_uzbek_calendar(): void
    {
        [$teacher, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->get(route('teacher.assignments.create'))
            ->assertOk()
            ->assertSee('class="datepicker"', false)
            ->assertSee('name="due_date"', false)
            ->assertDontSee('type="date"', false);

        $this->actingAs($teacher)->get(route('attendance.create', [$group, 'date' => '2026-10-05']))
            ->assertOk()
            ->assertSee('05.10.2026')
            ->assertSee('value="2026-10-05"', false);
    }
}
