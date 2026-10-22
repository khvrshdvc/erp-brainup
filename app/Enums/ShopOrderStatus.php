<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ShopOrderStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Kutilmoqda'),
            self::Delivered => __('Topshirildi'),
            self::Cancelled => __('Bekor qilindi'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Delivered => 'ok',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * Statuses whose price is taken from the student's wallet.
     *
     * @return list<string>
     */
    public static function spendingValues(): array
    {
        return [self::Pending->value, self::Delivered->value];
    }
}
