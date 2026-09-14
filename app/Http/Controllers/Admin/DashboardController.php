<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ServicioDashboard;
use App\Models\Order;

class DashboardController extends Controller
{
    /**
     * Mostrar dashboard con estadísticas.
     */
    public function index(ServicioDashboard $dashboardService)
    {
        $stats = $dashboardService->obtenerEstadisticas();
        $lastOrders = Order::with('user')->orderByDesc('created_at')->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'lastOrders'));
    }
}
