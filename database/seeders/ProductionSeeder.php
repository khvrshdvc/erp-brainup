<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * What a fresh production database starts with: levels, XP rules and badges, and the
 * Chirchiq branch with its staff, courses and 13 groups. No demo branches, students,
 * assignments or shop items. Staff get a generated password, printed once, that they
 * must change at first sign-in; existing accounts keep theirs. Students come from the roster:
 *
 *   php artisan db:seed --class=ProductionSeeder --force
 *   php artisan students:import storage/app/private/students-import.csv --branch="Chirchiq filiali"
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $chirchiq = $this->resolve(ChirchiqSeeder::class);
        $chirchiq->structureOnly = true;
        $chirchiq->__invoke();
    }
}
