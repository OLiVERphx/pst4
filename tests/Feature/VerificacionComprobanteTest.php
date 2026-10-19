<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Product;
use App\Models\Payment;
use App\Livewire\Datatables\VerificacionPagos;

/**
 * Flujo completo: el cliente sube el comprobante en el checkout y el admin
 * lo ve y lo aprueba desde Verificar Pagos.
 */
class VerificacionComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutConComprobante(array $extra = []): Payment
    {
        $this->seed();
        $this->configurarDatosDePago();
        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();
        $producto = Product::where('activo', true)->where('stock', '>', 0)->firstOrFail();

        $this->actingAs($cliente)->post(route('web.checkout.store'), array_merge([
            'items' => json_encode([['id' => $producto->id, 'cantidad' => 1, 'tipo' => 'detal']]),
            'tipo_entrega' => 'retiro',
            'entrega_nombre' => 'Cliente',
            'entrega_apellido' => 'Prueba',
            'entrega_telefono' => '04141234567',
            'metodo_pago' => 'pagomovil',
            'numero_referencia' => '007693346569',
            'comprobante' => UploadedFile::fake()->image('comprobante.png', 400, 600),
        ], $extra))->assertRedirect();

        return Payment::latest('id')->firstOrFail();
    }

    #[Test]
    public function el_admin_puede_ver_el_comprobante_subido()
    {
        $payment = $this->checkoutConComprobante();
        $this->assertNotNull($payment->ruta_comprobante);
        $this->assertTrue(Storage::disk('local')->exists($payment->ruta_comprobante));

        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.payments.receipt', $payment))
            ->assertOk();
    }

    #[Test]
    public function el_admin_puede_aprobar_el_pago_desde_verificacion()
    {
        $payment = $this->checkoutConComprobante();
        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(VerificacionPagos::class)
            ->assertSee($payment->order->numero_pedido)
            ->call('approve', $payment->id);

        $this->assertEquals('valido', $payment->fresh()->estado);
        // Retiro + pago online: aprobar lleva el pedido directo a pago confirmado
        $this->assertEquals('pago_confirmado_retirar', $payment->order->fresh()->estado);
    }

    #[Test]
    public function un_pago_sospechoso_sigue_visible_para_verificacion()
    {
        $payment = $this->checkoutConComprobante();
        $payment->update(['estado' => 'sospechoso']);
        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(VerificacionPagos::class)
            ->assertSee($payment->order->numero_pedido)
            ->assertSee('Sospechoso');
    }
}
