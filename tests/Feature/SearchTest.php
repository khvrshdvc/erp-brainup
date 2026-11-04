<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_finds_students_in_their_branch_only(): void
    {
        $branch = Branch::create(['name' => 'A']);
        $other = Branch::create(['name' => 'B']);

        $admin = User::factory()->branchAdmin()->inBranch($branch)->create();
        User::factory()->student()->inBranch($branch)->create(['name' => 'Aziz Karimov']);
        User::factory()->student()->inBranch($other)->create(['name' => 'Aziz Boshqa']);

        $this->actingAs($admin)->get(route('search', ['q' => 'Aziz']))
            ->assertOk()
            ->assertSee('Aziz Karimov')
            ->assertDontSee('Aziz Boshqa');
    }

    public function test_search_treats_wildcards_literally_and_needs_two_characters(): void
    {
        $branch = Branch::create(['name' => 'A']);
        $admin = User::factory()->branchAdmin()->inBranch($branch)->create();
        User::factory()->student()->inBranch($branch)->create(['name' => 'Aziz Karimov']);

        $this->actingAs($admin)->get(route('search', ['q' => '%']))->assertOk()->assertDontSee('Aziz Karimov');
        $this->actingAs($admin)->get(route('search', ['q' => 'A']))->assertOk()->assertDontSee('Aziz Karimov');
        $this->actingAs($admin)->get(route('search', ['q' => 'Az']))->assertOk()->assertSee('Aziz Karimov');
    }

    public function test_teacher_finds_only_students_of_own_groups(): void
    {
        $branch = Branch::create(['name' => 'A']);

        $teacher = User::factory()->teacher()->create();
        $teacher->branches()->attach($branch->id);
        $otherTeacher = User::factory()->teacher()->create();
        $otherTeacher->branches()->attach($branch->id);

        $mine = User::factory()->student()->inBranch($branch)->create(['name' => 'Aziz Karimov']);
        $foreign = User::factory()->student()->inBranch($branch)->create(['name' => 'Aziz Boshqa']);

        StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'English-1'])
            ->students()->attach($mine->id);
        StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $otherTeacher->id, 'name' => 'IT-1'])
            ->students()->attach($foreign->id);

        $this->actingAs($teacher)->get(route('search', ['q' => 'Aziz']))
            ->assertOk()
            ->assertSee('Aziz Karimov')
            ->assertSee('English-1')
            ->assertDontSee('Aziz Boshqa')
            ->assertDontSee(route('admin.students.show', $mine), false);
    }

    public function test_students_cannot_search(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('search', ['q' => 'a']))->assertForbidden();
    }
}
