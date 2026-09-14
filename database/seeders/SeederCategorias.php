<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder para categorias.
 */
class SeederCategorias extends Seeder
{
    public function run(): void
    {
        $now = now();
        $names = ['Audífonos', 'Fundas', 'Cargadores', 'Cables', 'Protectores', 'Baterías', 'Soportes'];
        $insert = [];
        foreach ($names as $name) {
            $insert[] = [
                'padre_id' => null,
                'nombre' => $name,
                'slug' => Str::slug($name),
                'icono' => null,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('categorias')->insertOrIgnore($insert);
    }
}
