<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum Role: string
{
    use HasValues;

    case SuperAdmin = 'super_admin';
    case BranchAdmin = 'branch_admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super admin'),
            self::BranchAdmin => __('Filial admini'),
            self::Teacher => __('O‘qituvchi'),
            self::Student => __('O‘quvchi'),
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
