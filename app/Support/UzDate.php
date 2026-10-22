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

    /**
     * Time left until the given moment, e.g. "2 kun 5 soat" or "40 daqiqa".
     */
    public static function remaining(CarbonInterface $until): string
    {
        $minutes = max(0, (int) floor(now()->diffInMinutes($until, false)));

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $rest = $minutes % 60;

        $parts = match (true) {
            $days > 0 => [$days.' kun', $hours ? $hours.' soat' : null],
            $hours > 0 => [$hours.' soat', $rest ? $rest.' daqiqa' : null],
            default => [max(1, $rest).' daqiqa'],
        };

        return implode(' ', array_filter($parts));
    }
}
