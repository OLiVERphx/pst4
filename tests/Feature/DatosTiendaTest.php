<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Configuracion;

/**
 * Datos de la empresa y de cobro editables desde Configuración; el checkout
 * solo ofrece los métodos de pago que tienen sus datos cargados.
 */
class DatosTiendaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@swworld.com')->firstOrFail();
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_replace_recursive([
            'empresa' => ['nombre' => 'Smartphone World C.A.', 'rif' => 'j-12345678-9', 'telefono' => '0271-1234567', 'direccion' => 'Valera'],
            'pago' => [
                'transferencia' => ['banco' => '0134', 'tipo_cuenta' => 'corriente', 'numero_cuenta' => '0134-0000-11-2222222222', 'titular' => 'Smartphone World C.A.', 'documento' => 'J-12345678-9'],
                'pagomovil' => ['banco' => '0102', 'telefono' => '0414-123.45.67', 'documento' => 'J-12345678-9'],
                'binance' => ['pay_id' => '284910482', 'correo' => ''],
            ],
        ], $cambios);
    }

    #[Test]
    public function el_admin_guarda_los_datos_normalizados()
    {
        $this->actingAs($this->admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.configuracion.datos_tienda'), $this->datosValidos())
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasNoErrors();

        $pago = Configuracion::datosPago();
        $this->assertSame('01340000112222222222', $pago['transferencia']['numero_cuenta']);
        $this->assertSame('04141234567', $pago['pagomovil']['telefono']);
        $this->assertSame('J-12345678-9', Configuracion::empresa()['rif']);
        $this->assertSame(['transferencia' => true, 'pagomovil' => true, 'binance' => true, 'fisico' => true], Configuracion::metodosPagoDisponibles());
    }

    #[Test]
    public function rechaza_una_cuenta_que_no_es_del_banco_elegido_y_un_telefono_que_no_es_celular()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.configuracion.datos_tienda'), $this->datosValidos([
                'pago' => [
                    'transferencia' => ['banco' => '0102'], // la cuenta empieza por 0134
                    'pagomovil' => ['telefono' => '0271-1234567'],
                ],
            ]))
            ->assertSessionHasErrors(['pago.transferencia.numero_cuenta', 'pago.pagomovil.telefono']);
    }

    #[Test]
    public function el_checkout_muestra_solo_los_metodos_configurados()
    {
        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();

        // Sin datos: solo efectivo en tienda
        $this->actingAs($cliente)->get(route('web.checkout'))
            ->assertOk()
            ->assertDontSee('Transferencia bancaria')
            ->assertDontSee('Binance Pay ID')
            ->assertSee('Pago en tienda');

        // Con pago móvil cargado: aparece con los datos reales
        $this->actingAs($this->admin)->post(route('admin.configuracion.datos_tienda'), $this->datosValidos([
            'pago' => ['transferencia' => ['banco' => '', 'numero_cuenta' => '', 'titular' => '', 'documento' => ''], 'binance' => ['pay_id' => '']],
        ]))->assertSessionHasNoErrors();

        $this->actingAs($cliente)->get(route('web.checkout'))
            ->assertSee('Pago móvil')
            ->assertSee('0414-1234567')
            ->assertDontSee('Transferencia bancaria');
    }

    #[Test]
    public function el_servidor_rechaza_un_metodo_que_la_tienda_no_ofrece()
    {
        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();

        $this->actingAs($cliente)->post(route('web.checkout.store'), [
            'tipo_entrega' => 'retiro',
            'entrega_nombre' => 'A',
            'entrega_apellido' => 'B',
            'entrega_telefono' => '04140000000',
            'metodo_pago' => 'binance',
        ])->assertSessionHasErrors('metodo_pago');
    }
}
