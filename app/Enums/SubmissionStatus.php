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
            self::Submitted => 'Tekshirilmoqda',
            self::Graded => 'Baholangan',
            self::Returned => 'Qayta topshirish uchun qaytarilgan',
        };
    }
}
