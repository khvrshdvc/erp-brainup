<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['name' => 'IELTS', 'slug' => 'ielts', 'duration_months' => 6],
            ['name' => 'General English', 'slug' => 'general-english', 'duration_months' => 9],
            ['name' => 'Frontend dasturlash', 'slug' => 'frontend', 'duration_months' => 8],
            ['name' => 'Python', 'slug' => 'python', 'duration_months' => 6],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(['slug' => $course['slug']], $course);
        }
    }
}
