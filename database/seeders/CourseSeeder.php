<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['name' => 'Ingliz tili', 'slug' => 'english', 'duration_months' => 6],
            ['name' => 'Ingliz tili (ru)', 'slug' => 'english-ru', 'duration_months' => 9],
            ['name' => 'Frontend dasturlash', 'slug' => 'frontend', 'duration_months' => 8],
            ['name' => 'Matematika', 'slug' => 'math', 'duration_months' => 6],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(['slug' => $course['slug']], $course);
        }
    }
}
