<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Services\ServicioPedidos;

/**
 * Los números de pedido eran 4 dígitos al azar y chocaban con unos cientos de
 * pedidos al año. Ahora son secuenciales por año y nunca se repiten.
 */
class NumeroPedidoTest extends TestCase
{
    use RefreshDatabase;

    private function crearPedido(): Order
    {
        $producto = Product::where('activo', true)->where('stock', '>', 0)->firstOrFail();
        $producto->update(['stock' => 100000]);
        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();

        return app(ServicioPedidos::class)->crearPedidoDesdeLineas(
            [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 1.00, 'tipo' => 'detal']],
            $admin->id, $admin->id, 'local', null, ['tipo_entrega' => 'retiro']
        );
    }

    #[Test]
    public function trescientos_pedidos_seguidos_tienen_numeros_unicos_y_consecutivos()
    {
        $this->seed();

        $numeros = collect(range(1, 300))->map(fn () => $this->crearPedido()->numero_pedido);

        $this->assertCount(300, $numeros->unique());
        $anio = date('Y');
        $secuencia = $numeros->map(fn ($n) => (int) substr($n, strlen("SW-{$anio}-")))->values();
        foreach ($secuencia as $i => $n) {
            if ($i > 0) {
                $this->assertSame($secuencia[$i - 1] + 1, $n, 'Los números deben ser consecutivos');
            }
        }
        $this->assertMatchesRegularExpression("/^SW-{$anio}-\\d{4,}$/", $numeros->first());
    }

    #[Test]
    public function continua_desde_el_numero_mas_alto_aunque_sea_antiguo_o_este_eliminado()
    {
        $this->seed();
        $anio = date('Y');
        // Un número del formato anterior (al azar) y otro mayor en un pedido eliminado
        $viejo = $this->crearPedido();
        $viejo->update(['numero_pedido' => "SW-{$anio}-8007"]);
        $eliminado = $this->crearPedido();
        $eliminado->update(['numero_pedido' => "SW-{$anio}-9999"]);
        $eliminado->delete();

        $this->assertSame("SW-{$anio}-10000", $this->crearPedido()->numero_pedido);
        $this->assertSame("SW-{$anio}-10001", $this->crearPedido()->numero_pedido);
    }
}
