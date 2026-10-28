<?php

namespace Tests\Feature;

use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function branches(): array
    {
        return [Branch::create(['name' => 'Birinchi']), Branch::create(['name' => 'Ikkinchi'])];
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => Role::SuperAdmin]);
    }

    private function branchAdmin(Branch $branch): User
    {
        return User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $branch->id]);
    }

    private function student(Branch $branch): User
    {
        return User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);
    }

    private function teacher(Branch ...$branches): User
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach(collect($branches)->pluck('id')->all());

        return $teacher;
    }

    public function test_super_admin_creates_and_updates_branch(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.branches.store'), [
            'name' => 'Yangi filial',
            'address' => 'Toshkent',
            'is_active' => 1,
        ])->assertRedirect(route('admin.branches.index'));

        $branch = Branch::where('name', 'Yangi filial')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
            'name' => 'Nomi o‘zgardi',
        ])->assertRedirect();

        $this->assertSame('Nomi o‘zgardi', $branch->fresh()->name);
        $this->assertFalse($branch->fresh()->is_active);
    }

    public function test_branch_admin_cannot_manage_branches(): void
    {
        [$first] = $this->branches();

        $this->actingAs($this->branchAdmin($first))->get(route('admin.branches.index'))->assertForbidden();
    }

    public function test_branch_admin_creates_student_only_in_own_branch(): void
    {
        [$first, $second] = $this->branches();
        $admin = $this->branchAdmin($first);

        $payload = fn (int $branchId, string $email) => [
            'name' => 'Yangi O‘quvchi',
            'email' => $email,
            'password' => 'password123',
            'branch_id' => $branchId,
        ];

        $this->actingAs($admin)->post(route('admin.students.store'), $payload($first->id, 'a@test.uz'))->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'a@test.uz',
            'role' => Role::Student->value,
            'branch_id' => $first->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.students.store'), $payload($second->id, 'b@test.uz'))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('users', ['email' => 'b@test.uz']);
    }

    public function test_branch_admin_cannot_open_student_of_another_branch(): void
    {
        [$first, $second] = $this->branches();

        $this->actingAs($this->branchAdmin($first))
            ->get(route('admin.students.show', $this->student($second)))
            ->assertNotFound();
    }

    public function test_group_teacher_must_belong_to_the_branch(): void
    {
        [$first, $second] = $this->branches();
        $admin = $this->branchAdmin($first);

        $payload = fn (User $teacher) => [
            'name' => 'IELTS 1',
            'branch_id' => $first->id,
            'teacher_id' => $teacher->id,
            'capacity' => 15,
            'status' => 'active',
        ];

        $this->actingAs($admin)
            ->post(route('admin.groups.store'), $payload($this->teacher($second)))
            ->assertSessionHasErrors('teacher_id');

        $this->actingAs($admin)
            ->post(route('admin.groups.store'), $payload($this->teacher($first)))
            ->assertRedirect();

        $this->assertDatabaseHas('study_groups', ['name' => 'IELTS 1', 'branch_id' => $first->id]);
    }

    public function test_adding_students_respects_group_capacity(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);

        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Kichik', 'capacity' => 1]);

        $one = $this->student($first);
        $two = $this->student($first);

        $this->actingAs($admin)
            ->post(route('admin.groups.students.add', $group), ['student_id' => $one->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.groups.students.add', $group), ['student_id' => $two->id])
            ->assertSessionHasErrors('student_id');

        // Adding someone who is already enrolled is not refused because the group is full.
        $this->actingAs($admin)
            ->post(route('admin.groups.students.add', $group), ['student_id' => $one->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $group->students()->count());
    }

    public function test_group_capacity_cannot_drop_below_enrolled_students(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);

        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Guruh', 'capacity' => 10]);
        $group->students()->attach([$this->student($first)->id, $this->student($first)->id, $this->student($first)->id]);

        $payload = fn (int $capacity) => ['name' => 'Guruh', 'capacity' => $capacity, 'status' => 'active'];

        $this->actingAs($admin)
            ->put(route('admin.groups.update', $group), $payload(2))
            ->assertSessionHasErrors(['capacity' => 'Sig‘im guruhdagi o‘quvchilar sonidan (3) kam bo‘lishi mumkin emas.']);

        $this->actingAs($admin)
            ->put(route('admin.groups.update', $group), $payload(3))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $group->fresh()->capacity);
    }

    public function test_inactive_student_cannot_be_added_to_group(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);

        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Guruh', 'capacity' => 5]);
        $student = $this->student($first);
        $student->update(['status' => UserStatus::Inactive]);

        $this->actingAs($admin)
            ->post(route('admin.groups.students.add', $group), ['student_id' => $student->id])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(0, $group->students()->count());
    }

    public function test_creating_a_student_for_a_full_group_shows_a_warning(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);

        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'To‘lgan', 'capacity' => 1]);
        $group->students()->attach($this->student($first)->id);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Yangi talaba',
            'email' => 'yangi@test.uz',
            'password' => 'password123',
            'branch_id' => $first->id,
            'group_id' => $group->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('warning', '“To‘lgan” guruhida bo‘sh joy qolmagan, o‘quvchi guruhga biriktirilmadi.');

        $this->assertSame(1, $group->students()->count());
        $this->assertDatabaseHas('users', ['email' => 'yangi@test.uz']);
    }

    public function test_super_admin_creates_a_student_with_a_group_outside_the_selected_branch(): void
    {
        [, $second] = $this->branches();
        $group = StudyGroup::create(['branch_id' => $second->id, 'name' => 'Ikkinchi guruh', 'capacity' => 5]);

        // With nothing chosen in the switcher the super admin works in the first branch.
        $this->actingAs($this->superAdmin())->post(route('admin.students.store'), [
            'name' => 'Yangi talaba',
            'email' => 'yangi@test.uz',
            'password' => 'password123',
            'branch_id' => $second->id,
            'group_id' => $group->id,
        ])->assertRedirect()->assertSessionMissing('warning');

        $student = User::where('email', 'yangi@test.uz')->firstOrFail();
        $this->assertSame([$student->id], $group->students()->pluck('users.id')->all());
    }

    public function test_student_can_be_removed_from_group(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);

        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Guruh']);
        $student = $this->student($first);
        $group->students()->attach($student->id);

        $this->actingAs($admin)
            ->delete(route('admin.groups.students.remove', [$group, $student]))
            ->assertRedirect();

        $this->assertSame(0, $group->students()->count());
    }

    public function test_branch_admin_update_keeps_teacher_in_other_branches(): void
    {
        [$first, $second] = $this->branches();
        $teacher = $this->teacher($first, $second);

        $this->actingAs($this->branchAdmin($first))->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'branches' => [$first->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $teacher->branches()->pluck('branches.id')->all()
        );
    }

    public function test_branch_admin_cannot_change_credentials_of_a_shared_teacher(): void
    {
        [$first, $second] = $this->branches();
        $teacher = $this->teacher($first, $second);
        $email = $teacher->email;
        $password = $teacher->password;
        $admin = $this->branchAdmin($first);

        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'name' => 'Yangi ism',
            'email' => 'hijack@example.com',
            'password' => 'new-password-1',
            'branches' => [$first->id],
        ])->assertSessionHasErrors(['email', 'password']);

        $teacher->refresh();
        $this->assertSame($email, $teacher->email);
        $this->assertSame($password, $teacher->password);
        $this->assertNotSame('Yangi ism', $teacher->name);

        $this->actingAs($admin)->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="password"', false);
    }

    public function test_branch_admin_can_change_credentials_of_own_branch_teacher(): void
    {
        [$first] = $this->branches();
        $teacher = $this->teacher($first);
        $admin = $this->branchAdmin($first);

        $this->actingAs($admin)->get(route('admin.teachers.edit', $teacher))
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);

        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'email' => 'new@example.com',
            'password' => 'new-password-1',
            'branches' => [$first->id],
        ])->assertRedirect();

        $teacher->refresh();
        $this->assertSame('new@example.com', $teacher->email);
        $this->assertTrue(Hash::check('new-password-1', $teacher->password));
    }

    public function test_super_admin_can_change_credentials_of_a_shared_teacher(): void
    {
        [$first, $second] = $this->branches();
        $teacher = $this->teacher($first, $second);

        $this->actingAs($this->superAdmin())->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'email' => 'super@example.com',
            'branches' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSame('super@example.com', $teacher->fresh()->email);
    }

    public function test_status_can_be_toggled_and_inactive_user_is_logged_out(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);
        $student = $this->student($first);

        $this->actingAs($admin)->patch(route('admin.students.status', $student))->assertRedirect();

        $this->assertSame(UserStatus::Inactive, $student->fresh()->status);

        auth()->logout();

        $this->actingAs($student->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_management_pages_open_for_super_admin(): void
    {
        [$first] = $this->branches();
        $admin = $this->superAdmin();
        $student = $this->student($first);
        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Guruh']);
        $teacher = $this->teacher($first);

        foreach ([
            route('admin.branches.index'),
            route('admin.branches.create'),
            route('admin.groups.index'),
            route('admin.groups.create'),
            route('admin.groups.show', $group),
            route('admin.groups.edit', $group),
            route('admin.students.index'),
            route('admin.students.create'),
            route('admin.students.show', $student),
            route('admin.students.edit', $student),
            route('admin.teachers.index'),
            route('admin.teachers.create'),
            route('admin.teachers.edit', $teacher),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_dashboard_groups_use_the_same_status_switch_as_the_groups_page(): void
    {
        [$first] = $this->branches();
        $admin = $this->branchAdmin($first);
        $group = StudyGroup::create(['branch_id' => $first->id, 'name' => 'Bosh sahifa guruhi', 'capacity' => 10]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Bosh sahifa guruhi')
            ->assertSee(route('admin.groups.status', $group), false)
            ->assertSee('role="switch"', false);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->patch(route('admin.groups.status', $group))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(GroupStatus::Archived, $group->fresh()->status);
    }
}
