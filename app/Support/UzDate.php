<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Date and duration text in the interface language (the class keeps its original name; texts
 * come from lang/{locale}/dates.php, Uzbek being the default).
 */
class UzDate
{
    public static function long(CarbonInterface $date): string
    {
        return __('dates.long', [
            'weekday' => trans('dates.days')[$date->dayOfWeek],
            'day_month' => self::dayMonth($date),
            'year' => $date->year,
        ]);
    }

    public static function dayMonth(CarbonInterface $date): string
    {
        return __('dates.day_month', ['day' => $date->day, 'month' => trans('dates.months')[$date->month - 1]]);
    }

    public static function shortDay(CarbonInterface $date): string
    {
        return trans('dates.days_short')[$date->dayOfWeek];
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
            $days > 0 => [self::unit('day', $days), $hours ? self::unit('hour', $hours) : null],
            $hours > 0 => [self::unit('hour', $hours), $rest ? self::unit('minute', $rest) : null],
            default => [self::unit('minute', max(1, $rest))],
        };

        return implode(' ', array_filter($parts));
    }

    private static function unit(string $unit, int $n): string
    {
        return __('dates.'.$unit, ['n' => $n]);
    }
}
