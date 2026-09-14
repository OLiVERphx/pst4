<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServicioDashboard;

class DashboardController extends Controller
{
    protected $servicioDashboard;

    public function __construct(ServicioDashboard $servicioDashboard)
    {
        $this->servicioDashboard = $servicioDashboard;
    }

    public function index()
    {
        $stats = $this->servicioDashboard->obtenerEstadisticas();
        return view('admin.dashboard', compact('stats'));
    }
}
