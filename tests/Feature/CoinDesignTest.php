<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinDesignTest extends TestCase
{
    use RefreshDatabase;

    private function student(int $coins): User
    {
        return User::factory()->student()->inBranch(Branch::create(['name' => 'A']))->create(['xp' => $coins]);
    }

    public function test_the_coin_gradients_are_defined_once_on_every_app_page(): void
    {
        $page = $this->actingAs($this->student(120))->get(route('student.shop.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($page, 'id="bc-rim"'));
        $this->assertSame(1, substr_count($page, 'id="bc-face"'));
        $this->assertStringContainsString('fill="url(#bc-rim)"', $page);
        $this->assertStringContainsString('fill="url(#bc-face)"', $page);
    }

    public function test_the_wallet_shows_an_animated_coin_with_a_counting_balance(): void
    {
        $this->actingAs($this->student(1250))->get(route('student.shop.index'))
            ->assertOk()
            ->assertSee('class="card wallet"', false)
            ->assertSee('coin coin-xl', false)
            ->assertSee('<b data-count-up="1250">1 250</b>', false);
    }

    public function test_the_dashboard_balance_card_uses_the_coin_and_counts_up(): void
    {
        $this->actingAs($this->student(300))->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('class="coin-mark"', false)
            ->assertSee('data-count-up="300"', false);
    }

    public function test_prices_in_lists_get_a_still_coin_without_counting(): void
    {
        ShopItem::factory()->create(['name' => 'Notebook', 'price' => 99]);

        $page = $this->actingAs($this->student(500))->get(route('student.shop.index'))->assertOk()->getContent();

        $this->assertStringContainsString('coin coin-lg', $page);
        $this->assertStringNotContainsString('data-count-up="99"', $page);
    }

    public function test_the_profile_shows_the_coin_next_to_the_balance(): void
    {
        $this->actingAs($this->student(40))->get(route('profile.edit'))->assertOk()->assertSee('coin-stat', false);
    }

    public function test_the_animations_exist_and_respect_reduced_motion(): void
    {
        $css = file_get_contents(public_path('css/erp.css'));

        foreach (['coin-shine', 'coin-flip', 'coin-pop', 'coin-float', 'coin-twinkle'] as $animation) {
            $this->assertStringContainsString("@keyframes {$animation}", $css);
        }

        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: reduce\) \{[^@]*\.coin-shine[^@]*animation: none !important/s', $css);

        $js = file_get_contents(public_path('js/erp.js'));
        $this->assertStringContainsString('data-count-up', $js);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $js);
    }
}
