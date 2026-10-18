<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum GroupStatus: string
{
    use HasValues;

    case Planned = 'planned';
    case Active = 'active';
    case Finished = 'finished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Yangi',
            self::Active => 'Faol',
            self::Finished => 'Yakunlangan',
            self::Archived => 'Arxivlangan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Active => 'ok',
            self::Finished, self::Archived => 'neutral',
        };
    }
}
