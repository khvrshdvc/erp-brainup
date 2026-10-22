<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\AssertsValidation;
use Tests\TestCase;

class AuthValidationTest extends TestCase
{
    use AssertsValidation;
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->create(['role' => Role::Teacher, 'email' => 'ustoz@test.uz']);
    }

    public function test_login_requires_both_fields(): void
    {
        $errors = $this->assertInvalid(null, 'post', route('login'), route('login'), [], ['email', 'password']);

        $this->assertSame(['Email kiritilishi shart.'], $errors['email']);
        $this->assertSame(['Parol kiritilishi shart.'], $errors['password']);
    }

    public function test_login_rejects_a_malformed_email(): void
    {
        $errors = $this->assertInvalid(null, 'post', route('login'), route('login'), ['email' => 'not-an-email', 'password' => 'x'], ['email']);

        $this->assertSame(['Email haqiqiy email manzil bo‘lishi kerak.'], $errors['email']);
    }

    public function test_login_reports_wrong_credentials_under_the_email_field(): void
    {
        $this->teacher();

        $errors = $this->assertInvalid(null, 'post', route('login'), route('login'), ['email' => 'ustoz@test.uz', 'password' => 'wrong-password'], ['email']);

        $this->assertSame(['Email yoki parol noto‘g‘ri.'], $errors['email']);
        $this->assertGuest();
    }

    public function test_login_keeps_the_typed_email_but_never_the_password(): void
    {
        $this->followingRedirects()->from(route('login'))
            ->post(route('login'), ['email' => 'ustoz@test.uz', 'password' => 'secret-password-1'])
            ->assertSee('value="ustoz@test.uz"', false)
            ->assertDontSee('secret-password-1');
    }

    public function test_login_marks_invalid_fields_for_the_eye_and_for_screen_readers(): void
    {
        $this->followingRedirects()->from(route('login'))->post(route('login'), [])
            ->assertSee('class="input is-invalid"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('role="alert"', false);
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $this->teacher();
        RateLimiter::clear('ustoz@test.uz|127.0.0.1');

        foreach (range(1, 5) as $ignored) {
            $this->from(route('login'))->post(route('login'), ['email' => 'ustoz@test.uz', 'password' => 'wrong']);
        }

        $errors = $this->assertInvalid(null, 'post', route('login'), route('login'), ['email' => 'ustoz@test.uz', 'password' => 'wrong'], ['email']);

        $this->assertStringContainsString('Juda ko‘p urinish', $errors['email'][0]);
        $this->assertMatchesRegularExpression('/\d+ soniyadan keyin/', $errors['email'][0]);
    }

    public function test_login_messages_follow_the_language(): void
    {
        $this->withCookie('locale', 'en')->from(route('login'))->post(route('login'), [])
            ->assertSessionHasErrors(['email' => 'The email field is required.', 'password' => 'The password field is required.']);

        $this->withCookie('locale', 'ru')->from(route('login'))->post(route('login'), [])
            ->assertSessionHasErrors(['email' => 'Поле email обязательно для заполнения.', 'password' => 'Поле пароль обязательно для заполнения.']);
    }

    public function test_forgot_password_validation(): void
    {
        $this->assertInvalid(null, 'post', route('password.email'), route('password.request'), [], ['email']);
        $this->assertInvalid(null, 'post', route('password.email'), route('password.request'), ['email' => 'nope'], ['email']);

        $errors = $this->assertInvalid(null, 'post', route('password.email'), route('password.request'), ['email' => 'nobody@test.uz'], ['email']);
        $this->assertSame(['Bu email bilan foydalanuvchi topilmadi.'], $errors['email']);
    }

    public function test_reset_password_validation(): void
    {
        $user = $this->teacher();
        $token = Password::createToken($user);
        $form = route('password.reset', $token);

        $this->assertInvalid(null, 'post', route('password.store'), $form, ['token' => $token], ['email', 'password']);
        $this->assertInvalid(null, 'post', route('password.store'), $form, ['token' => $token, 'email' => $user->email, 'password' => 'short1', 'password_confirmation' => 'short1'], ['password']);
        $this->assertInvalid(null, 'post', route('password.store'), $form, ['token' => $token, 'email' => $user->email, 'password' => 'long-enough-1', 'password_confirmation' => 'different-1'], ['password']);

        $errors = $this->assertInvalid(null, 'post', route('password.store'), $form, ['token' => 'bad-token', 'email' => $user->email, 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1'], ['email']);
        $this->assertSame(['Parolni tiklash havolasi yaroqsiz.'], $errors['email']);
    }

    public function test_confirm_password_validation(): void
    {
        $teacher = $this->teacher();
        $errors = $this->assertInvalid($teacher, 'post', route('password.confirm'), route('password.confirm'), ['password' => 'wrong'], ['password']);

        $this->assertSame(['Parol noto‘g‘ri.'], $errors['password']);
        $this->assertInvalid($teacher, 'post', route('password.confirm'), route('password.confirm'), [], ['password']);
    }

    public function test_login_has_no_remember_me_box_and_no_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('name="remember"', false)
            ->assertDontSee('Eslab qolish')
            ->assertDontSee('Parolni unutdingizmi?')
            ->assertDontSee(route('password.request'), false)
            ->assertSee('type="submit"', false);
    }
}
