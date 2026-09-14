<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Services\ServicioInventario;
use App\Services\ServicioValidacionPagos;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Mostrar la vista de checkout; el carrito se gestiona en el cliente
    public function index()
    {
        return view('web.checkout');
    }

    /**
     * Reservar stock temporal para el carrito activo del usuario (15 minutos por defecto)
     * Requiere que el usuario esté autenticado (ruta protegida por middleware 'auth').
     */
    public function reserve(Request $request)
    {
        $cart = \App\Models\Cart::where('usuario_id', auth()->id())->where('estado','active')->with('items')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Carrito vacío'], 400);
        }

        $service = app(\App\Services\ReservationService::class);
        $results = $service->reserveForCart($cart, 15);

        // Si algún item falló, devolver 409 con detalle
        if (collect($results)->contains('ok', false)) {
            return response()->json(['ok' => false, 'results' => $results], 409);
        }

        return response()->json(['ok' => true, 'results' => $results]);
    }

    // Procesar el pedido enviado desde el servidor (carrito persistente)
    public function store(Request $request)
    {
        $request->validate([
            'entrega_nombre'    => 'required|string|max:100',
            'entrega_apellido'  => 'required|string|max:100',
            'entrega_telefono'  => 'required|string|max:20',
            'entrega_direccion' => 'required|string',
            'entrega_ciudad'    => 'required|string|max:80',
            'metodo_pago'       => 'required|in:transferencia,pagomovil,binance,fisico',
            'comprobante'       => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'numero_referencia' => 'nullable|string|max:100',
            // Campos declarados por el cliente para verificación automática
            'fecha_pago'        => 'nullable|date_format:'.config('pagos.fecha_format','Y-m-d'),
            'monto_pagado'      => 'nullable|numeric|min:0',
            'moneda_pagada'     => 'nullable|string|max:5',
        ]);

        // Regla de negocio: si el cliente está marcado como bloqueado, no puede completar checkout.
        // Esto permite que aún navegue el catálogo, pero evita la creación del pedido.
        if (auth()->check() && optional(auth()->user())->bloqueado) {
            return back()->withErrors(['blocked' => 'Su cuenta está bloqueada. Contacte a soporte para completar el pago.']);
        }

        // Recuperar carrito persistente del usuario (la ruta /checkout está protegida por auth)
        $cart = \App\Models\Cart::where('usuario_id', auth()->id())->where('estado','active')->with('items')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return back()->withErrors(['items' => 'El carrito está vacío.']);
        }

        $inventario = app(ServicioInventario::class);

        // Delegar la creación del pedido al servicio reutilizable para evitar duplicar lógica
        try {
            $lineas = [];
            foreach ($cart->items as $item) {
                $producto = \App\Models\Product::where('id', $item->producto_id)->first();
                $precio = $item->tipo === 'mayor' ? $producto->precio_mayor : $producto->precio_detal;
                $lineas[] = ['producto_id' => $item->producto_id, 'cantidad' => $item->cantidad, 'precio' => $precio, 'tipo' => $item->tipo];
            }

            $servicioPedidos = app(\App\Services\ServicioPedidos::class);

            // Crear pedido reusando la lógica centralizada (el servicio hace la transacción y locks)
            $order = $servicioPedidos->crearPedidoDesdeLineas($lineas, auth()->id(), auth()->id(), 'online', $request->notas ?? null, [
                'entrega_nombre' => $request->entrega_nombre . ' ' . $request->entrega_apellido,
                'entrega_telefono' => $request->entrega_telefono,
                'entrega_direccion' => $request->entrega_direccion,
                'entrega_ciudad' => $request->entrega_ciudad,
            ]);

            // Marcar carrito como checked_out (sin borrado físico)
            $cart->estado = 'checked_out';
            $cart->save();

            // Registrar pago si aplica (fuera de la transacción)
            if ($request->metodo_pago !== 'fisico') {
                $pago = Payment::create([
                    'pedido_id'        => $order->id,
                    'metodo'           => $request->metodo_pago,
                    'monto'            => $order->subtotal,
                    'monto_declarado'  => $request->monto_pagado ?? null,
                    'moneda_declarada' => $request->moneda_pagada ?? null,
                    'fecha_pago_declarada' => $request->fecha_pago ? \Carbon\Carbon::createFromFormat(config('pagos.fecha_format','Y-m-d'), $request->fecha_pago) : null,
                    'moneda'           => 'USD',
                    'numero_referencia'=> $request->numero_referencia,
                    'estado'           => 'pendiente',
                ]);

                if ($request->hasFile('comprobante')) {
                    try {
                        app(ServicioValidacionPagos::class)->store($pago, $request->file('comprobante'));
                        $order->update(['estado' => 'pago_subido']);
                    } catch (\Throwable $e) {
                        logger()->error('Pago comprobante store failed: ' . $e->getMessage());
                    }
                }
            }

            \Illuminate\Support\Facades\Redirect::setIntendedUrl(route('web.order.confirmed', $order->numero_pedido));
        } catch (\Exception $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        }

        return redirect()->route('web.order.confirmed', session()->pull('redirect_intended') ?? '\\');
    }

    public function confirmed(string $numero)
    {
        $order = Order::where('numero_pedido', $numero)
            ->where('usuario_id', auth()->id())
            ->firstOrFail();
        return view('web.order-confirmed', compact('order'));
    }
}
