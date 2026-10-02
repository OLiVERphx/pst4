<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ServicioPedidos;

class AccountController extends Controller
{
    public function index(ServicioPedidos $service)
    {
        $pedidos = auth()->user()
            ->orders()
            ->with(['items.product', 'payment'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $estados = $service->obtenerFlujoEstados();

        return view('web.mi-cuenta', compact('pedidos', 'estados'));
    }
}
