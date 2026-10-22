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
        ])->assertSessionHasErrors(['file' => 'Vazifa faylini biriktiring.']);

        $this->actingAs($student)->post(route('student.assignments.submit.store', $assignment), [
            'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors(['file' => 'Faqat PDF, DOC, DOCX, JPG yoki PNG fayl yuklash mumkin.']);
    }
}
