<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum XpReason: string
{
    use HasValues;

    case Attendance = 'attendance';
    case StreakBonus = 'streak_bonus';
    case Assignment = 'assignment';
}
