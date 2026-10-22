<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SubmissionStatus: string
{
    use HasValues;

    case Submitted = 'submitted';
    case Graded = 'graded';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => __('Tekshirilmoqda'),
            self::Graded => __('Tekshirildi'),
            self::Returned => __('Qayta topshirish kerak'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Submitted => 'warn',
            self::Graded => 'ok',
            self::Returned => 'err',
        };
    }
}
