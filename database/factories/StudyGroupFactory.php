<?php

namespace Database\Factories;

use App\Enums\GroupStatus;
use App\Models\StudyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyGroup>
 */
class StudyGroupFactory extends Factory
{
    /**
     * branch_id, course_id and teacher_id must be supplied by the caller.
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->bothify('??-#')),
            'starts_on' => now()->subMonths(2)->startOfMonth(),
            'ends_on' => now()->addMonths(4)->endOfMonth(),
            'capacity' => 20,
            'status' => GroupStatus::Active,
        ];
    }
}
