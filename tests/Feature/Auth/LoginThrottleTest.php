<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $email, string $password, string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_an_account_is_locked_after_many_failures_from_different_addresses(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 20; $i++) {
            $this->attempt($user->email, 'wrong-'.$i, "10.0.0.$i");
        }

        $this->attempt($user->email, 'password', '10.0.1.1')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_address_is_locked_after_trying_many_accounts(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 50; $i++) {
            $this->attempt("student$i@brainup.uz", '123456', '10.0.0.9');
        }

        $this->attempt($user->email, 'password', '10.0.0.9')->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->attempt($user->email, 'password', '10.0.0.10');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_few_mistakes_do_not_lock_the_account(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 4; $i++) {
            $this->attempt($user->email, 'wrong', '10.0.0.5');
        }

        $this->attempt($user->email, 'password', '10.0.0.5');
        $this->assertAuthenticatedAs($user);
    }
}
