<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum XpReason: string
{
    use HasValues;

    case Attendance = 'attendance';
    case StreakBonus = 'streak_bonus';
    case Assignment = 'assignment';
    case AssignmentScore = 'assignment_score';
    case AttendanceMonth = 'attendance_month';
    case PerfectMonth = 'perfect_month';
}
