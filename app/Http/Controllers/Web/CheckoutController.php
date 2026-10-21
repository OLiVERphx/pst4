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
use App\Services\ServicioPrecios;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Mostrar la vista de checkout; el carrito se gestiona en el cliente
    public function index()
    {
        $user = auth()->user();
        $tasaBcv = ServicioPrecios::tasaBcvEn(now());
        $fechaTasa = \App\Models\TasaBcv::vigenteEn()?->fecha;
        $datosPago = \App\Models\Configuracion::datosPago();
        $metodos = \App\Models\Configuracion::metodosPagoDisponibles();
        return view('web.checkout', compact('user', 'tasaBcv', 'fechaTasa', 'datosPago', 'metodos'));
    }

    /**
     * Carrito del usuario en BD, sincronizado con el que envía el navegador (localStorage).
     * Antes se libera la reserva previa del usuario: si no, cada visita al checkout reservaba otra vez
     * y la compra contaba su propia reserva como stock ocupado (fallaba al llevar las últimas unidades).
     * Los precios y la disponibilidad se recalculan siempre contra la base de datos (Regla #1).
     */
    protected function getOrCreateUserCart(Request $request): ?Cart
    {
        $userId = auth()->id();
        $reservas = app(\App\Services\ReservationService::class);

        Cart::where('usuario_id', $userId)->where('estado', 'reserved')->get()
            ->each(fn (Cart $previo) => $reservas->releaseCartReservations($previo));

        // Un solo carrito activo por usuario
        $activos = Cart::where('usuario_id', $userId)->where('estado', 'active')->with('items')->orderByDesc('id')->get();
        $cart = $activos->shift();
        $activos->each->delete();

        $rawItems = $request->input('items');
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }

        if (!empty($rawItems) && is_array($rawItems)) {
            $cart ??= Cart::create(['usuario_id' => $userId, 'estado' => 'active']);
            $cart->items()->delete();

            foreach ($rawItems as $i) {
                $productId = $i['id'] ?? $i['producto_id'] ?? null;
                $cantidad = max(1, (int)($i['cantidad'] ?? 1));
                $tipo = ($i['tipo'] ?? 'detal') === 'mayor' ? 'mayor' : 'detal';

                $product = Product::where('id', $productId)->where('activo', true)->first();
                if ($product) {
                    $cart->items()->create([
                        'producto_id'     => $product->id,
                        'cantidad'        => $cantidad,
                        'tipo'            => $tipo,
                        'precio_unitario' => $tipo === 'mayor' ? $product->precio_mayor : $product->precio_detal,
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
            // Solo los métodos que la tienda tiene configurados en Configuración
            'metodo_pago'       => ['required', \Illuminate\Validation\Rule::in(array_keys(array_filter(\App\Models\Configuracion::metodosPagoDisponibles())))],
            'comprobante'       => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'numero_referencia' => 'nullable|string|max:100',
            'fecha_pago'        => 'nullable|date_format:'.config('pagos.fecha_format','Y-m-d').'|before_or_equal:today',
            'monto_pagado'      => 'nullable|numeric|min:0',
            'moneda_pagada'     => 'nullable|in:VES,USD,USDT',
            'banco_origen'      => 'nullable|string|max:60',
            'cedula_pagador'    => ['nullable', 'string', 'max:15', 'regex:/^[VEJGPvejgp]?-?\d{6,9}$/'],
            'cf-turnstile-response' => [new \App\Rules\Turnstile()],
        ]);

        // Regla de negocio: no se permite pago en efectivo con entrega a domicilio
        // (no hay forma de confirmar el pago antes de despachar el pedido).
        if ($request->tipo_entrega === 'delivery' && $request->metodo_pago === 'fisico') {
            return back()->withInput()->withErrors(['metodo_pago' => 'El pago en efectivo no está disponible para pedidos con delivery. Elegí retiro en tienda o un método de pago en línea.']);
        }

        // Regla de negocio: si el cliente está marcado como bloqueado, no puede completar checkout.
        if (auth()->check() && optional(auth()->user())->bloqueado) {
            return back()->withInput()->withErrors(['blocked' => 'Su cuenta está bloqueada. Contacte a soporte para completar el pago.']);
        }

        // Recuperar o sincronizar carrito persistente del usuario
        $cart = $this->getOrCreateUserCart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'El carrito está vacío.']);
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

            $pago = Payment::create([
                'pedido_id'        => $order->id,
                'metodo'           => $request->metodo_pago,
                'monto'            => $order->subtotal,
                'monto_declarado'  => $request->monto_pagado ?? null,
                'moneda_declarada' => $request->moneda_pagada ?? null,
                'fecha_pago_declarada' => $request->fecha_pago ? \Carbon\Carbon::createFromFormat(config('pagos.fecha_format','Y-m-d'), $request->fecha_pago) : null,
                'moneda'           => 'USD',
                'numero_referencia'=> $request->numero_referencia,
                'banco_origen'     => $request->banco_origen,
                'cedula_pagador'   => $request->cedula_pagador ? strtoupper(str_replace('-', '', $request->cedula_pagador)) : null,
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

            // Efectivo + retiro en tienda: no hay pago online que confirmar, el pedido queda listo de inmediato.
            if ($order->tipo_entrega === 'retiro' && $request->metodo_pago === 'fisico') {
                $order->update(['estado' => 'listo_para_retirar']);
                \App\Services\ServicioAuditoria::registrar(
                    'pedido.estado_cambiado',
                    $order,
                    ['estado' => 'pendiente'],
                    ['estado' => 'listo_para_retirar'],
                    auth()->id()
                );
            }

            return redirect()->route('web.order.confirmed', $order->numero_pedido);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['stock' => $e->getMessage()]);
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
