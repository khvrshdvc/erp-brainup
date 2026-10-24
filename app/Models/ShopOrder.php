<?php

namespace App\Models;

use App\Enums\ShopOrderStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrder extends Model
{
    use BelongsToBranch;

    /** A pending order not handed over within this many days is cancelled and refunded. */
    public const PICKUP_DAYS = 3;

    protected $fillable = ['branch_id', 'user_id', 'shop_item_id', 'price', 'status', 'handled_by', 'handled_at', 'note'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'status' => ShopOrderStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'shop_item_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === ShopOrderStatus::Pending;
    }
}
