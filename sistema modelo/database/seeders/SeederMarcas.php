<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeederMarcas extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('brands')->insertOrIgnore([
            ['nombre' => 'Xiaomi', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Samsung', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Apple', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Baseus', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Anker', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Infinix', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Hyundai', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Hi-Treek', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
