<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\IndiceCiego;
use Illuminate\Http\Request;

/**
 * Buscador de la barra superior del panel: pedidos, productos y clientes,
 * cada sección solo si el usuario tiene permiso para verla.
 */
class BuscarController extends Controller
{
    private const LIMITE = 5;

    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();
        $secciones = [];

        if (mb_strlen($q) >= 2) {
            $like = "%{$q}%";

            if ($user->can('pedidos.ver')) {
                $secciones['pedidos'] = Order::with('user:id,name,apellido')
                    ->where(function ($w) use ($like) {
                        $w->where('numero_pedido', 'like', $like)
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('apellido', 'like', $like));
                    })
                    ->latest()
                    ->limit(self::LIMITE)
                    ->get(['id', 'numero_pedido', 'usuario_id', 'estado', 'total', 'created_at']);
            }

            if ($user->can('productos.ver')) {
                $secciones['productos'] = Product::where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('codigo', 'like', $like))
                    ->orderBy('nombre')
                    ->limit(self::LIMITE)
                    ->get(['id', 'nombre', 'codigo', 'stock', 'stock_minimo', 'precio_detal', 'activo']);
            }

            if ($user->can('clientes.ver')) {
                // Cédula cifrada: solo coincidencia exacta por su índice ciego
                $secciones['clientes'] = User::clientes()
                    ->where(function ($w) use ($like, $q) {
                        $w->where('name', 'like', $like)
                            ->orWhere('apellido', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->when(IndiceCiego::cedula($q), fn ($c, $hash) => $c->orWhere('cedula_hash', $hash));
                    })
                    ->orderBy('name')
                    ->limit(self::LIMITE)
                    ->get(['id', 'name', 'apellido', 'email', 'activo']);
            }
        }

        return view('admin.buscar', compact('q', 'secciones'));
    }
}
