<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsValidation;
use Tests\TestCase;

class BranchFormTest extends TestCase
{
    use AssertsValidation;
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
    }

    public function test_the_form_shows_every_field_with_the_right_limits(): void
    {
        $this->actingAs($this->superAdmin)->get(route('admin.branches.create'))
            ->assertOk()
            ->assertSee('Yangi filial')
            ->assertSee('action="'.route('admin.branches.store').'"', false)
            ->assertSee('name="name" maxlength="120" required', false)
            ->assertSee('name="manager_name" maxlength="120"', false)
            ->assertSee('name="address" maxlength="255"', false)
            ->assertSee('name="is_active" value="1" checked', false)
            ->assertDontSee('name="_method"', false);
    }

    public function test_a_branch_is_created_with_all_fields(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), [
            'name' => 'Chirchiq filiali',
            'manager_name' => 'Karimov Sherzod',
            'address' => 'Chirchiq, Navoiy ko‘chasi 5',
            'is_active' => '1',
        ])->assertRedirect(route('admin.branches.index'))->assertSessionHas('status', 'Filial qo‘shildi');

        $this->assertDatabaseHas('branches', [
            'name' => 'Chirchiq filiali',
            'manager_name' => 'Karimov Sherzod',
            'address' => 'Chirchiq, Navoiy ko‘chasi 5',
            'is_active' => true,
        ]);
    }

    public function test_only_the_name_is_required_and_empty_optionals_are_stored_as_null(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), [
            'name' => 'Faqat nom', 'manager_name' => '', 'address' => '',
        ])->assertRedirect(route('admin.branches.index'));

        $branch = Branch::where('name', 'Faqat nom')->firstOrFail();
        $this->assertNull($branch->manager_name);
        $this->assertNull($branch->address);
    }

    public function test_an_unticked_active_box_creates_an_inactive_branch(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), ['name' => 'Yopiq filial']);

        $this->assertFalse(Branch::where('name', 'Yopiq filial')->firstOrFail()->is_active);
    }

    public function test_the_name_is_trimmed_and_blank_names_are_refused(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), ['name' => "   Yunusobod filiali \t "]);

        $this->assertDatabaseHas('branches', ['name' => 'Yunusobod filiali']);

        $this->assertInvalid($this->superAdmin, 'post', route('admin.branches.store'), route('admin.branches.create'), ['name' => '    '], ['name']);
    }

    public function test_the_name_must_be_unique_whatever_its_case(): void
    {
        Branch::create(['name' => 'Chirchiq filiali']);

        $errors = $this->assertInvalid($this->superAdmin, 'post', route('admin.branches.store'), route('admin.branches.create'), ['name' => 'CHIRCHIQ FILIALI'], ['name']);

        $this->assertSame(['Bunday filial nomi allaqachon mavjud.'], $errors['name']);
        $this->assertSame(1, Branch::count());
    }

    public function test_field_errors_sit_next_to_their_fields_and_the_input_is_kept(): void
    {
        $this->actingAs($this->superAdmin)->followingRedirects()->from(route('admin.branches.create'))
            ->post(route('admin.branches.store'), [
                'name' => '',
                'manager_name' => str_repeat('m', 121),
                'address' => 'Toshkent',
            ])
            ->assertSee('class="input is-invalid"', false)
            ->assertSee('aria-describedby="name-error"', false)
            ->assertSee('id="name-error"', false)
            ->assertSee('id="manager_name-error"', false)
            ->assertDontSee('id="address-error"', false)
            ->assertSee('value="Toshkent"', false);
    }

    public function test_an_unticked_box_stays_unticked_after_a_failed_save(): void
    {
        // The box was unticked: the request has no is_active, but other old input exists.
        $page = $this->actingAs($this->superAdmin)->followingRedirects()->from(route('admin.branches.create'))
            ->post(route('admin.branches.store'), ['name' => '']);

        $page->assertSee('name="is_active" value="1"', false)->assertDontSee('name="is_active" value="1" checked', false);

        $page = $this->actingAs($this->superAdmin)->followingRedirects()->from(route('admin.branches.create'))
            ->post(route('admin.branches.store'), ['name' => '', 'is_active' => '1']);

        $page->assertSee('name="is_active" value="1" checked', false);
    }

    public function test_the_new_branch_appears_in_the_list_and_the_branch_switcher(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), ['name' => 'Yangi filial nomi', 'is_active' => '1']);

        $this->actingAs($this->superAdmin)->get(route('admin.branches.index'))->assertOk()->assertSee('Yangi filial nomi');
        $this->actingAs($this->superAdmin)->get(route('admin.settings.edit'))->assertSee('name="branch_id" value="'.Branch::where('name', 'Yangi filial nomi')->value('id').'"', false);
    }

    public function test_html_in_the_name_is_shown_as_text(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), ['name' => '<script>alert(1)</script>']);

        $this->actingAs($this->superAdmin)->get(route('admin.branches.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_extra_fields_cannot_be_smuggled_in(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.branches.store'), ['name' => 'Toza', 'id' => 777, 'created_at' => '2000-01-01']);

        $branch = Branch::where('name', 'Toza')->firstOrFail();
        $this->assertNotSame(777, $branch->id);
        $this->assertTrue($branch->created_at->isToday());
    }

    public function test_editing_keeps_the_same_name_and_prefills_the_form(): void
    {
        $branch = Branch::create(['name' => 'Eski nom', 'manager_name' => 'Menejer', 'address' => 'Manzil', 'is_active' => false]);

        $this->actingAs($this->superAdmin)->get(route('admin.branches.edit', $branch))
            ->assertOk()
            ->assertSee('value="Eski nom"', false)
            ->assertSee('value="Menejer"', false)
            ->assertSee('value="Manzil"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertDontSee('name="is_active" value="1" checked', false);

        $this->actingAs($this->superAdmin)->put(route('admin.branches.update', $branch), ['name' => 'Eski nom', 'manager_name' => 'Yangi menejer', 'address' => '', 'is_active' => '1'])
            ->assertRedirect(route('admin.branches.index'));

        $branch->refresh();
        $this->assertSame('Yangi menejer', $branch->manager_name);
        $this->assertNull($branch->address);
        $this->assertTrue($branch->is_active);
    }

    public function test_only_the_super_admin_reaches_the_form(): void
    {
        $branch = Branch::create(['name' => 'Mavjud']);
        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        foreach ([$admin, $teacher] as $user) {
            $this->actingAs($user)->get(route('admin.branches.create'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.branches.store'), ['name' => 'Hujum'])->assertForbidden();
        }

        auth()->logout();
        $this->get(route('admin.branches.create'))->assertRedirect(route('login'));
        $this->post(route('admin.branches.store'), ['name' => 'Hujum'])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('branches', ['name' => 'Hujum']);
    }

    public function test_the_form_speaks_english_and_russian(): void
    {
        $this->superAdmin->forceFill(['locale' => 'en'])->save();
        $this->actingAs($this->superAdmin->fresh())->get(route('admin.branches.create'))
            ->assertSee('New branch')->assertSee('Branch name *')->assertSee('Manager')->assertSee('Address')->assertSee('Branch is active');

        $this->superAdmin->forceFill(['locale' => 'ru'])->save();
        $this->actingAs($this->superAdmin->fresh())->get(route('admin.branches.create'))
            ->assertSee('Новый филиал')->assertSee('Название филиала *')->assertSee('Менеджер')->assertSee('Адрес')->assertSee('Филиал активен');
    }

    public function test_a_deactivated_branch_signs_out_its_people_and_drops_out_of_shared_teachers(): void
    {
        $closed = Branch::create(['name' => 'Yopilgan', 'is_active' => false]);
        $open = Branch::create(['name' => 'Ochiq']);

        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $closed->id]);
        $admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $closed->id]);

        foreach ([$student, $admin] as $user) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }

        $shared = User::factory()->create(['role' => Role::Teacher]);
        $shared->branches()->attach([$closed->id, $open->id]);

        $this->actingAs($shared)->get(route('teacher.dashboard'))->assertOk();
        $this->assertSame([$open->id], $shared->availableBranchIds());

        // The super admin still reaches the deactivated branch to manage it.
        $this->assertContains($closed->id, $this->superAdmin->availableBranchIds());
    }
}
