<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrar usuarios existentes a los roles de Spatie
        // Usamos DB facade o el modelo, preferimos DB o consultas masivas para evitar problemas de eventos si es posible
        // Pero Spatie usa modelos para assignRole, asi que lo haremos de forma segura:
        $users = User::with('role')->get();

        foreach ($users as $user) {
            if ($user->role && !empty($user->role->nombre)) {
                $roleName = strtolower($user->role->nombre);
                // Asegurarse de que el rol exista en Spatie
                if (SpatieRole::where('name', $roleName)->where('guard_name', 'web')->exists()) {
                    $user->assignRole($roleName);
                }
            }
        }

        // 2. Marcar la columna `permisos` de la tabla antigua `roles` como deprecated
        if (Schema::hasColumn('roles', 'permisos')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->renameColumn('permisos', 'permisos_deprecated');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('roles', 'permisos_deprecated')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->renameColumn('permisos_deprecated', 'permisos');
            });
        }
    }
};
