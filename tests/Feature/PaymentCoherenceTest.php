<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ServicioValidacionPagos;

class PaymentCoherenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pago_con_monto_declarado_distinto_se_marca_riesgo_alto_y_puede_aprobarse_por_admin()
    {
        // Crear usuario admin
        $admin = User::factory()->create();

        // Crear pedido
        $order = Order::create([
            'numero_pedido' => 'TEST-1',
            'usuario_id' => $admin->id,
            'tipo' => 'detal',
            'estado' => 'pendiente',
            'subtotal' => 100.00,
            'descuento' => 0,
            'total' => 100.00,
            'entrega_nombre' => 'Test User',
            'entrega_telefono' => '000',
            'entrega_direccion' => 'Calle Falsa 123',
            'entrega_ciudad' => 'Ciudad',
        ]);

        // Crear pago con monto_declarado muy distinto
        $payment = Payment::create([
            'pedido_id' => $order->id,
            'metodo' => 'transferencia',
            'monto' => 100.00,
            'monto_declarado' => 10.00, // discrepancia grande
            'moneda' => 'USD',
            'numero_referencia' => 'ABC-123-TEST',
            'estado' => 'pendiente',
        ]);

        $service = app(ServicioValidacionPagos::class);

        $coh = $service->validarCoherencia($payment);

        $this->assertFalse($coh['monto_coincide'], 'El monto debe considerarse no coincidente');
        $this->assertEquals('high', $coh['nivel_riesgo_coherencia']);

        // Guardar metadata y comprobar que approve() sigue pudiendo ejecutarse
        $service->store($payment, new \Illuminate\Http\UploadedFile(__DIR__ . '/dummy.pdf', 'dummy.pdf'));

        // Después de store, metadata debe contener el resultado de coherencia
        $payment->refresh();
        $this->assertEquals('high', $payment->metadata_comprobante['coherencia']['nivel_riesgo_coherencia']);

        // Intentar aprobar (debe ejecutarse sin lanzar excepción y cambiar estado a valido)
        $service->approve($payment, $admin);
        $payment->refresh();
        $this->assertEquals('valido', $payment->estado);
    }
}
