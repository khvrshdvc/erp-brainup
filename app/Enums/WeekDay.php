<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum WeekDay: string
{
    use HasValues;

    case Monday = 'mon';
    case Tuesday = 'tue';
    case Wednesday = 'wed';
    case Thursday = 'thu';
    case Friday = 'fri';
    case Saturday = 'sat';
    case Sunday = 'sun';

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Dushanba',
            self::Tuesday => 'Seshanba',
            self::Wednesday => 'Chorshanba',
            self::Thursday => 'Payshanba',
            self::Friday => 'Juma',
            self::Saturday => 'Shanba',
            self::Sunday => 'Yakshanba',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Monday => 'Du',
            self::Tuesday => 'Se',
            self::Wednesday => 'Ch',
            self::Thursday => 'Pa',
            self::Friday => 'Ju',
            self::Saturday => 'Sh',
            self::Sunday => 'Ya',
        };
    }

    /**
     * @return array<string, string> e.g. ['mon' => 'Dushanba', ...]
     */
    public static function labels(): array
    {
        return array_column(array_map(fn (self $day) => [$day->value, $day->label()], self::cases()), 1, 0);
    }

    /**
     * @return array<string, string> e.g. ['mon' => 'Du', ...]
     */
    public static function shortLabels(): array
    {
        return array_column(array_map(fn (self $day) => [$day->value, $day->shortLabel()], self::cases()), 1, 0);
    }
}
