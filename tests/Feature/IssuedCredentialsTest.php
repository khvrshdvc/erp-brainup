<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use App\Support\IssuedCredentials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IssuedCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['erp.login_domain' => 'brainup.uz', 'app.url' => 'https://erp.brainup.uz']);

        $this->branch = Branch::create(['name' => 'Chirchiq']);
        $this->admin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $this->branch->id]);
    }

    /**
     * The credentials flashed by the last request, decrypted.
     */
    private function issued(TestResponse $response): array
    {
        $response->assertSessionHas(IssuedCredentials::SESSION_KEY);

        return Crypt::decrypt(session(IssuedCredentials::SESSION_KEY));
    }

    private function teacher(Branch ...$branches): User
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach(collect($branches)->pluck('id')->all());

        return $teacher;
    }

    public function test_empty_student_login_and_password_are_generated_on_save(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'O‘tkir G‘ulomov',
            'phone' => '+998 90 123 45 67',
            'branch_id' => $this->branch->id,
        ]);

        $student = User::where('name', 'O‘tkir G‘ulomov')->firstOrFail();
        $response->assertRedirect(route('admin.students.show', $student));

        $issued = $this->issued($response);

        $this->assertSame('901234567@brainup.uz', $student->email);
        $this->assertMatchesRegularExpression('/^[a-z]{4,6}\d{4}$/', $issued['password']);
        $this->assertTrue(Hash::check($issued['password'], $student->password));
        $this->assertNotSame($issued['password'], $student->password);
        $this->assertFalse($student->must_change_password, 'students keep the password on their card');
        $this->assertSame(['name' => 'O‘tkir G‘ulomov', 'branch' => 'Chirchiq', 'site' => 'https://erp.brainup.uz', 'login' => '901234567@brainup.uz'], array_diff_key($issued, ['password' => 1]));
    }

    public function test_empty_teacher_login_and_password_are_generated_and_must_be_changed(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.teachers.store'), [
            'name' => 'Utkirbek Tulqinov',
            'branches' => [$this->branch->id],
        ])->assertRedirect(route('admin.teachers.index'));

        $teacher = User::where('email', 'utkirbek.tulqinov@brainup.uz')->firstOrFail();
        $issued = $this->issued($response);

        $this->assertSame(10, strlen($issued['password']));
        $this->assertTrue(Hash::check($issued['password'], $teacher->password));
        $this->assertTrue($teacher->must_change_password);
    }

    public function test_typed_login_and_password_are_kept_and_shown_once(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aziz Karimov',
            'email' => 'aziz@test.uz',
            'password' => 'olma4821',
            'branch_id' => $this->branch->id,
        ]);

        $student = User::where('email', 'aziz@test.uz')->firstOrFail();
        $this->assertSame('olma4821', $this->issued($response)['password']);
        $this->assertTrue(Hash::check('olma4821', $student->password));
    }

    public function test_password_is_shown_only_on_the_next_page_and_never_stored_in_plain_text(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aziz Karimov',
            'branch_id' => $this->branch->id,
        ]);

        $password = $this->issued($response)['password'];
        $student = User::where('name', 'Aziz Karimov')->firstOrFail();

        // Neither the session store nor the database holds the plain password.
        $this->assertStringNotContainsString($password, serialize(session()->all()));
        $this->assertStringNotContainsString($password, json_encode(User::query()->toBase()->get()));

        $first = $this->actingAs($this->admin)->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee($password)
            ->assertSee($student->email)
            ->assertSee('https://erp.brainup.uz')
            ->assertSee('Kartochkani chop etish');
        $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));

        $this->actingAs($this->admin)->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertDontSee($password)
            ->assertDontSee('Kartochkani chop etish');
    }

    public function test_generate_endpoint_returns_login_and_password(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.credentials.generate'), [
            'role' => 'teacher',
            'name' => 'Utkirbek Tulqinov',
        ])->assertOk()->assertJsonPath('email', 'utkirbek.tulqinov@brainup.uz')
            ->assertJson(fn ($json) => $json->whereType('password', 'string')->etc());

        $this->actingAs($this->admin)->postJson(route('admin.credentials.generate'), [
            'role' => 'student',
            'phone' => '+998901234567',
        ])->assertOk()->assertJsonPath('email', '901234567@brainup.uz');

        $this->actingAs($this->admin)->postJson(route('admin.credentials.generate'), ['role' => 'student'])
            ->assertOk()->assertJsonPath('email', null);

        $this->actingAs($this->admin)->postJson(route('admin.credentials.generate'), ['role' => 'super_admin'])
            ->assertUnprocessable();

        $this->actingAs($this->teacher($this->branch))->postJson(route('admin.credentials.generate'), ['role' => 'student'])
            ->assertForbidden();
    }

    public function test_create_forms_have_generate_buttons(): void
    {
        foreach (['admin.students.create', 'admin.teachers.create'] as $route) {
            $this->actingAs($this->admin)->get(route($route))
                ->assertOk()
                ->assertSee('credentialFields', false)
                ->assertSee("fill('email')", false)
                ->assertSee("fill('password')", false);
        }
    }

    public function test_resetting_a_students_password_shows_the_new_one_once(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)->get(route('admin.students.edit', $student))->assertSee(route('admin.students.password', $student));

        $response = $this->actingAs($this->admin)->from(route('admin.students.edit', $student))
            ->post(route('admin.students.password', $student))
            ->assertRedirect(route('admin.students.edit', $student));

        $password = $this->issued($response)['password'];
        $student->refresh();

        $this->assertTrue(Hash::check($password, $student->password));
        $this->assertFalse(Hash::check('password', $student->password));
        $this->assertFalse($student->must_change_password);
    }

    public function test_resetting_a_teachers_password_requires_a_change_at_next_sign_in(): void
    {
        $teacher = $this->teacher($this->branch);

        $response = $this->actingAs($this->admin)->post(route('admin.teachers.password', $teacher))->assertRedirect();

        $teacher->refresh();
        $this->assertTrue(Hash::check($this->issued($response)['password'], $teacher->password));
        $this->assertTrue($teacher->must_change_password);
    }

    public function test_a_new_password_signs_the_account_out_of_sessions_opened_with_the_old_one(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id]);

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();

        // The admin resets it while the student's browser still holds the old session.
        $student->forceFill(['password' => 'olma1234'])->save();

        $this->actingAs($student)->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_branch_admin_cannot_reset_a_teacher_shared_with_another_branch(): void
    {
        $teacher = $this->teacher($this->branch, Branch::create(['name' => 'Toshkent']));

        $this->actingAs($this->admin)->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertDontSee(route('admin.teachers.password', $teacher));

        $this->actingAs($this->admin)->post(route('admin.teachers.password', $teacher))->assertForbidden();

        $this->assertTrue(Hash::check('password', $teacher->fresh()->password));
        $this->assertFalse($teacher->fresh()->must_change_password);
    }

    public function test_branch_admin_cannot_reset_a_student_of_another_branch(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => Branch::create(['name' => 'Toshkent'])->id]);

        $this->actingAs($this->admin)->post(route('admin.students.password', $student))->assertNotFound();
    }

    public function test_staff_with_a_temporary_password_is_held_on_the_profile_until_it_is_changed(): void
    {
        $teacher = $this->teacher($this->branch);
        $teacher->forceFill(['must_change_password' => true])->save();

        $this->actingAs($teacher)->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->actingAs($teacher)->get(route('groups.index'))->assertRedirect(route('profile.edit'));

        $this->actingAs($teacher)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Siz administrator bergan vaqtinchalik parol bilan kirdingiz. Davom etish uchun parolni o‘zgartiring.');

        $this->actingAs($teacher)->from(route('profile.edit'))->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect(route('profile.edit'));

        // The new password must differ from the temporary one.
        $this->actingAs($teacher)->from(route('profile.edit'))->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
        $this->assertTrue($teacher->fresh()->must_change_password);

        $this->actingAs($teacher)->from(route('profile.edit'))->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'yangi-parol-2026',
            'password_confirmation' => 'yangi-parol-2026',
        ])->assertRedirect(route('profile.edit'));

        $this->assertFalse($teacher->fresh()->must_change_password);
        $this->actingAs($teacher->fresh())->get(route('groups.index'))->assertOk();
    }

    public function test_logout_stays_open_with_a_temporary_password(): void
    {
        $teacher = $this->teacher($this->branch);
        $teacher->forceFill(['must_change_password' => true])->save();

        $this->actingAs($teacher)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_students_are_never_held_on_the_profile(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id, 'must_change_password' => true]);

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    }
}
