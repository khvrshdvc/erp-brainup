<?php

namespace App\Support;

/**
 * A student's attendance in one month across all of their groups, built by LessonPlan.
 */
final readonly class MonthAttendance
{
    public function __construct(
        public int $key,
        public int $expected,
        public int $present,
        public int $late,
        public int $missed,
        public int $groups,
        public ?int $branchId,
    ) {}

    /**
     * The student's share of the monthly coins; a late arrival counts as half a lesson.
     */
    public function share(int $pool): int
    {
        if ($this->expected === 0) {
            return 0;
        }

        return min($pool, intdiv($pool * (2 * $this->present + $this->late), 2 * $this->expected));
    }

    /**
     * Every expected lesson has been marked and none of them was missed.
     */
    public function isPerfect(): bool
    {
        return $this->expected > 0
            && $this->missed === 0
            && $this->present + $this->late >= $this->expected;
    }
}
