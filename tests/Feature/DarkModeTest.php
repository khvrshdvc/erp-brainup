<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        return file_get_contents(public_path('css/erp.css'));
    }

    /**
     * @return array<string, string>
     */
    private function variables(string $block): array
    {
        preg_match_all('/(--[\w-]+):\s*([^;]+);/', $block, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $m) => [$m[1] => trim($m[2])])->all();
    }

    public function test_every_page_layout_has_the_toggle_and_the_early_theme_script(): void
    {
        $branch = Branch::create(['name' => 'Test filial']);

        foreach ([Role::SuperAdmin, Role::BranchAdmin, Role::Teacher, Role::Student] as $role) {
            $user = User::factory()->create(['role' => $role, 'branch_id' => $branch->id]);

            $this->actingAs($user)->get(route('dashboard'))->assertRedirect();
            $this->actingAs($user)->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('data-theme-toggle', false)
                ->assertSee('ErpTheme.toggle()', false)
                ->assertSee('localStorage.getItem(KEY)', false)
                ->assertSee('Tungi rejimga o‘tish');
        }
    }

    public function test_the_script_runs_before_the_stylesheet_so_the_page_does_not_flash(): void
    {
        $user = User::factory()->create(['role' => Role::Student, 'branch_id' => Branch::create(['name' => 'F'])->id]);

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertLessThan(strpos($html, 'css/erp.css'), strpos($html, 'window.ErpTheme'));
    }

    public function test_the_login_page_can_switch_theme_and_swaps_the_logo(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('logo-brainup-dark.png', false)
            ->assertSee('logo-brainup-white.png', false)
            ->assertSee('logo-on-dark', false);
    }

    public function test_the_dark_theme_overrides_only_known_variables_and_all_the_main_ones(): void
    {
        preg_match('/:root \{(.*?)\n\}/s', $this->css(), $light);
        preg_match('/:root\[data-theme="dark"\] \{(.*?)\n\}/s', $this->css(), $dark);

        $this->assertNotEmpty($light);
        $this->assertNotEmpty($dark);

        $lightVars = $this->variables($light[1]);
        $darkVars = $this->variables($dark[1]);

        $this->assertSame([], array_values(array_diff(array_keys($darkVars), array_keys($lightVars))), 'Dark theme defines a variable that the light theme does not');

        foreach (['--ink', '--ink2', '--muted', '--line', '--line2', '--bg', '--surface', '--hover', '--line-strong', '--ok', '--warn', '--err', '--ok-bg', '--warn-bg', '--err-bg', '--neutral', '--neutral-bg', '--accent-soft', '--accent-dark'] as $name) {
            $this->assertArrayHasKey($name, $darkVars, "Dark theme is missing {$name}");
            $this->assertNotSame($lightVars[$name], $darkVars[$name], "{$name} is the same in both themes");
        }
    }

    public function test_surfaces_use_the_theme_variable_instead_of_hard_coded_white(): void
    {
        // The only legitimate white backgrounds are the --surface definition and the switch knob.
        preg_match_all('/background:\s*#fff\b[^;]*;/i', $this->css(), $matches);

        $this->assertCount(1, $matches[0], 'Unexpected hard-coded white backgrounds: '.implode(' | ', $matches[0]));
        $this->assertStringContainsString('background: #fff; box-shadow: 0 1px 2px', $this->css());
        $this->assertStringNotContainsString('1px solid #D1D5DB', $this->css());
    }

    private function darkPalette(): array
    {
        preg_match('/:root \{(.*?)\n\}/s', $this->css(), $light);
        preg_match('/:root\[data-theme="dark"\] \{(.*?)\n\}/s', $this->css(), $dark);

        return [...$this->variables($light[1]), ...$this->variables($dark[1])];
    }

    private function contrast(string $foreground, string $background, array $palette): float
    {
        $rgb = function (string $name) use ($palette): array {
            $value = $name;

            while (preg_match('/^var\((--[\w-]+)\)$/', $value, $m)) {
                $value = $palette[$m[1]];
            }

            $hex = ltrim($value, '#');

            return array_map('hexdec', str_split(strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex, 2));
        };

        $luminance = function (array $channels): float {
            [$r, $g, $b] = array_map(fn (int $c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $channels);

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        [$a, $b] = [$luminance($rgb($foreground)), $luminance($rgb($background))];

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public function test_dark_theme_text_is_readable_on_its_backgrounds(): void
    {
        $palette = $this->darkPalette();

        $pairs = [
            // [text, background, minimum contrast]
            ['--ink', '--surface', 7], ['--ink', '--bg', 7], ['--ink2', '--surface', 7], ['--muted', '--surface', 4.5],
            ['--muted', '--bg', 4.5], ['--link', '--surface', 4.5], ['--accent-dark', '--accent-soft', 4.5],
            ['--ok', '--ok-bg', 4.5], ['--warn', '--warn-bg', 4.5], ['--err', '--err-bg', 4.5], ['--neutral', '--neutral-bg', 4.5],
            ['--ok', '--surface', 4.5], ['--warn', '--surface', 4.5], ['--err', '--surface', 4.5],
            // The active sidebar item and the calendar's faded days.
            ['--nav-active', '--accent-soft', 7], ['--day-out', '--surface', 3],
        ];

        foreach ($pairs as [$text, $background, $minimum]) {
            $ratio = $this->contrast("var({$text})", "var({$background})", $palette);

            $this->assertGreaterThanOrEqual($minimum, $ratio, sprintf('%s on %s has contrast %.2f in the dark theme (needs %s)', $text, $background, $ratio, $minimum));
        }
    }

    public function test_tailwind_dark_variants_follow_the_apps_theme_switch(): void
    {
        $config = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString("darkMode: ['selector', '[data-theme=\"dark\"]']", $config);
        $this->assertStringNotContainsString('color: var(--navy)', $this->css());
    }

    public function test_checkboxes_are_custom_drawn_and_visible_in_both_themes(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('.check input[type="checkbox"] {', $css);
        $this->assertStringContainsString('appearance: none', $css);
        $this->assertStringContainsString('.check input[type="checkbox"]:checked', $css);
        $this->assertStringContainsString('.check input[type="checkbox"]:focus-visible', $css);
        $this->assertStringContainsString('.check-row .check:has(input:checked)', $css);

        // The empty box outline and the ticked box must stand out from the card in the dark theme.
        $palette = $this->darkPalette();
        $this->assertGreaterThanOrEqual(1.6, $this->contrast('var(--line-strong)', 'var(--surface)', $palette));
        $this->assertGreaterThanOrEqual(3, $this->contrast('var(--accent)', 'var(--surface)', $palette));
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('var(--accent-dark)', 'var(--accent-soft)', $palette));
    }

    public function test_every_checkbox_in_the_views_sits_in_a_styled_label(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (preg_match_all('/<label class="check">\s*<input type="checkbox"/', $file->getContents(), $styled)
                + preg_match_all('/<input type="checkbox"/', $file->getContents(), $all) === 0) {
                continue;
            }

            $this->assertSame(count($all[0]), count($styled[0]), "{$file->getRelativePathname()} has a checkbox outside <label class=\"check\">");
        }
    }

    public function test_form_controls_follow_the_apps_theme_not_the_devices(): void
    {
        $css = $this->css();

        // Native controls (selects, scrollbars) are drawn in the colour scheme of <html>; it must come from our switch.
        $this->assertMatchesRegularExpression('/:root \{\s*color-scheme: light;/', $css);
        $this->assertMatchesRegularExpression('/:root\[data-theme="dark"\] \{\s*color-scheme: dark;/', $css);
        $this->assertStringNotContainsString('select option', $css);

        foreach (['layouts.app', 'layouts.guest'] as $layout) {
            $this->assertStringNotContainsString('name="color-scheme"', file_get_contents(view($layout)->getPath()));
        }
    }

    public function test_the_interface_uses_the_light_minimal_typeface_on_every_layout(): void
    {
        $this->assertStringContainsString("--font: 'Onest'", $this->css());
        $this->assertDoesNotMatchRegularExpression('/font-weight:\s*(700|800|900)|font:\s*(700|800|900)\b/', $this->css(), 'Heavy bold weights make the minimal look noisy');

        foreach (['layouts.app', 'layouts.guest'] as $layout) {
            $source = file_get_contents(view($layout)->getPath());

            $this->assertStringContainsString('family=Onest:wght@300;400;500;600', $source);
            $this->assertStringNotContainsString('Manrope', $source);
        }
    }
}
