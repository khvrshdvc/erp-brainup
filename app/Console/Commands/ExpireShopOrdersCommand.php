<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use App\Services\ShopService;
use Illuminate\Console\Command;

class ExpireShopOrdersCommand extends Command
{
    protected $signature = 'shop:expire-orders';

    protected $description = 'Muddatida olinmagan do‘kon buyurtmalarini bekor qiladi va BrainCoin ni qaytaradi';

    public function handle(ShopService $shop): int
    {
        $count = $shop->expireOverdue();

        $this->info($count.' ta buyurtma bekor qilindi ('.ShopOrder::PICKUP_DAYS.' kundan oshgan)');

        return self::SUCCESS;
    }
}
