<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsStaffTest extends TestCase
{
    use RefreshDatabase;

    private Branch $first;

    private Branch $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->first = Branch::create(['name' => 'Birinchi filial']);
        $this->second = Branch::create(['name' => 'Ikkinchi filial']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => Role::SuperAdmin]);
    }

    private function branchAdmin(Branch $branch, string $name = 'Filial Admini'): User
    {
        return User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id, 'name' => $name]);
    }

    private function teacher(string $name, Branch ...$branches): User
    {
        $teacher = User::factory()->create(['role' => Role::Teacher, 'name' => $name]);
        $teacher->branches()->attach(collect($branches)->pluck('id')->all());

        return $teacher;
    }

    public function test_super_admin_sees_the_branch_admins_and_teachers_of_the_default_branch(): void
    {
        $this->branchAdmin($this->first, 'Birinchi Admin');
        $this->branchAdmin($this->second, 'Ikkinchi Admin');
        $this->teacher('Birinchi Ustoz', $this->first);

        $this->actingAs($this->superAdmin())->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Filial adminlari')
            ->assertSee('Filial o‘qituvchilari')
            ->assertSee('Birinchi Admin')
            ->assertSee('Birinchi Ustoz');
    }

    public function test_the_lists_follow_the_selected_branch(): void
    {
        $this->branchAdmin($this->first, 'Birinchi Admin');
        $this->branchAdmin($this->second, 'Ikkinchi Admin');
        $this->teacher('Birinchi Ustoz', $this->first);
        $this->teacher('Ikkinchi Ustoz', $this->second);

        $this->actingAs($this->superAdmin())->withSession(['branch_id' => $this->second->id])
            ->get(route('admin.settings.edit'))
            ->assertSee('Ikkinchi Admin')
            ->assertSee('Ikkinchi Ustoz')
            ->assertDontSee('Birinchi Admin')
            ->assertDontSee('Birinchi Ustoz');
    }

    public function test_all_branches_shows_nobody_until_a_branch_is_chosen(): void
    {
        $this->branchAdmin($this->first, 'Birinchi Admin');
        $this->teacher('Birinchi Ustoz', $this->first);

        $this->actingAs($this->superAdmin())->withSession(['branch_id' => 0])
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('yuqoridan bitta filialni tanlang')
            ->assertDontSee('Birinchi Admin')
            ->assertDontSee('Birinchi Ustoz')
            ->assertDontSee('Filial adminlari</h2>', false);

        $this->actingAs($this->superAdmin())->withSession(['branch_id' => $this->first->id])
            ->get(route('admin.settings.edit'))
            ->assertSee('Birinchi Admin')
            ->assertSee('Birinchi Ustoz')
            ->assertDontSee('yuqoridan bitta filialni tanlang');
    }

    public function test_super_admin_edits_a_branch_admin(): void
    {
        $admin = $this->branchAdmin($this->first);

        $this->actingAs($this->superAdmin())->put(route('admin.settings.staff', $admin), [
            'name' => 'Yangi Ism',
            'phone' => '+998 90 111 22 33',
            'email' => 'yangi@brainup.uz',
            'password' => 'yangi-parol-1',
        ])->assertRedirect(route('admin.settings.edit').'#staff-'.$admin->id)
            ->assertSessionHas('status', 'Ma‘lumotlar saqlandi');

        $admin->refresh();
        $this->assertSame('Yangi Ism', $admin->name);
        $this->assertSame('+998 90 111 22 33', $admin->phone);
        $this->assertSame('yangi@brainup.uz', $admin->email);
        $this->assertTrue(Hash::check('yangi-parol-1', $admin->password));
        $this->assertTrue($admin->must_change_password);
        $this->assertSame(Role::BranchAdmin, $admin->role);
        $this->assertSame($this->first->id, $admin->branch_id);
    }

    public function test_blank_password_keeps_the_old_one(): void
    {
        $admin = $this->branchAdmin($this->first);
        $hash = $admin->password;

        $this->actingAs($this->superAdmin())->put(route('admin.settings.staff', $admin), [
            'name' => 'Boshqa Ism',
            'email' => $admin->email,
            'password' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertFalse($admin->fresh()->must_change_password);
    }

    public function test_branch_admin_cannot_edit_branch_admins_and_does_not_see_them(): void
    {
        $me = $this->branchAdmin($this->first, 'Men Adminman');
        $other = $this->branchAdmin($this->first, 'Hamkasb Admin');

        $this->actingAs($me)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertDontSee('Filial adminlari')
            ->assertDontSee('Hamkasb Admin');

        $this->actingAs($me)->put(route('admin.settings.staff', $other), [
            'name' => 'Hujum', 'email' => 'hujum@test.uz',
        ])->assertNotFound();

        $this->assertNotSame('Hujum', $other->fresh()->name);
    }

    public function test_branch_admin_edits_a_teacher_of_their_branch(): void
    {
        $teacher = $this->teacher('Eski Ustoz', $this->first);

        $this->actingAs($this->branchAdmin($this->first))->get(route('admin.settings.edit'))
            ->assertSee('Eski Ustoz')
            ->assertSee('id="staff-'.$teacher->id.'-email"', false)
            ->assertSee('id="staff-'.$teacher->id.'-password"', false);

        $this->actingAs($this->branchAdmin($this->first))->put(route('admin.settings.staff', $teacher), [
            'name' => 'Yangi Ustoz',
            'phone' => '+998 93 000 11 22',
            'email' => 'ustoz@brainup.uz',
            'password' => 'ustoz-parol-1',
        ])->assertRedirect();

        $teacher->refresh();
        $this->assertSame('Yangi Ustoz', $teacher->name);
        $this->assertSame('ustoz@brainup.uz', $teacher->email);
        $this->assertTrue(Hash::check('ustoz-parol-1', $teacher->password));
        $this->assertTrue($teacher->must_change_password);
        $this->assertSame([$this->first->id], $teacher->branches()->pluck('branches.id')->all());
    }

    public function test_branch_admin_cannot_edit_a_teacher_of_another_branch(): void
    {
        $teacher = $this->teacher('Begona Ustoz', $this->second);

        $this->actingAs($this->branchAdmin($this->first))->put(route('admin.settings.staff', $teacher), [
            'name' => 'Hujum', 'email' => 'hujum@test.uz',
        ])->assertNotFound();

        $this->assertSame('Begona Ustoz', $teacher->fresh()->name);
    }

    public function test_shared_teacher_credentials_are_protected_from_a_branch_admin(): void
    {
        $teacher = $this->teacher('Umumiy Ustoz', $this->first, $this->second);
        $email = $teacher->email;
        $admin = $this->branchAdmin($this->first);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertSee('Umumiy Ustoz')
            ->assertDontSee('id="staff-'.$teacher->id.'-email"', false)
            ->assertDontSee('id="staff-'.$teacher->id.'-password"', false)
            ->assertSee('id="staff-'.$teacher->id.'-name"', false)
            ->assertSee('faqat super admin o‘zgartira oladi');

        $this->actingAs($admin)->put(route('admin.settings.staff', $teacher), [
            'name' => 'Yangi Nom', 'email' => 'hijack@test.uz', 'password' => 'hijack-parol-1',
        ])->assertRedirect(route('admin.settings.edit').'#staff-'.$teacher->id)
            ->assertSessionHasErrorsIn('staff_'.$teacher->id, ['email', 'password']);

        $this->assertSame($email, $teacher->fresh()->email);
        $this->assertSame('Umumiy Ustoz', $teacher->fresh()->name);

        // Name and phone can still be changed.
        $this->actingAs($admin)->put(route('admin.settings.staff', $teacher), ['name' => 'Yangi Nom'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Yangi Nom', $teacher->fresh()->name);
        $this->assertSame($email, $teacher->fresh()->email);
    }

    public function test_super_admin_can_change_shared_teacher_credentials(): void
    {
        $teacher = $this->teacher('Umumiy Ustoz', $this->first, $this->second);

        $this->actingAs($this->superAdmin())->put(route('admin.settings.staff', $teacher), [
            'name' => 'Umumiy Ustoz', 'email' => 'super@brainup.uz',
        ])->assertSessionHasNoErrors();

        $this->assertSame('super@brainup.uz', $teacher->fresh()->email);
    }

    public function test_validation_errors_are_shown_next_to_the_right_person(): void
    {
        $taken = $this->branchAdmin($this->first, 'Band Email');
        $teacher = $this->teacher('Xato Ustoz', $this->first);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('admin.settings.staff', $teacher), [
            'name' => '', 'email' => $taken->email,
        ])->assertRedirect(route('admin.settings.edit').'#staff-'.$teacher->id)
            ->assertSessionHasErrorsIn('staff_'.$teacher->id, ['name', 'email']);

        $this->actingAs($admin)->followingRedirects()->from(route('admin.settings.edit'))
            ->put(route('admin.settings.staff', $teacher), ['name' => '', 'email' => $taken->email])
            ->assertSee('<details id="staff-'.$teacher->id.'" class="card" style="padding: 12px 16px" open>', false);
    }

    public function test_students_and_non_staff_cannot_be_edited_through_this_route(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->first->id]);

        $this->actingAs($this->superAdmin())->put(route('admin.settings.staff', $student), [
            'name' => 'Hujum', 'email' => 'x@test.uz',
        ])->assertNotFound();

        $this->actingAs($this->teacher('Ustoz', $this->first))->put(route('admin.settings.staff', $student), [
            'name' => 'Hujum', 'email' => 'x@test.uz',
        ])->assertForbidden();
    }
}
