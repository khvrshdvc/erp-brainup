<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Badge;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Level;
use App\Models\ShopItem;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\XpRule;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_seeds_only_the_real_structure(): void
    {
        $this->seed(ProductionSeeder::class);

        $this->assertSame(['Chirchiq filiali'], Branch::pluck('name')->all());
        $this->assertSame(['english', 'it', 'math'], Course::orderBy('slug')->pluck('slug')->all());
        $this->assertSame(13, StudyGroup::withoutGlobalScopes()->count());
        $this->assertSame(4, User::teachers()->count());
        $this->assertSame(6, User::count());
        $this->assertSame(0, User::students()->count());
        $this->assertSame(0, Assignment::withoutGlobalScopes()->count());
        $this->assertSame(0, ShopItem::count());
        $this->assertSame(4, Level::count());
        $this->assertSame(5, XpRule::count());
        $this->assertSame(6, Badge::count());
        $this->assertTrue(User::where('email', 'admin@brainup.uz')->where('role', Role::SuperAdmin->value)->exists());
    }

    public function test_staff_get_generated_passwords_they_must_change(): void
    {
        $this->seed(ProductionSeeder::class);

        foreach (User::all() as $user) {
            $this->assertFalse(Hash::check('password', $user->password), $user->email);
            $this->assertTrue($user->must_change_password, $user->email);
        }

        $this->actingAs(User::where('email', 'utkirbek@brainup.uz')->firstOrFail())
            ->get(route('dashboard'))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_running_twice_creates_no_duplicates_and_keeps_passwords(): void
    {
        $this->seed(ProductionSeeder::class);
        $passwords = User::orderBy('id')->pluck('password', 'email')->all();

        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, Branch::count());
        $this->assertSame(13, StudyGroup::withoutGlobalScopes()->count());
        $this->assertSame($passwords, User::orderBy('id')->pluck('password', 'email')->all());
    }

    public function test_students_import_into_the_seeded_groups(): void
    {
        $this->seed(ProductionSeeder::class);

        $path = $this->files[] = tempnam(sys_get_temp_dir(), 'roster');
        file_put_contents($path, implode("\n", [
            'full_name,phone,email,password,groups',
            'Nazarov Ali,+998901234567,ali.nazarov@brainup.uz,135790,English-Utkirbek-4',
            'Nazarova Zarina,,zarina.nazarova@brainup.uz,246801,Math-Olimjon-1|IT-Utkirbek-3',
        ]));

        $this->artisan('students:import', ['file' => $path])->assertSuccessful();

        $this->assertSame(2, User::students()->count());
        $this->assertSame(1, StudyGroup::withoutGlobalScopes()->where('name', 'Math-Olimjon-1')->firstOrFail()->students()->count());
        $this->assertFalse(User::where('email', 'ali.nazarov@brainup.uz')->value('must_change_password'));
    }
}
