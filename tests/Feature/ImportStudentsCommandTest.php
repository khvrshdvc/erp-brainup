<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private StudyGroup $english;

    private StudyGroup $math;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Chirchiq filiali', 'is_active' => true]);
        $this->english = StudyGroup::factory()->create(['branch_id' => $this->branch->id, 'name' => 'English-Utkirbek-3', 'capacity' => 2]);
        $this->math = StudyGroup::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Math-Olimjon-2', 'capacity' => 15]);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        foreach (glob(storage_path('app/private/credentials-*.csv')) as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'roster');
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['full_name', 'phone', 'email', 'password', 'groups'], ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        fclose($handle);

        return $this->files[] = $path;
    }

    private function roster(): string
    {
        return $this->csv([
            ['Karimova Dilnoza', '+998901112233', 'dilnoza.karimova@brainup.uz', '482915', 'English-Utkirbek-3|Math-Olimjon-2'],
            ['Karimova Madina', '', 'madina.karimova@brainup.uz', '730264', 'English-Utkirbek-3|Math-Olimjon-2'],
            ['Toshmatov Jasur', '+998935554466', 'jasur.toshmatov@brainup.uz', '519308', 'English-Utkirbek-3'],
        ]);
    }

    public function test_it_creates_students_with_the_given_logins_and_groups(): void
    {
        $this->artisan('students:import', ['file' => $this->roster(), '--branch' => 'Chirchiq filiali'])->assertSuccessful();

        $dilnoza = User::where('email', 'dilnoza.karimova@brainup.uz')->firstOrFail();

        $this->assertSame(Role::Student, $dilnoza->role);
        $this->assertSame($this->branch->id, (int) $dilnoza->branch_id);
        $this->assertSame('+998901112233', $dilnoza->phone);
        $this->assertTrue(Hash::check('482915', $dilnoza->password));
        $this->assertNull(User::where('email', 'madina.karimova@brainup.uz')->value('phone'));
        $this->assertSame(3, $this->english->students()->count());
        $this->assertSame(2, $this->math->students()->count());
        $this->assertSame(3, $this->english->fresh()->capacity);
        $this->assertSame(15, $this->math->fresh()->capacity);
        $this->assertSame([], glob(storage_path('app/private/credentials-*.csv')));
    }

    public function test_running_twice_creates_no_duplicates_and_keeps_passwords(): void
    {
        $file = $this->roster();
        $this->artisan('students:import', ['file' => $file, '--branch' => $this->branch->id])->assertSuccessful();

        User::where('email', 'jasur.toshmatov@brainup.uz')->firstOrFail()->update(['password' => 'yangi-parol']);

        $this->artisan('students:import', ['file' => $file, '--branch' => $this->branch->id])->assertSuccessful();

        $this->assertSame(3, User::students()->count());
        $this->assertSame(3, $this->english->students()->count());
        $this->assertTrue(Hash::check('yangi-parol', User::where('email', 'jasur.toshmatov@brainup.uz')->value('password')));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('students:import', ['file' => $this->roster(), '--branch' => $this->branch->id, '--dry-run' => true])
            ->expectsOutputToContain('Sinov rejimi')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(2, $this->english->fresh()->capacity);
    }

    public function test_unknown_group_stops_the_import(): void
    {
        $file = $this->csv([['Toshmatov Jasur', '', 'jasur.toshmatov@brainup.uz', '519308', 'IT-Utkirbek-9']]);

        $this->artisan('students:import', ['file' => $file, '--branch' => $this->branch->id])
            ->expectsOutputToContain('IT-Utkirbek-9')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_missing_login_and_password_are_generated_and_saved(): void
    {
        User::factory()->create(['email' => 'ogabek.yoldoshev@brainup.uz']);

        $file = $this->csv([
            ['Rahimov Bobur', '88 123 45 67', '', '', 'Math-Olimjon-2'],
            ['Yo‘ldoshev Og‘abek', '', '', '', 'Math-Olimjon-2'],
        ]);

        $this->artisan('students:import', ['file' => $file, '--branch' => $this->branch->id])->assertSuccessful();

        $student = User::where('email', 'bobur.rahimov@brainup.uz')->firstOrFail();
        $saved = glob(storage_path('app/private/credentials-*.csv'));

        $this->assertSame('+998881234567', $student->phone);
        $this->assertTrue(User::where('email', 'ogabek.yoldoshev2@brainup.uz')->exists());
        $this->assertCount(1, $saved);

        [, $row] = array_map(fn ($line) => str_getcsv(ltrim($line, "\xEF\xBB\xBF"), ',', '"', ''), file($saved[0], FILE_IGNORE_NEW_LINES));

        $this->assertSame('bobur.rahimov@brainup.uz', $row[1]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $row[2]);
        $this->assertTrue(Hash::check($row[2], $student->password));
    }

    public function test_a_phone_taken_by_another_user_is_left_empty(): void
    {
        User::factory()->create(['phone' => '+998901112233']);

        $this->artisan('students:import', ['file' => $this->roster(), '--branch' => $this->branch->id])
            ->expectsOutputToContain('band')
            ->assertSuccessful();

        $this->assertNull(User::where('email', 'dilnoza.karimova@brainup.uz')->value('phone'));
    }

    public function test_an_email_of_another_role_is_not_touched(): void
    {
        User::factory()->create(['email' => 'jasur.toshmatov@brainup.uz', 'role' => Role::Teacher]);

        $this->artisan('students:import', ['file' => $this->roster(), '--branch' => $this->branch->id])->assertSuccessful();

        $this->assertSame(2, User::students()->count());
        $this->assertSame(2, $this->english->students()->count());
    }

    public function test_an_email_of_a_different_student_is_not_touched(): void
    {
        $other = User::factory()->create([
            'name' => 'Toshmatov Sardor',
            'email' => 'jasur.toshmatov@brainup.uz',
            'role' => Role::Student,
            'branch_id' => $this->branch->id,
        ]);

        $this->artisan('students:import', ['file' => $this->roster(), '--branch' => $this->branch->id])
            ->expectsOutputToContain('boshqa foydalanuvchiga tegishli')
            ->assertSuccessful();

        $this->assertFalse($this->english->students()->where('users.id', $other->id)->exists());
        $this->assertSame(2, $this->english->students()->count());
    }
}
