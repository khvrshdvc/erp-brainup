<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private Branch $chirchiq;

    private Branch $yunusobod;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chirchiq = Branch::create(['name' => 'Chirchiq filiali']);
        $this->yunusobod = Branch::create(['name' => 'Yunusobod filiali']);
        $this->superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
    }

    public function test_the_switcher_is_a_custom_list_not_a_native_select(): void
    {
        $page = $this->actingAs($this->superAdmin)->withSession(['branch_id' => $this->chirchiq->id])->get(route('admin.dashboard'))->assertOk();

        $page->assertSee('class="btn branch-btn"', false)
            ->assertSee('role="listbox"', false)
            ->assertDontSee('<select name="branch_id"', false);

        $this->assertSame(0, preg_match('/<select[^>]*name="branch_id"/', $page->getContent()));
    }

    public function test_it_shows_the_current_branch_and_marks_it_in_the_list(): void
    {
        $page = $this->actingAs($this->superAdmin)->withSession(['branch_id' => $this->chirchiq->id])->get(route('admin.dashboard'));

        $page->assertSee('aria-label="Filial: Chirchiq filiali"', false);
        $page->assertSee('name="branch_id" value="'.$this->chirchiq->id.'" role="option" aria-selected="true"', false);
        $page->assertSee('name="branch_id" value="'.$this->yunusobod->id.'" role="option" aria-selected="false"', false);
        $page->assertSee('name="branch_id" value="all" role="option" aria-selected="false"', false);
    }

    public function test_all_branches_is_a_row_and_the_label_when_chosen(): void
    {
        $page = $this->actingAs($this->superAdmin)->withSession(['branch_id' => 0])->get(route('admin.dashboard'));

        $page->assertSee('aria-label="Filial: Barcha filiallar"', false);
        $page->assertSee('name="branch_id" value="all" role="option" aria-selected="true"', false);
    }

    public function test_choosing_a_row_switches_the_branch(): void
    {
        $this->actingAs($this->superAdmin)->from(route('admin.dashboard'))
            ->post(route('branch.switch'), ['branch_id' => (string) $this->yunusobod->id])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->superAdmin)->withSession(['branch_id' => $this->yunusobod->id])->get(route('admin.dashboard'))
            ->assertSee('aria-label="Filial: Yunusobod filiali"', false);
    }

    public function test_a_branch_admin_with_one_branch_gets_no_switcher(): void
    {
        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $this->chirchiq->id]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('branch-btn', false);
    }

    public function test_a_teacher_in_two_branches_switches_between_them_without_an_all_row(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach([$this->chirchiq->id, $this->yunusobod->id]);

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertSee('branch-btn', false)
            ->assertSee('name="branch_id" value="'.$this->chirchiq->id.'"', false)
            ->assertDontSee('value="all"', false);
    }

    public function test_the_list_text_is_readable_in_both_themes(): void
    {
        $css = file_get_contents(public_path('css/erp.css'));

        // The rows use theme variables on the card background, so they follow light and dark alike.
        $this->assertStringContainsString('.branch-menu button { color: var(--ink); }', $css);
        $this->assertStringContainsString('.lang-menu {', $css);
        $this->assertMatchesRegularExpression('/\.lang-menu \{[^}]*background|\.card \{ background: var\(--surface\)/', $css);
    }

    public function test_the_branch_names_are_escaped(): void
    {
        Branch::create(['name' => '<b>Qalin</b>']);

        $this->actingAs($this->superAdmin)->get(route('admin.dashboard'))
            ->assertDontSee('<b>Qalin</b>', false)
            ->assertSee('&lt;b&gt;Qalin&lt;/b&gt;', false);
    }
}
