<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LoadTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoadTestSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedSmall(): void
    {
        app(LoadTestSeeder::class)
            ->setContainer(app())
            ->configure(branches: 2, teachers: 4, students: 40, groups: 6, days: 30, assignmentsPerGroup: 3)
            ->run();
    }

    public function test_it_builds_a_consistent_data_set(): void
    {
        $this->seedSmall();

        $this->assertSame(2, DB::table('branches')->where('name', 'like', 'Sinov filiali%')->count());
        $this->assertSame(4, DB::table('users')->where('role', Role::Teacher->value)->count());
        $this->assertSame(40, DB::table('users')->where('role', Role::Student->value)->count());
        $this->assertSame(6, DB::table('study_groups')->count());
        $this->assertSame(18, DB::table('assignments')->count());
        $this->assertGreaterThan(0, DB::table('attendances')->count());
        $this->assertGreaterThan(0, DB::table('submissions')->count());

        // Every student sits in a group of their own branch, and the group stays within capacity.
        $this->assertSame(0, DB::table('group_student')
            ->join('users', 'users.id', '=', 'group_student.student_id')
            ->join('study_groups', 'study_groups.id', '=', 'group_student.study_group_id')
            ->whereColumn('users.branch_id', '!=', 'study_groups.branch_id')
            ->count());
        $this->assertSame(0, DB::table('study_groups')
            ->whereRaw('(select count(*) from group_student where study_group_id = study_groups.id) > capacity')
            ->count());

        // Attendance and submissions only exist for enrolled students, and never in the future.
        $this->assertSame(0, DB::table('attendances')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('group_student')
                ->whereColumn('group_student.study_group_id', 'attendances.study_group_id')
                ->whereColumn('group_student.student_id', 'attendances.student_id'))
            ->count());
        $this->assertSame(0, DB::table('attendances')->where('lesson_date', '>=', today()->toDateString())->count());
        $this->assertSame(0, DB::table('submissions')->where('submitted_at', '>', now()->addMinute())->count());
    }

    public function test_it_does_nothing_when_run_twice(): void
    {
        $this->seedSmall();
        $students = DB::table('users')->count();

        $this->seedSmall();

        $this->assertSame($students, DB::table('users')->count());
    }

    public function test_it_is_not_part_of_the_default_seeding(): void
    {
        $this->assertStringNotContainsString('LoadTestSeeder', file_get_contents((new \ReflectionClass(DatabaseSeeder::class))->getFileName()));
    }
}
