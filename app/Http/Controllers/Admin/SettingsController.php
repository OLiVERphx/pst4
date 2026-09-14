<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('admin.settings.index', compact('config', 'productCount', 'orderCount', 'totalClients'));
    }
}
