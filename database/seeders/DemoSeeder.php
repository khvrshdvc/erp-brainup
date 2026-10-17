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
        $chilonzor = Branch::where('name', 'Chilonzor filiali')->firstOrFail();
        $yunusobod = Branch::where('name', 'Yunusobod filiali')->firstOrFail();

        $this->user('filial@erp.test', 'Aziza Qodirova', Role::BranchAdmin, $chilonzor->id);

        $teacher = $this->user('teacher@erp.test', 'Jasur Karimov', Role::Teacher, null);
        $teacher->branches()->syncWithoutDetaching([$chilonzor->id, $yunusobod->id]);

        $laravel = StudyGroup::firstOrCreate(
            ['branch_id' => $chilonzor->id, 'name' => 'Laravel 1'],
            ['teacher_id' => $teacher->id, 'status' => GroupStatus::Active]
        );

        $python = StudyGroup::firstOrCreate(
            ['branch_id' => $yunusobod->id, 'name' => 'Python 1'],
            ['teacher_id' => $teacher->id, 'status' => GroupStatus::Active]
        );

        $chilonzorStudents = [
            ['student@erp.test', 'Dilnoza Nazarova'],
            ['student2@erp.test', 'Madina Yusupova'],
            ['student3@erp.test', 'Sardor Aliyev'],
        ];

        foreach ($chilonzorStudents as [$email, $name]) {
            $student = $this->user($email, $name, Role::Student, $chilonzor->id);
            $laravel->students()->syncWithoutDetaching([$student->id]);
        }

        $yunusobodStudents = [
            ['student4@erp.test', 'Azizbek Toshmatov'],
            ['student5@erp.test', 'Otabek Karimov'],
        ];

        foreach ($yunusobodStudents as [$email, $name]) {
            $student = $this->user($email, $name, Role::Student, $yunusobod->id);
            $python->students()->syncWithoutDetaching([$student->id]);
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
