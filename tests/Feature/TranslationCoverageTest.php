<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Enums\ShopOrderStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserStatus;
use App\Enums\WeekDay;
use App\Models\Badge;
use App\Models\Level;
use App\Models\XpRule;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Uzbek texts are the translation keys, so a text that is shown through __() but missing from
 * lang/en.json or lang/ru.json would silently stay Uzbek. These tests make that visible.
 */
class TranslationCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $locale): array
    {
        return json_decode(File::get(lang_path($locale.'.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Static __('...') keys used by views and PHP code (dotted lowercase ones are PHP lang groups).
     *
     * @return array<string, list<string>>
     */
    private function usedKeys(): array
    {
        $keys = [];
        $files = collect(File::allFiles(resource_path('views')))->merge(File::allFiles(app_path()));

        foreach ($files as $file) {
            $source = $file->getContents();

            preg_match_all('/__\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $source, $calls);
            preg_match_all('/array_map\(\'__\', \[(.*?)\]\)/s', $source, $maps);

            foreach ($maps[1] as $list) {
                preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'/', $list, $items);
                $calls[1] = array_merge($calls[1], $items[1]);
            }

            foreach ($calls[1] as $key) {
                $key = stripslashes($key);

                if (! preg_match('/^[a-z_]+\.[a-z_.]*$/', $key)) {
                    $keys[$key][] = $file->getRelativePathname();
                }
            }
        }

        return $keys;
    }

    public function test_every_text_shown_through_the_translator_is_translated(): void
    {
        $en = $this->translations('en');
        $ru = $this->translations('ru');
        $uz = $this->translations('uz');

        foreach ($this->usedKeys() as $key => $files) {
            // English Breeze texts (auth pages) are translated to Uzbek instead.
            if (array_key_exists($key, $uz)) {
                continue;
            }

            $this->assertArrayHasKey($key, $en, "Missing English translation of “{$key}” (used in {$files[0]})");
            $this->assertArrayHasKey($key, $ru, "Missing Russian translation of “{$key}” (used in {$files[0]})");
        }
    }

    public function test_english_and_russian_have_the_same_keys_and_placeholders(): void
    {
        $en = $this->translations('en');
        $ru = $this->translations('ru');

        $this->assertSame([], array_keys(array_diff_key($en, $ru)));
        $this->assertSame([], array_keys(array_diff_key($ru, $en)));

        foreach ($en as $key => $english) {
            $placeholders = fn (string $text) => collect(preg_match_all('/:[a-z_]+/', $text, $m) ? $m[0] : [])->sort()->values()->all();

            $this->assertSame($placeholders($key), $placeholders($english), "English placeholders differ for “{$key}”");
            $this->assertSame($placeholders($key), $placeholders($ru[$key]), "Russian placeholders differ for “{$key}”");
            $this->assertNotSame('', trim($english));
            $this->assertNotSame('', trim($ru[$key]));
        }
    }

    public function test_russian_text_is_cyrillic_and_english_is_not_uzbek(): void
    {
        foreach ($this->translations('ru') as $key => $text) {
            if (preg_match('/[a-zA-Z]{3,}/u', preg_replace('/:[a-z_]+|BrainCoin|BrainUp|CSV|PDF|MB|KB|JPG|PNG|WEBP|DOC|DOCX|Email|email|HEX|[A-Z]{2,}/', '', $text))) {
                $this->fail("Russian translation of “{$key}” still has Latin words: {$text}");
            }
        }

        foreach ($this->translations('en') as $key => $text) {
            $this->assertDoesNotMatchRegularExpression('/[a-zA-Z][‘ʻ][a-zA-Z]/u', $text, "English translation of “{$key}” looks Uzbek: {$text}");
        }
    }

    public function test_enums_and_seeded_texts_are_translated(): void
    {
        $this->seed(GamificationSeeder::class);

        $texts = collect([
            AssignmentStatus::cases(), AttendanceStatus::cases(), GroupStatus::cases(), Role::cases(),
            ShopOrderStatus::cases(), SubmissionStatus::cases(), UserStatus::cases(),
        ])->flatten()->map(fn ($case) => $case->label());

        $texts = $texts
            ->merge(collect(WeekDay::cases())->flatMap(fn (WeekDay $day) => [$day->label(), $day->shortLabel()]))
            ->merge(Level::pluck('name'))
            ->merge(Badge::pluck('title'))
            ->merge(Badge::pluck('description'))
            ->merge(XpRule::pluck('title'))
            ->merge(['Yangi vazifa', 'Yangi topshiriq', 'Do‘kondan yangi buyurtma', 'Buyurtmangiz topshirildi', 'Buyurtma bekor qilindi', 'Vazifa tekshirildi', 'Qayta topshirish kerak'])
            // Course names seeded by CourseSeeder.
            ->merge(['Ingliz tili', 'Ingliz tili (ru)', 'Frontend dasturlash', 'Matematika', 'IT'])
            // Notification bodies are stored as keys with parameters and translated when shown.
            ->merge([
                ':title · muddat: :due', ':student «:title» vazifasini topshirdi', ':student · :item · :price BrainCoin', ':item',
                ':item · :price BrainCoin hamyoningizga qaytarildi', ':item · :price BrainCoin hamyoningizga qaytarildi · :note',
            ]);

        // Labels are already translated for the current (Uzbek) language, so they are the keys themselves.
        foreach ($texts->unique() as $key) {
            $this->assertArrayHasKey($key, $this->translations('en'), "No English translation of “{$key}”");
            $this->assertArrayHasKey($key, $this->translations('ru'), "No Russian translation of “{$key}”");
        }
    }

    public function test_php_language_files_have_the_same_keys_in_every_language(): void
    {
        foreach (['validation', 'auth', 'passwords', 'pagination', 'dates'] as $group) {
            $uz = array_keys(require lang_path("uz/{$group}.php"));

            foreach (['en', 'ru'] as $locale) {
                // Every Uzbek line needs a counterpart (the framework's English file may have a few more).
                $this->assertSame([], array_values(array_diff($uz, array_keys(require lang_path("{$locale}/{$group}.php")))), "{$locale}/{$group}.php lacks lines of uz/{$group}.php");
            }
        }

        foreach (['days', 'days_short', 'months', 'months_full', 'months_short'] as $list) {
            foreach (['uz', 'en', 'ru'] as $locale) {
                $this->assertCount(str_starts_with($list, 'days') ? 7 : 12, (require lang_path("{$locale}/dates.php"))[$list]);
            }
        }
    }
}
