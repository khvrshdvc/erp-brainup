<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Tests\TestCase;

class PulseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Livewire keeps this in a static; a real request starts a new process, tests share one.
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;

        parent::tearDown();
    }

    public function test_only_the_configured_email_can_open_the_dashboard(): void
    {
        $this->get('/pulse')->assertForbidden();

        $branch = Branch::create(['name' => 'Test filial']);

        foreach ([Role::SuperAdmin, Role::BranchAdmin, Role::Student] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'branch_id' => $branch->id]))
                ->get('/pulse')->assertForbidden();
        }

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);
        $this->actingAs($teacher)->get('/pulse')->assertForbidden();

        $owner = User::factory()->create(['role' => Role::Teacher, 'email' => 'UTKIRBEK@brainup.uz']);
        $owner->branches()->attach($branch->id);
        $this->actingAs($owner)->get('/pulse')->assertOk();
    }

    public function test_user_cards_do_not_send_emails_to_gravatar(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'name' => 'Ali Valiyev']);

        $card = app(ResolvesUsers::class)->load(collect([$student->id]))->find($student->id);

        $this->assertSame('Ali Valiyev', $card->name);
        $this->assertFalse(isset($card->avatar));
    }

    public function test_pages_of_the_app_do_not_load_livewire(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('livewire', false);
    }
}
