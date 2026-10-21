<?php

namespace Database\Seeders;

use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $chirchiq = Branch::firstOrCreate(
            ['name' => 'Chirchiq filiali']
        );

        $this->user('chirchik@erp.test', 'Mahliyo', Role::BranchAdmin, $chirchiq->id);

        $teacher = $this->user('teacher@erp.test', 'Tulqinov Utkirbek', Role::Teacher, null);
        $teacher->branches()->syncWithoutDetaching($chirchiq->id);

        $english = StudyGroup::firstOrCreate(
            ['branch_id' => $chirchiq->id, 'name' => 'English'],
            ['teacher_id' => $teacher->id, 'status' => GroupStatus::Active]
        );


        $chirchiqStudents = [
            ['student@erp.test', 'Asal Eshanova'],
            ['student2@erp.test', 'Dina Tasbaltayeva'],
            ['student3@erp.test', 'Suhbat Abdurahmonov'],
            ['student4@erp.test', 'Ixtiyor Eshanov'],
        ];

        foreach ($chirchiqStudents as [$email, $name]) {
            $student = $this->user($email, $name, Role::Student, $chirchiq->id);
            $english->students()->syncWithoutDetaching([$student->id]);
        }

    }

    private function user(string $email, string $name, Role $role, ?int $branchId): User
    {
        return User::unguarded(fn () => User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'branch_id' => $branchId,
                'email_verified_at' => now(),
            ]
        ));
    }
}
