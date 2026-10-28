<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use App\Services\CredentialGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private CredentialGenerator $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        config(['erp.login_domain' => 'brainup.uz']);

        $this->credentials = new CredentialGenerator;
    }

    public function test_uzbek_letters_and_apostrophes_are_transliterated(): void
    {
        $this->assertSame('otkir.gulomov', $this->credentials->slug('O‘tkir G‘ulomov'));
        $this->assertSame('otkir.gulomov', $this->credentials->slug('Oʻtkir Gʻulomov'));
        $this->assertSame('otkir.gulomov', $this->credentials->slug("O'tkir G'ulomov"));
        $this->assertSame('marifat.sanatov', $this->credentials->slug('Ma’rifat San`atov'));
        $this->assertSame('otkir.gulomov', $this->credentials->slug('Ўткир Ғуломов'));
        $this->assertSame('shohruh.choriyev', $this->credentials->slug('  Shohruh   Choriyev!! '));
    }

    public function test_teacher_login_is_name_dot_surname(): void
    {
        $this->assertSame('otkir.gulomov@brainup.uz', $this->credentials->login(Role::Teacher, 'O‘tkir G‘ulomov Abdullayevich'));
        $this->assertSame('utkirbek.tulqinov@brainup.uz', $this->credentials->login(Role::Teacher, 'Utkirbek Tulqinov'));
    }

    public function test_login_domain_comes_from_config(): void
    {
        config(['erp.login_domain' => 'example.uz']);

        $this->assertSame('utkirbek.tulqinov@example.uz', $this->credentials->login(Role::Teacher, 'Utkirbek Tulqinov'));
    }

    public function test_branch_admin_login_is_the_branch_name(): void
    {
        $this->assertSame('chirchiq@brainup.uz', $this->credentials->login(Role::BranchAdmin, 'Chirchiq'));
    }

    public function test_student_login_is_the_nine_digit_phone(): void
    {
        foreach (['+998 90 123 45 67', '+998901234567', '998-90-123-45-67', '90 123 45 67', '(90) 123-45-67'] as $phone) {
            $this->assertSame('901234567@brainup.uz', $this->credentials->login(Role::Student, 'Aziz Karimov', $phone), $phone);
        }
    }

    public function test_student_without_a_usable_phone_gets_name_and_two_digits(): void
    {
        foreach ([null, '', '12345'] as $phone) {
            $this->assertMatchesRegularExpression(
                '/^aziz\.karimov\d{2}@brainup\.uz$/',
                $this->credentials->login(Role::Student, 'Aziz Karimov', $phone)
            );
        }
    }

    public function test_taken_login_gets_the_next_free_number(): void
    {
        User::factory()->create(['email' => 'utkirbek.tulqinov@brainup.uz']);
        User::factory()->create(['email' => 'utkirbek.tulqinov2@brainup.uz']);
        User::factory()->create(['email' => '901234567@brainup.uz']);

        $this->assertSame('utkirbek.tulqinov3@brainup.uz', $this->credentials->login(Role::Teacher, 'Utkirbek Tulqinov'));
        $this->assertSame('9012345672@brainup.uz', $this->credentials->login(Role::Student, 'Aziz Karimov', '+998901234567'));
    }

    public function test_login_without_a_usable_name_is_null(): void
    {
        $this->assertNull($this->credentials->login(Role::Teacher, '!!!'));
        $this->assertNull($this->credentials->login(Role::Student, '', null));
    }

    public function test_logins_only_use_lowercase_letters_digits_and_dots(): void
    {
        $login = $this->credentials->login(Role::Teacher, 'Ёқубжон Ҳамдамов-Ўғли');

        $this->assertMatchesRegularExpression('/^[a-z0-9.]+@brainup\.uz$/', $login);
        $this->assertSame('yoqubjon.hamdamov@brainup.uz', $login);
    }

    public function test_word_list_has_fifty_simple_words(): void
    {
        $words = CredentialGenerator::WORDS;

        $this->assertGreaterThanOrEqual(50, count($words));
        $this->assertSame($words, array_values(array_unique($words)));

        foreach ($words as $word) {
            $this->assertMatchesRegularExpression('/^[a-z]{4,6}$/', $word);
        }
    }

    public function test_student_password_is_a_word_and_four_digits(): void
    {
        foreach (range(1, 50) as $ignored) {
            $password = $this->credentials->password(Role::Student);

            $this->assertMatchesRegularExpression('/^([a-z]{4,6})\d{4}$/', $password);
            $this->assertContains(preg_replace('/\d+$/', '', $password), CredentialGenerator::WORDS);
        }
    }

    public function test_teacher_password_is_ten_unambiguous_mixed_characters(): void
    {
        foreach (range(1, 50) as $ignored) {
            $password = $this->credentials->password(Role::Teacher);

            $this->assertSame(10, strlen($password));
            $this->assertMatchesRegularExpression('/^[A-Za-z2-9]+$/', $password);
            $this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $password);
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/\d/', $password);
        }
    }

    public function test_admin_passwords_are_three_words_and_two_digits(): void
    {
        foreach ([Role::SuperAdmin, Role::BranchAdmin] as $role) {
            foreach (range(1, 20) as $ignored) {
                $password = $this->credentials->password($role);

                $this->assertMatchesRegularExpression('/^[a-z]{4,6}-[a-z]{4,6}-[a-z]{4,6}-\d{2}$/', $password);

                $words = array_slice(explode('-', $password), 0, 3);
                $this->assertCount(3, array_unique($words));
                $this->assertEmpty(array_diff($words, CredentialGenerator::WORDS));
            }
        }
    }
}
