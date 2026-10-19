<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Services\ServicioAuditoria;
use App\Services\ServicioBusquedaInteligente;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        $config = \App\Models\Configuracion::first();
        $productCount = Product::count();
        $orderCount = Order::count();
        $totalClients = DB::table('users')
            ->join('roles', 'users.rol_id', '=', 'roles.id')
            ->where('roles.nombre', 'cliente')
            ->count();

        $conexion = DB::connection();
        $baseDeDatos = $conexion->getDriverTitle();
        try {
            $baseDeDatos .= ' ' . $conexion->getServerVersion();
        } catch (\Throwable) {
            // Algunos drivers no informan la versión
        }

        return view('admin.settings.index', compact('config', 'productCount', 'orderCount', 'totalClients', 'baseDeDatos'));
    }

    /**
     * Borra la caché de la aplicación (tasas, reportes de analítica, índice de búsqueda)
     * y las vistas compiladas. No toca sesiones ni la configuración.
     */
    public function limpiarCache()
    {
        Cache::flush();
        Artisan::call('view:clear');

        // El índice de búsqueda se reconstruye ya, para que la tienda no lo haga en la próxima búsqueda
        try {
            app(ServicioBusquedaInteligente::class)->buildIndex();
        } catch (\Throwable $e) {
            Log::warning('No se pudo reconstruir el índice de búsqueda: ' . $e->getMessage());
        }

        ServicioAuditoria::registrar('sistema.cache_limpiada');

        return back()->with('success', 'Caché limpiada. Los datos se vuelven a calcular en la próxima consulta.');
    }
}
