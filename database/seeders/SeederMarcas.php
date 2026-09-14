<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para marcas.
 */
class SeederMarcas extends Seeder
{
    public function run(): void
    {
        $now = now();
        $brands = ['Xiaomi', 'Samsung', 'Apple', 'Baseus', 'Anker', 'Infinix', 'Hyundai', 'Hi-Treek'];
        $insert = [];
        foreach ($brands as $b) {
            $insert[] = ['nombre' => $b, 'logo' => null, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('marcas')->insertOrIgnore($insert);
    }
}
