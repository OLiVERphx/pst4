<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;

class SeederRoles extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            ['nombre' => 'superadmin', 'permisos' => json_encode(['*']), 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'admin', 'permisos' => json_encode(['orders', 'products', 'payments', 'clients', 'inventory']), 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'cliente', 'permisos' => json_encode(['catalog', 'cart', 'orders.own']), 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (class_exists(SpatieRole::class)) {
            $roles = [
                'superadmin' => ['*'],
                'admin' => ['orders', 'products', 'payments', 'clients', 'inventory'],
                'cliente' => ['catalog', 'cart', 'orders.own'],
            ];

            foreach ($roles as $name => $permissions) {
                $role = SpatieRole::firstOrCreate(['name' => $name]);

                if ($name !== 'superadmin') {
                    $permissionModels = [];
                    foreach ($permissions as $permission) {
                        $permissionModels[] = Permission::firstOrCreate(['name' => $permission]);
                    }
                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }
}
