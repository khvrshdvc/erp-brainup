<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AssignmentStatus: string
{
    use HasValues;

    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Qoralama',
            self::Published => 'E‘lon qilingan',
            self::Closed => 'Yopilgan',
        };
    }
}
