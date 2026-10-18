<?php

namespace App\Support;

use Carbon\CarbonInterface;

class UzDate
{
    private const DAYS = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

    private const SHORT_DAYS = ['Ya', 'Du', 'Se', 'Ch', 'Pa', 'Ju', 'Sh'];

    private const MONTHS = [
        'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun',
        'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr',
    ];

    public static function long(CarbonInterface $date): string
    {
        return sprintf('%s, %s %d', self::DAYS[$date->dayOfWeek], self::dayMonth($date), $date->year);
    }

    public static function dayMonth(CarbonInterface $date): string
    {
        return sprintf('%d-%s', $date->day, self::MONTHS[$date->month - 1]);
    }

    public static function shortDay(CarbonInterface $date): string
    {
        return self::SHORT_DAYS[$date->dayOfWeek];
    }
}
