<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para manipular el carrito persistente.
 */
class CartController extends Controller
{
    /**
     * Añadir producto al carrito (autenticado o invitado via token de sesión)
     */
    public function add(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:products,id',
            'cantidad'    => 'required|integer|min:1',
            'tipo'        => 'nullable|in:detal,mayor'
        ]);

        $producto = Product::find($request->producto_id);
        if (!$producto) {
            return response()->json(['ok' => false, 'message' => 'Producto no encontrado'], 404);
        }

        // Determinar cart (usuario o token en sesión)
        $cart = $this->getOrCreateCart($request);

        // Validar stock real contra DB (stock - stock_reservado)
        $disponible = $producto->stock - $producto->stock_reservado;
        if ($disponible < $request->cantidad) {
            return response()->json(['ok' => false, 'message' => 'Stock insuficiente'], 409);
        }

        // Guardar o sumar a item existente
        $item = $cart->items()->where('producto_id', $producto->id)->where('tipo', $request->tipo ?? 'detal')->first();

        // Si el cliente solicita agregar como 'mayor', verificar que la cantidad (o la cantidad resultante si ya existe item)
        // cumple la mínima requerida para mayorar el producto.
        if (($request->tipo ?? 'detal') === 'mayor') {
            $minConfig = \App\Models\Configuracion::instance()?->minimo_unidades_mayor ?? 10;
            $required = $producto->min_cantidad_mayor ?? $minConfig;
            $wouldBeQuantity = ($item ? $item->cantidad : 0) + (int)$request->cantidad;
            if ($wouldBeQuantity < $required) {
                return response()->json([
                    'ok' => false,
                    'message' => "Para agregar este producto como mayor se requieren al menos {$required} unidad(es). Cantidad propuesta: {$wouldBeQuantity}.",
                ], 409);
            }
        }

        if ($item) {
            $item->cantidad += $request->cantidad;
            $item->precio_unitario = ($request->tipo ?? 'detal') === 'mayor' ? $producto->precio_mayor : $producto->precio_detal;
            $item->save();
        } else {
            $cart->items()->create([
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'tipo' => $request->tipo ?? 'detal',
                'precio_unitario' => ($request->tipo ?? 'detal') === 'mayor' ? $producto->precio_mayor : $producto->precio_detal
            ]);
        }

        return response()->json(['ok' => true, 'cart_id' => $cart->id]);
    }

    protected function getOrCreateCart(Request $request)
    {
        if (auth()->check()) {
            // Usuario autenticado: usar su carrito activo o crear
            $cart = Cart::firstOrCreate(['usuario_id' => auth()->id(), 'estado' => 'active'], ['token' => null]);
            // Si el usuario tenía un token en sesión migrar items (si existe)
            if ($request->session()->has('sw_cart_token')) {
                $token = $request->session()->pull('sw_cart_token');
                $guest = Cart::where('token', $token)->where('estado','active')->first();
                if ($guest) {
                    foreach ($guest->items as $gi) {
                        $existing = $cart->items()->where('producto_id', $gi->producto_id)->where('tipo', $gi->tipo)->first();
                        if ($existing) {
                            $existing->cantidad += $gi->cantidad;
                            $existing->save();
                        } else {
                            $cart->items()->create($gi->toArray());
                        }
                    }
                    $guest->delete();
                }
            }
            return $cart;
        }

        // Invitado: token en sesión o crear nuevo cart con token
        $token = $request->session()->get('sw_cart_token');
        if (!$token) {
            $token = Str::uuid()->toString();
            $request->session()->put('sw_cart_token', $token);
        }

        $cart = Cart::firstOrCreate(['token' => $token, 'estado' => 'active'], ['usuario_id' => null]);
        return $cart;
    }

    /**
     * Obtener el carrito actual (JSON)
     */
    public function current(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.producto');
        return response()->json(['ok' => true, 'cart' => $cart]);
    }
}
