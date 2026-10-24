<?php

namespace App\Notifications;

use App\Models\ShopOrder;
use Illuminate\Notifications\Notification;

class ShopOrderPlaced extends Notification
{
    public function __construct(private ShopOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Do‘kondan yangi buyurtma',
            'body' => ($this->order->student?->name ?? 'O‘quvchi').' · '
                .($this->order->item?->name ?? 'Mahsulot').' · '
                .$this->order->price.' BrainCoin',
            'body_key' => ':student · :item · :price BrainCoin',
            'body_params' => [
                'student' => $this->order->student?->name ?? __('O‘quvchi'),
                'item' => $this->order->item?->name ?? __('Mahsulot'),
                'price' => $this->order->price,
            ],
            'order_id' => $this->order->id,
            'url' => route('admin.shop.orders.index', [], false),
        ];
    }
}
