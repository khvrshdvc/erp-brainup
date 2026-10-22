<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_defaults_to_first_branch_and_can_pick_all(): void
    {
        $first = Branch::create(['name' => 'A']);
        Branch::create(['name' => 'B']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame($first->id, app(CurrentBranch::class)->id());

        $this->post(route('branch.switch'), ['branch_id' => 'all'])->assertRedirect();
        $this->assertNull(app(CurrentBranch::class)->id());
    }

    public function test_super_admin_can_toggle_branch_status(): void
    {
        $branch = Branch::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->patch(route('admin.branches.status', $branch))->assertRedirect();

        $this->assertFalse($branch->fresh()->is_active);
    }

    public function test_admin_can_toggle_group_status(): void
    {
        $branch = Branch::create(['name' => 'A']);
        $admin = User::factory()->branchAdmin()->inBranch($branch)->create();
        $group = \App\Models\StudyGroup::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($admin)->patch(route('admin.groups.status', $group))->assertRedirect();
        $this->assertSame(\App\Enums\GroupStatus::Archived, $group->fresh()->status);

        $this->patch(route('admin.groups.status', $group));
        $this->assertSame(\App\Enums\GroupStatus::Active, $group->fresh()->status);
    }
}
