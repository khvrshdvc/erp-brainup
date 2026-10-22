<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserStatus: string
{
    use HasValues;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Faol'),
            self::Inactive => __('To‘xtatilgan'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'ok',
            self::Inactive => 'warn',
        };
    }
}
