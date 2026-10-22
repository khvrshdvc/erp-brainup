<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_the_list_and_students_cannot(): void
    {
        $branch = Branch::create(['name' => 'A']);

        $admin = User::factory()->branchAdmin()->inBranch($branch)->create();
        $teacher = User::factory()->teacher()->inBranch($branch)->create();
        $teacher->branches()->attach($branch->id);
        $student = User::factory()->student()->inBranch($branch)->create();

        $this->actingAs($admin)->get(route('submissions.index'))->assertOk()->assertSee('Topshirganlar');
        $this->actingAs($teacher)->get(route('submissions.index'))->assertOk();
        $this->actingAs($student)->get(route('submissions.index'))->assertForbidden();
    }
}
