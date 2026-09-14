<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Seeder para la tabla roles y permisos de Spatie.
 */
class SeederRoles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 1. Opcional: seguimos insertando en la tabla antigua por compatibilidad si es necesario
        // pero la tabla antigua tiene la columna permisos_deprecated ahora.
        DB::table('roles')->updateOrInsert(
            ['id' => 1],
            ['nombre' => 'superadmin', 'permisos_deprecated' => json_encode(['*']), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('roles')->updateOrInsert(
            ['id' => 2],
            ['nombre' => 'admin', 'permisos_deprecated' => json_encode(['orders','products','payments','clients','inventory']), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('roles')->updateOrInsert(
            ['id' => 3],
            ['nombre' => 'cliente', 'permisos_deprecated' => json_encode(['catalog','cart','orders.own']), 'created_at' => $now, 'updated_at' => $now]
        );
        
        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['nombre' => 'vendedor', 'permisos_deprecated' => json_encode(['orders','products','clients','inventory']), 'created_at' => $now, 'updated_at' => $now]
        );

        // Sincronizar con Spatie
        $this->syncWithSpatie();
    }

    // Sincronizar roles con Spatie
    private function syncWithSpatie(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $vendedor = Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        // Definir permisos granulares
        $permisos = [
            'pagos.ver', 'pagos.aprobar', 'pagos.rechazar',
            'inventario.ver', 'inventario.ajustar',
            'pedidos.ver', 'pedidos.crear', 'pedidos.anular',
            'clientes.ver', 'clientes.editar',
            'reportes.ver',
            'auditoria.ver',
            'configuracion.editar',
            'productos.ver', 'productos.editar'
        ];

        // Crear todos los permisos
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Asignar TODO a superadmin y admin
        $superadmin->syncPermissions(Permission::all());
        $admin->syncPermissions(Permission::all());

        // Asignar permisos específicos a vendedor
        $vendedor->syncPermissions([
            'pedidos.crear', 
            'pedidos.ver', 
            'inventario.ver', 
            'productos.ver', 
            'clientes.ver'
        ]);
    }
}
