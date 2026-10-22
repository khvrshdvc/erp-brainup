<?php

namespace App\Notifications;

use App\Enums\ShopOrderStatus;
use App\Models\ShopOrder;
use Illuminate\Notifications\Notification;

class ShopOrderUpdated extends Notification
{
    public function __construct(private ShopOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $delivered = $this->order->status === ShopOrderStatus::Delivered;
        $item = $this->order->item?->name ?? 'Mahsulot';
        $note = $this->order->note;

        return [
            'title' => $delivered ? 'Buyurtmangiz topshirildi' : 'Buyurtma bekor qilindi',
            'body' => $delivered
                ? $item
                : $item.' · '.$this->order->price.' BrainCoin hamyoningizga qaytarildi'.($note ? ' · '.$note : ''),
            'body_key' => match (true) {
                $delivered => ':item',
                (bool) $note => ':item · :price BrainCoin hamyoningizga qaytarildi · :note',
                default => ':item · :price BrainCoin hamyoningizga qaytarildi',
            },
            'body_params' => ['item' => $this->order->item?->name ?? __('Mahsulot'), 'price' => $this->order->price, 'note' => $note],
            'url' => route('student.shop.orders', [], false),
        ];
    }
}
