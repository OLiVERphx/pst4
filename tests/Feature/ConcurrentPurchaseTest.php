<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;

class ConcurrentPurchaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simula dos compras sobre el Ãºltimo producto en stock y asegura que solo una tenga exito.
     */
    public function test_only_one_purchase_succeeds_when_stock_is_one()
    {
        // Crear producto con stock 1
        $product = Product::factory()->create([ 'stock' => 1, 'stock_reservado' => 0 ]);

        // Crear dos usuarios
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        // Crear carrito para cada usuario con 1 unidad del producto
        $c1 = Cart::create(['usuario_id' => $u1->id]);
        CartItem::create(['cart_id' => $c1->id, 'producto_id' => $product->id, 'cantidad' => 1, 'tipo' => 'detal', 'precio_unitario' => $product->precio_detal]);

        $c2 = Cart::create(['usuario_id' => $u2->id]);
        CartItem::create(['cart_id' => $c2->id, 'producto_id' => $product->id, 'cantidad' => 1, 'tipo' => 'detal', 'precio_unitario' => $product->precio_detal]);

        // Primer usuario intenta comprar
        $resp1 = $this->actingAs($u1)->post(route('web.checkout.store'), [
            'entrega_nombre' => 'A', 'entrega_apellido' => 'B', 'entrega_telefono' => '04141234567',
            'entrega_direccion' => 'C', 'entrega_ciudad' => 'D', 'metodo_pago' => 'fisico'
        ]);

        // Segundo usuario intenta comprar
        $resp2 = $this->actingAs($u2)->post(route('web.checkout.store'), [
            'entrega_nombre' => 'X', 'entrega_apellido' => 'Y', 'entrega_telefono' => '04149876543',
            'entrega_direccion' => 'Z', 'entrega_ciudad' => 'Q', 'metodo_pago' => 'fisico'
        ]);

        // Recargar producto
        $product->refresh();

        // Debe quedar stock 0
        $this->assertEquals(0, $product->stock);

        // Uno de los dos responses debe haber fallado con error de stock (redirect back with errors) y el otro redirigido a confirmado
        $this->assertTrue(
            ($resp1->isRedirect() && $resp2->assertSessionHasErrors('stock')) ||
            ($resp2->isRedirect() && $resp1->assertSessionHasErrors('stock'))
        );
    }
}
