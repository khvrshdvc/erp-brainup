<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_expected_users_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(200, User::where('role', Role::Student)->count());
        $this->assertSame(4, User::where('role', Role::Teacher)->count());
        $this->assertSame(1, User::where('role', Role::BranchAdmin)->count());
        $this->assertSame(1, User::where('role', Role::SuperAdmin)->count());
        $this->assertSame(0, User::whereNull('email_verified_at')->count());
    }
}
