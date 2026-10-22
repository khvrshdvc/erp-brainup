<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
