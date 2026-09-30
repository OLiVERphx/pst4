<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\ServicioInventario;
use App\Services\ServicioValidacionPagos;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Mostrar la vista de checkout; el carrito se gestiona en el cliente
    public function index()
    {
        $user = auth()->user();
        return view('web.checkout', compact('user'));
    }

    /**
     * Obtiene el carrito activo del usuario en BD, o lo sincroniza/crea si viene del cliente.
     * Siempre recalcula precios y disponibilidad contra la base de datos (Regla #1).
     */
    protected function getOrCreateUserCart(Request $request): ?Cart
    {
        $userId = auth()->id();
        $cart = Cart::where('usuario_id', $userId)->where('estado', 'active')->with('items')->first();

        // Si el carrito en BD no existe o está vacío, pero el cliente envía items (desde localStorage)
        $rawItems = $request->input('items');
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }

        if ((!$cart || $cart->items->isEmpty()) && !empty($rawItems) && is_array($rawItems)) {
            if (!$cart) {
                $cart = Cart::create(['usuario_id' => $userId, 'estado' => 'active']);
            }

            foreach ($rawItems as $i) {
                $productId = $i['id'] ?? $i['producto_id'] ?? null;
                $cantidad = max(1, (int)($i['cantidad'] ?? 1));
                $tipo = ($i['tipo'] ?? 'detal') === 'mayor' ? 'mayor' : 'detal';

                // Recalcular siempre contra la base de datos en el servidor
                $product = Product::where('id', $productId)->where('activo', true)->first();
                if ($product && $cantidad > 0) {
                    $precioUnitario = $tipo === 'mayor' ? $product->precio_mayor : $product->precio_detal;
                    $cart->items()->create([
                        'producto_id'     => $product->id,
                        'cantidad'        => $cantidad,
                        'tipo'            => $tipo,
                        'precio_unitario' => $precioUnitario,
                    ]);
                }
            }
            $cart->load('items');
        }

        return $cart;
    }

    /**
     * Reservar stock temporal para el carrito activo del usuario (15 minutos por defecto)
     * Requiere que el usuario esté autenticado (ruta protegida por middleware 'auth').
     */
    public function reserve(Request $request)
    {
        $cart = $this->getOrCreateUserCart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'El carrito está vacío'], 400);
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
            'tipo_entrega'      => 'required|in:retiro,delivery',
            'entrega_nombre'    => 'required|string|max:100',
            'entrega_apellido'  => 'required|string|max:100',
            'entrega_telefono'  => 'required|string|max:20',
            'entrega_direccion' => 'required_if:tipo_entrega,delivery|nullable|string',
            'entrega_ciudad'    => 'required_if:tipo_entrega,delivery|nullable|string|max:80',
            'metodo_pago'       => 'required|in:transferencia,pagomovil,binance,fisico',
            'comprobante'       => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'numero_referencia' => 'nullable|string|max:100',
            'fecha_pago'        => 'nullable|date_format:'.config('pagos.fecha_format','Y-m-d'),
            'monto_pagado'      => 'nullable|numeric|min:0',
            'moneda_pagada'     => 'nullable|string|max:5',
        ]);

        // Regla de negocio: no se permite pago en efectivo con entrega a domicilio
        // (no hay forma de confirmar el pago antes de despachar el pedido).
        if ($request->tipo_entrega === 'delivery' && $request->metodo_pago === 'fisico') {
            return back()->withErrors(['metodo_pago' => 'El pago en efectivo no está disponible para pedidos con delivery. Elegí retiro en tienda o un método de pago en línea.']);
        }

        // Regla de negocio: si el cliente está marcado como bloqueado, no puede completar checkout.
        if (auth()->check() && optional(auth()->user())->bloqueado) {
            return back()->withErrors(['blocked' => 'Su cuenta está bloqueada. Contacte a soporte para completar el pago.']);
        }

        // Recuperar o sincronizar carrito persistente del usuario
        $cart = $this->getOrCreateUserCart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return back()->withErrors(['items' => 'El carrito está vacío.']);
        }

        $inventario = app(ServicioInventario::class);

        // Delegar la creación del pedido al servicio reutilizable para evitar duplicar lógica
        try {
            $lineas = [];
            foreach ($cart->items as $item) {
                $producto = Product::where('id', $item->producto_id)->first();
                $precio = $item->tipo === 'mayor' ? $producto->precio_mayor : $producto->precio_detal;
                $lineas[] = ['producto_id' => $item->producto_id, 'cantidad' => $item->cantidad, 'precio' => $precio, 'tipo' => $item->tipo];
            }

            $servicioPedidos = app(\App\Services\ServicioPedidos::class);

            // Crear pedido reusando la lógica centralizada (el servicio hace la transacción y locks)
            $order = $servicioPedidos->crearPedidoDesdeLineas($lineas, auth()->id(), auth()->id(), 'online', $request->notas ?? null, [
                'tipo_entrega' => $request->tipo_entrega,
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

            return redirect()->route('web.order.confirmed', $order->numero_pedido);
        } catch (\Exception $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        }
    }

    public function confirmed(string $numero)
    {
        $order = Order::where('numero_pedido', $numero)
            ->where('usuario_id', auth()->id())
            ->firstOrFail();
        return view('web.order-confirmed', compact('order'));
    }
}
