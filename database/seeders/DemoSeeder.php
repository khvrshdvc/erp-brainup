<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Course;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data built with factories: 1 super admin, 1 branch admin, 4 teachers and
 * 200 students in one branch, spread over 12 study groups. All passwords are "password".
 * Run it once on a fresh database; it does nothing if demo students already exist.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::students()->exists()) {
            return;
        }

        DB::transaction(function () {
            $branch = Branch::firstOrCreate(['name' => 'Chirchiq filiali'], ['is_active' => true]);

            // BranchSeeder may already have created the super admin.
            User::where('email', 'admin@erp.test')->exists()
                || User::factory()->superAdmin()->create(['name' => 'Super Admin', 'email' => 'admin@erp.test']);

            User::factory()->branchAdmin()->inBranch($branch)->create([
                'name' => 'Karimov Sherzod',
                'email' => 'filial.admin@brainup.uz',
            ]);

            $teachers = collect([
                'robia' => ['Yunusova Robiya', 'robia@brainup.uz'],
                'sabina' => ['Sabina Axmedova', 'sabina@brainup.uz'],
                'utkirbek' => ['Tulqinov Utkirbek', 'utkirbek@brainup.uz'],
                'olimjon' => ['Mamurov Olimjon', 'olimjon@brainup.uz'],
            ])->map(function (array $data) use ($branch) {
                [$name, $email] = $data;

                $teacher = User::factory()->teacher()->inBranch($branch)->create(['name' => $name, 'email' => $email]);
                $teacher->branches()->attach($branch->id);

                return $teacher;
            });

            // [group, course slug, teacher, students]; student counts add up to 200.
            $groups = [
                ['ER-1', 'english', 'robia', 18],
                ['ER-2', 'english', 'robia', 18],
                ['ES-1', 'english-ru', 'sabina', 16],
                ['EU-1', 'english', 'utkirbek', 14],
                ['EU-2', 'english', 'utkirbek', 18],
                ['EU-3', 'english', 'utkirbek', 18],
                ['EU-4', 'english', 'utkirbek', 18],
                ['FU-1', 'frontend', 'utkirbek', 18],
                ['FU-2', 'frontend', 'utkirbek', 12],
                ['FU-3', 'frontend', 'utkirbek', 18],
                ['MO-1', 'math', 'olimjon', 16],
                ['MO-2', 'math', 'olimjon', 16],
            ];

            foreach ($groups as [$name, $courseSlug, $teacher, $studentCount]) {
                StudyGroup::factory()
                    ->hasAttached(
                        User::factory()->count($studentCount)->student()->inBranch($branch),
                        [],
                        'students'
                    )
                    ->create([
                        'branch_id' => $branch->id,
                        'course_id' => Course::where('slug', $courseSlug)->firstOrFail()->id,
                        'teacher_id' => $teachers[$teacher]->id,
                        'name' => $name,
                    ]);
            }
        });
    }
}
