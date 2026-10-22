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
            self::Planned => __('Yangi'),
            self::Active => __('Faol'),
            self::Finished => __('Yakunlangan'),
            self::Archived => __('Arxivlangan'),
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
