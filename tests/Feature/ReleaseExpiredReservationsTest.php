<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_releases_expired_cart_reservations()
    {
        $brand = Brand::create(['nombre' => 'Apple']);
        $category = Category::create(['nombre' => 'Accesorios', 'slug' => 'accesorios']);

        $product = Product::create([
            'codigo' => 'RES-001',
            'nombre' => 'Producto reservado',
            'marca_id' => $brand->id,
            'categoria_id' => $category->id,
            'precio_detal' => 10,
            'precio_mayor' => 9,
            'stock' => 10,
        ]);
        $product->stock_reservado = 3;
        $product->save();

        $cart = Cart::create([
            'estado' => 'reserved',
            'reserved_at' => Carbon::now()->subMinutes(20),
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'producto_id' => $product->id,
            'cantidad' => 3,
            'tipo' => 'detal',
            'precio_unitario' => $product->precio_detal,
        ]);

        $this->artisan('reservations:release-expired')->assertSuccessful();

        $this->assertSame(0, $product->refresh()->stock_reservado);
        $this->assertSame('active', $cart->refresh()->estado);
        $this->assertNull($cart->reserved_at);
    }
}
