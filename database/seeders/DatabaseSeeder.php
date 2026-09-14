<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Database\Seeders\SeederRoles::class,
            \Database\Seeders\SeederUsuarios::class,
            \Database\Seeders\SeederMarcas::class,
            \Database\Seeders\SeederCategorias::class,
            \Database\Seeders\SeederProductos::class,
            \Database\Seeders\SeederDatosDemo::class,
        ]);
    }
}
