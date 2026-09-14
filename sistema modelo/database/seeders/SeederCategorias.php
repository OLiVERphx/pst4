<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeederCategorias extends Seeder
{
    public function run(): void
    {
        $now = now();
        $categories = [
            'Audífonos',
            'Fundas',
            'Cargadores',
            'Cables',
            'Protectores',
            'Baterías',
            'Soportes',
        ];

        $rows = array_map(function ($category) use ($now) {
            return [
                'nombre' => $category,
                'slug' => Str::slug($category),
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $categories);

        DB::table('categories')->insertOrIgnore($rows);
    }
}
