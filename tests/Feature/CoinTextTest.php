<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use App\Support\CoinText;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class CoinTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_word_becomes_the_coin_and_the_rest_is_escaped(): void
    {
        $html = CoinText::render('<b>50</b> BrainCoin qoldi')->toHtml();

        $this->assertStringStartsWith('&lt;b&gt;50&lt;/b&gt; <span class="coin-inline" title="BrainCoin">', $html);
        $this->assertStringContainsString('class="coin-mark"', $html);
        $this->assertStringContainsString('<span class="sr-only">BrainCoin</span>', $html);
        $this->assertStringEndsWith('</span> qoldi', $html);
    }

    public function test_text_without_the_word_is_only_escaped(): void
    {
        $this->assertSame('Toza &amp; oddiy', CoinText::render('Toza & oddiy')->toHtml());
        $this->assertSame('', CoinText::render(null)->toHtml());
        $this->assertSame('<i>tayyor</i>', CoinText::render(new HtmlString('<i>tayyor</i>'))->toHtml());
    }

    public function test_pages_show_the_coin_instead_of_the_word(): void
    {
        $this->seed(GamificationSeeder::class);
        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => Branch::create(['name' => 'A'])->id, 'xp' => 120]);

        foreach (['student.achievements', 'student.shop.index'] as $route) {
            $html = $this->actingAs($student)->get(route($route))->assertOk()->getContent();
            $visible = preg_replace(['#<title>.*?</title>#s', '#<span class="sr-only">BrainCoin</span>#', '#title="BrainCoin"#', '#<script\b.*?</script>#s', '#<!--.*?-->#s'], '', $html);

            $this->assertStringContainsString('coin-inline', $html, $route);
            $this->assertDoesNotMatchRegularExpression('/>[^<]*\bBrainCoin\b[^<]*</', $visible, "The word BrainCoin is still printed on {$route}");
        }
    }

    public function test_admin_pages_and_flash_messages_use_the_coin_too(): void
    {
        $this->seed(GamificationSeeder::class);
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.gamification.index'))->assertOk()->assertSee('coin-inline', false);

        $html = $this->actingAs($admin)->withSession(['status' => 'Buyurtma bekor qilindi, BrainCoin o‘quvchiga qaytarildi'])
            ->get(route('admin.dashboard'))->getContent();

        $this->assertMatchesRegularExpression('/<div class="alert alert-ok">Buyurtma bekor qilindi, <span class="coin-inline"/', $html);
    }
}
