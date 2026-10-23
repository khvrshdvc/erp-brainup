<?php

namespace Tests\Feature;

use App\Models\Branch;
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

    public function test_students_cannot_search(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('search', ['q' => 'a']))->assertForbidden();
    }
}
