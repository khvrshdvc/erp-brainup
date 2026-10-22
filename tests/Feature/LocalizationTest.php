<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use App\Support\UzDate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        $student = User::factory()->create([
            'role' => Role::Student,
            'branch_id' => Branch::create(['name' => 'Test filial'])->id,
        ]);

        if ($attributes) {
            $student->forceFill($attributes)->save();
        }

        return $student;
    }

    public function test_uzbek_is_the_default_language(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="uz"', false)
            ->assertSee('Tizimga kirish');
    }

    public function test_a_guest_switches_language_with_the_button(): void
    {
        $this->get(route('login'))
            ->assertSee('Tilni tanlang')
            ->assertSee('name="locale" value="en"', false)
            ->assertSee('name="locale" value="ru"', false)
            ->assertSee('name="locale" value="uz"', false);

        $this->from(route('login'))->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect(route('login'))
            ->assertCookie('locale', 'en');

        $this->withCookie('locale', 'en')->get(route('login'))
            ->assertSee('<html lang="en"', false)
            ->assertSee('Sign in')
            ->assertDontSee('Tizimga kirish');

        $this->withCookie('locale', 'ru')->get(route('login'))
            ->assertSee('<html lang="ru"', false)
            ->assertSee('Вход в систему');
    }

    public function test_unknown_languages_are_rejected_and_ignored(): void
    {
        $this->post(route('locale.switch'), ['locale' => 'de'])->assertSessionHasErrors('locale');

        $this->withCookie('locale', 'de')->get(route('login'))
            ->assertSee('<html lang="uz"', false);
    }

    public function test_a_signed_in_users_choice_is_saved_on_the_account(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post(route('locale.switch'), ['locale' => 'ru'])->assertRedirect();

        $this->assertSame('ru', $student->fresh()->locale);

        // A new device has no cookie, the saved choice still applies.
        $this->actingAs($student->fresh())->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ru"', false)
            ->assertSee('Здравствуйте')
            ->assertDontSee('Salom,');
    }

    public function test_a_guests_choice_follows_them_into_their_account(): void
    {
        $student = $this->student(['locale' => null]);

        $this->withCookie('locale', 'en')->actingAs($student)->get(route('student.dashboard'))
            ->assertSee('<html lang="en"', false);

        $this->assertSame('en', $student->fresh()->locale);
    }

    public function test_the_account_choice_wins_over_the_cookie(): void
    {
        $student = $this->student(['locale' => 'uz']);

        $this->withCookie('locale', 'en')->actingAs($student)->get(route('student.dashboard'))
            ->assertSee('<html lang="uz"', false);
    }

    public function test_the_topbar_shows_the_current_language_and_the_menu(): void
    {
        $student = $this->student(['locale' => 'en']);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertSee('class="iconbtn lang-btn"', false)
            ->assertSeeInOrder(['<span>EN</span>', 'lang-menu'], false)
            ->assertSee('O‘zbekcha')
            ->assertSee('English')
            ->assertSee('Русский');
    }

    public function test_enum_labels_follow_the_language(): void
    {
        $this->assertSame('Keldi', AttendanceStatus::Present->label());

        app()->setLocale('en');
        $this->assertSame('Present', AttendanceStatus::Present->label());
        $this->assertSame('Student', Role::Student->label());

        app()->setLocale('ru');
        $this->assertSame('Пришёл', AttendanceStatus::Present->label());
        $this->assertSame('Ученик', Role::Student->label());
    }

    public function test_dates_follow_the_language(): void
    {
        $date = Carbon::create(2026, 10, 24, 12); // a Saturday

        $this->assertSame('Shanba, 24-oktabr 2026', UzDate::long($date));
        $this->assertSame('24-oktabr', UzDate::dayMonth($date));
        $this->assertSame('Sh', UzDate::shortDay($date));

        app()->setLocale('en');
        $this->assertSame('Saturday, October 24, 2026', UzDate::long($date));
        $this->assertSame('Sa', UzDate::shortDay($date));

        app()->setLocale('ru');
        $this->assertSame('Суббота, 24 октября 2026 г.', UzDate::long($date));
        $this->assertSame('Сб', UzDate::shortDay($date));

        $this->travelTo(now());
        $this->assertSame('2 kun 5 soat', $this->remaining('uz'));
        $this->assertSame('2 d 5 h', $this->remaining('en'));
        $this->assertSame('2 дн. 5 ч', $this->remaining('ru'));
    }

    private function remaining(string $locale): string
    {
        app()->setLocale($locale);

        return UzDate::remaining(now()->addDays(2)->addHours(5)->addSeconds(30));
    }

    public function test_validation_messages_and_attribute_names_are_translated(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);
        $admin->forceFill(['locale' => 'en'])->save();

        $this->actingAs($admin)->post(route('admin.branches.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The branch name field is required.']);

        $admin->forceFill(['locale' => 'ru'])->save();

        $this->actingAs($admin->fresh())->post(route('admin.branches.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Поле название филиала обязательно для заполнения.']);

        $admin->forceFill(['locale' => 'uz'])->save();

        $this->actingAs($admin->fresh())->post(route('admin.branches.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Filial nomi kiritilishi shart.']);
    }

    public function test_flash_messages_are_translated(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);
        $admin->forceFill(['locale' => 'en'])->save();

        $this->actingAs($admin)->post(route('admin.branches.store'), ['name' => 'Yangi filial'])
            ->assertSessionHas('status', 'Branch added');

        $admin->forceFill(['locale' => 'ru'])->save();

        $this->actingAs($admin->fresh())->post(route('admin.branches.store'), ['name' => 'Boshqa filial'])
            ->assertSessionHas('status', 'Филиал добавлен');
    }

    public function test_the_locale_cookie_lasts_a_year(): void
    {
        $this->post(route('locale.switch'), ['locale' => 'ru']);

        $cookie = collect(Cookie::getQueuedCookies())->first(fn ($c) => $c->getName() === 'locale');
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(now()->addYear()->timestamp, $cookie->getExpiresTime(), 120);
    }
}
