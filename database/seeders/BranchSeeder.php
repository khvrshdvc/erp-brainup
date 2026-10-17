<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Chilonzor filiali', 'address' => 'Chilonzor, 9-kvartal', 'manager_name' => 'Aziza Qodirova'],
            ['name' => 'Yunusobod filiali', 'address' => 'Yunusobod, 4-mavze', 'manager_name' => 'Rustam Ergashev'],
            ['name' => 'Mirzo Ulug‘bek filiali', 'address' => 'Mirzo Ulug‘bek tumani', 'manager_name' => 'Nigora Tursunova'],
            ['name' => 'Sergeli filiali', 'address' => 'Yangi Sergeli', 'manager_name' => 'Bekzod Ismoilov'],
        ];

        foreach ($branches as $data) {
            Branch::firstOrCreate(['name' => $data['name']], $data);
        }

        User::firstOrCreate(
            ['email' => 'admin@erp.test'],
            ['name' => 'Super Admin', 'password' => 'password', 'role' => Role::SuperAdmin]
        );
    }
}
