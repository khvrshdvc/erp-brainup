<?php

namespace Database\Seeders;

use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'BrainUp ruchkasi', 'description' => 'Logotipli ko‘k ruchka', 'price' => 60, 'stock' => 100],
            ['name' => 'BrainUp daftari', 'description' => '96 varaqli, katakli daftar', 'price' => 120, 'stock' => 60],
            ['name' => 'Stiker to‘plami', 'description' => '10 ta BrainUp stikeri', 'price' => 80, 'stock' => 80],
            ['name' => 'Kitob', 'description' => 'Kursingizga mos qo‘shimcha kitob', 'price' => 450, 'stock' => 15],
            ['name' => 'Brendli futbolka', 'description' => 'O‘lchamni administratorga ayting', 'price' => 900, 'stock' => 20],
            ['name' => 'Bepul qo‘shimcha dars', 'description' => 'O‘qituvchi bilan 1 soatlik individual dars', 'price' => 700, 'stock' => null],
        ];

        foreach ($items as $item) {
            ShopItem::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
