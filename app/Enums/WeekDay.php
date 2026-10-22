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
            self::Monday => __('Dushanba'),
            self::Tuesday => __('Seshanba'),
            self::Wednesday => __('Chorshanba'),
            self::Thursday => __('Payshanba'),
            self::Friday => __('Juma'),
            self::Saturday => __('Shanba'),
            self::Sunday => __('Yakshanba'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Monday => __('Du'),
            self::Tuesday => __('Se'),
            self::Wednesday => __('Ch'),
            self::Thursday => __('Pa'),
            self::Friday => __('Ju'),
            self::Saturday => __('Sh'),
            self::Sunday => __('Ya'),
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
