<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Payment;
use App\Livewire\Datatables\OrdersTable;
use App\Services\ServicioDashboard;
use App\Services\ServicioPedidos;
use App\Services\ServicioValidacionPagos;

/**
 * Pedido y pago deben quedar coherentes sin importar desde dónde se confirme
 * (Verificar Pagos o Pedidos), y el dashboard debe contar esas ventas.
 */
class ConfirmacionPagoPedidoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@swworld.com')->firstOrFail();
    }

    private function pedido(string $canal, string $metodo, string $estado = 'pendiente'): Order
    {
        $producto = Product::where('activo', true)->where('stock', '>', 0)->firstOrFail();
        $order = app(ServicioPedidos::class)->crearPedidoDesdeLineas(
            [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00, 'tipo' => 'detal']],
            $this->admin->id, $this->admin->id, $canal, null,
            ['tipo_entrega' => 'retiro', 'entrega_nombre' => 'A B', 'entrega_telefono' => '0414']
        );
        $order->update(['estado' => $estado]);
        if ($canal === 'online') {
            Payment::create(['pedido_id' => $order->id, 'metodo' => $metodo, 'monto' => $order->total, 'moneda' => 'USD', 'estado' => 'pendiente']);
        }

        return $order->fresh();
    }

    #[Test]
    public function confirmar_el_pago_desde_pedidos_tambien_aprueba_el_pago()
    {
        $order = $this->pedido('online', 'pagomovil', 'pago_a_confirmar');

        Livewire::actingAs($this->admin)->test(OrdersTable::class)->call('advance', $order->id);

        $this->assertSame('pago_confirmado_retirar', $order->fresh()->estado);
        $this->assertSame('valido', $order->payment->fresh()->estado);
        $this->assertSame($this->admin->id, $order->payment->fresh()->verificado_por);
    }

    #[Test]
    public function aprobar_una_venta_local_la_deja_completada()
    {
        $order = $this->pedido('local', 'fisico');
        $this->assertSame('fisico', $order->payment->metodo);

        app(ServicioValidacionPagos::class)->approve($order->payment, $this->admin);

        $this->assertSame('completado', $order->fresh()->estado);
        $this->assertSame('valido', $order->payment->fresh()->estado);
    }

    #[Test]
    public function el_dashboard_cuenta_como_venta_los_pedidos_con_pago_confirmado()
    {
        $antes = app(ServicioDashboard::class)->obtenerEstadisticas()['total_ventas_mes'];

        $this->pedido('online', 'pagomovil', 'pago_confirmado_retirar');
        $this->pedido('online', 'transferencia', 'completado');
        $this->pedido('online', 'transferencia', 'pago_subido'); // aún no confirmado: no cuenta

        $despues = app(ServicioDashboard::class)->obtenerEstadisticas()['total_ventas_mes'];
        $this->assertEquals(20.00, $despues - $antes);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
    }
}
