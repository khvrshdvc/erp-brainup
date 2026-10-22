<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AttendanceStatus: string
{
    use HasValues;

    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Excused = 'excused';

    public function isAttended(): bool
    {
        return in_array($this, self::attended(), true);
    }

    public static function attended(): array
    {
        return [self::Present, self::Late];
    }

    public static function attendedValues(): array
    {
        return array_map(fn (self $status) => $status->value, self::attended());
    }

    public function label(): string
    {
        return match ($this) {
            self::Present => __('Keldi'),
            self::Late => __('Kechikdi'),
            self::Absent => __('Kelmadi'),
            self::Excused => __('Sababli'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Present => 'ok',
            self::Late => 'warn',
            self::Absent => 'err',
            self::Excused => 'info',
        };
    }
}
