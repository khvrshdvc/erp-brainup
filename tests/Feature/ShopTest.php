<?php

namespace Tests\Feature;

use App\Enums\ShopOrderStatus;
use App\Models\Branch;
use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'A']);
    }

    private function student(int $coins): User
    {
        return User::factory()->student()->inBranch($this->branch)->create(['xp' => $coins]);
    }

    public function test_student_buys_an_item_and_coins_are_reserved(): void
    {
        $student = $this->student(300);
        $item = ShopItem::factory()->create(['price' => 120, 'stock' => 2]);

        $this->actingAs($student)->get(route('student.shop.index'))->assertOk()->assertSee($item->name);

        $this->actingAs($student)->post(route('student.shop.buy', $item))
            ->assertRedirect(route('student.shop.orders'));

        $this->assertSame(180, $student->fresh()->coinBalance());
        $this->assertSame(300, $student->fresh()->xp, 'earned total (level, leaderboard) must not drop');
        $this->assertSame(1, $item->fresh()->stock);
        $this->assertDatabaseHas('shop_orders', [
            'user_id' => $student->id,
            'shop_item_id' => $item->id,
            'price' => 120,
            'status' => ShopOrderStatus::Pending->value,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_cannot_buy_without_enough_coins_or_when_out_of_stock(): void
    {
        $student = $this->student(100);
        $expensive = ShopItem::factory()->create(['price' => 120]);
        $soldOut = ShopItem::factory()->create(['price' => 10, 'stock' => 0]);

        $this->actingAs($student)->post(route('student.shop.buy', $expensive))
            ->assertSessionHasErrors(['item' => 'BrainCoin yetarli emas.']);

        $this->actingAs($student)->post(route('student.shop.buy', $soldOut))
            ->assertSessionHasErrors(['item' => 'Bu mahsulot tugagan.']);

        $this->assertSame(0, ShopOrder::count());
    }

    public function test_cancelling_refunds_coins_and_stock(): void
    {
        $student = $this->student(200);
        $item = ShopItem::factory()->create(['price' => 150, 'stock' => 5]);

        $this->actingAs($student)->post(route('student.shop.buy', $item));
        $order = ShopOrder::firstOrFail();

        $this->actingAs($student)->delete(route('student.shop.orders.cancel', $order))->assertRedirect();

        $this->assertSame(ShopOrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(200, $student->fresh()->coinBalance());
        $this->assertSame(5, $item->fresh()->stock);
    }

    public function test_branch_admin_hands_over_an_order_and_student_is_notified(): void
    {
        $student = $this->student(500);
        $admin = User::factory()->branchAdmin()->inBranch($this->branch)->create();
        $item = ShopItem::factory()->unlimited()->create(['price' => 100]);

        $this->actingAs($student)->post(route('student.shop.buy', $item));
        $order = ShopOrder::firstOrFail();

        $this->actingAs($admin)->get(route('admin.shop.orders.index'))->assertOk()->assertSee($student->name);
        $this->actingAs($admin)->patch(route('admin.shop.orders.deliver', $order))->assertRedirect();

        $this->assertSame(ShopOrderStatus::Delivered, $order->fresh()->status);
        $this->assertSame(400, $student->fresh()->coinBalance(), 'delivered orders stay spent');
        $this->assertSame(1, $student->notifications()->count());

        // A closed order can't be handled again.
        $this->actingAs($admin)->patch(route('admin.shop.orders.cancel', $order))->assertForbidden();
    }

    public function test_admin_of_another_branch_cannot_handle_the_order(): void
    {
        $student = $this->student(500);
        $other = Branch::create(['name' => 'B']);
        $admin = User::factory()->branchAdmin()->inBranch($other)->create();
        $item = ShopItem::factory()->create(['price' => 100]);

        $this->actingAs($student)->post(route('student.shop.buy', $item));
        $order = ShopOrder::firstOrFail();

        // The order is outside the admin's branch, so it isn't even found.
        $this->actingAs($admin)->patch(route('admin.shop.orders.deliver', $order))->assertNotFound();
    }

    public function test_only_super_admin_manages_items(): void
    {
        Storage::fake('local');

        $super = User::factory()->superAdmin()->create();
        $branchAdmin = User::factory()->branchAdmin()->inBranch($this->branch)->create();

        $this->actingAs($branchAdmin)->get(route('admin.shop-items.index'))->assertForbidden();

        $this->actingAs($super)->post(route('admin.shop-items.store'), [
            'name' => 'Daftar',
            'price' => 80,
            'stock' => '',
            'image' => UploadedFile::fake()->image('daftar.png'),
            'is_active' => '1',
        ])->assertRedirect(route('admin.shop-items.index'));

        $item = ShopItem::firstOrFail();
        $this->assertNull($item->stock, 'empty stock means unlimited');
        Storage::disk('local')->assertExists($item->image_path);

        $this->actingAs($super)->get(route('shop.items.image', $item))->assertOk();
    }

    public function test_branch_admins_of_that_branch_are_notified_about_a_new_order(): void
    {
        $student = $this->student(500);
        $admin = User::factory()->branchAdmin()->inBranch($this->branch)->create();
        $otherAdmin = User::factory()->branchAdmin()->inBranch(Branch::create(['name' => 'B']))->create();
        $item = ShopItem::factory()->create(['name' => 'Daftar', 'price' => 100]);

        $this->actingAs($student)->post(route('student.shop.buy', $item));

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('Do‘kondan yangi buyurtma', $admin->notifications()->first()->data['title']);
        $this->assertStringContainsString('Daftar', $admin->notifications()->first()->data['body']);
        $this->assertSame(0, $otherAdmin->notifications()->count());
    }

    public function test_braincoin_keeps_its_spelling_in_uppercase_labels(): void
    {
        $student = $this->student(100);

        $this->actingAs($student)->get(route('student.shop.index'))
            ->assertOk()
            ->assertSee('<span class="brand-name">BrainCoin</span>', false);
    }
}
