<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The stylesheet and script are versioned by their content. A version based on the file time could stay the same
 * across two quick edits, and a browser that cached a half-written copy then kept showing an unstyled page
 * (raw HTML and an oversized coin).
 */
class AssetVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_pages_load_the_current_stylesheet_and_script(): void
    {
        $student = User::factory()->student()->inBranch(Branch::create(['name' => 'A']))->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('css/erp.css?v='.hash_file('xxh3', public_path('css/erp.css')), false)
            ->assertSee('js/erp.js?v='.hash_file('xxh3', public_path('js/erp.js')), false);
    }

    public function test_login_page_loads_the_current_stylesheet(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/erp.css?v='.hash_file('xxh3', public_path('css/erp.css')), false);
    }

    public function test_the_coin_has_a_natural_size_even_without_the_stylesheet(): void
    {
        $student = User::factory()->student()->inBranch(Branch::create(['name' => 'B']))->create(['xp' => 50]);

        $this->actingAs($student)->get(route('student.shop.index'))
            ->assertSee('<svg viewBox="0 0 32 32" width="18" height="18"', false);
    }

    public function test_the_sidebar_logo_is_part_of_the_page_and_does_not_pop_in(): void
    {
        $student = User::factory()->student()->inBranch(Branch::create(['name' => 'C']))->create();

        $html = $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('<img src="data:image/webp;base64,', $html);
        $this->assertStringContainsString('width="180" height="64"', $html);
        $this->assertStringNotContainsString('images/logo-brainup-white.png', $html);

        // Small enough to inline on every page.
        $this->assertLessThan(15_000, filesize(public_path('images/logo-brainup-white.webp')));
        $this->assertLessThan(15_000, filesize(public_path('images/logo-brainup-dark.webp')));
    }
}
