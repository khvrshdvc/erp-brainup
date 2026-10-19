<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame(1, $group->students()->count());
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
            'email' => $teacher->email,
            'branches' => [$first->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $teacher->branches()->pluck('branches.id')->all()
        );
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
}
