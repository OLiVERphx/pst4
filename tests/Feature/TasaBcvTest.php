<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\TasaBcv;
use App\Models\Configuracion;
use App\Services\ServicioPrecios;

class TasaBcvTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function usa_la_ultima_tasa_publicada_en_o_antes_de_la_fecha()
    {
        ServicioPrecios::registrarTasaBcv(40.00, '2026-10-09'); // viernes
        ServicioPrecios::registrarTasaBcv(41.50, '2026-10-12'); // lunes

        // Sábado y domingo rige la del viernes
        $this->assertEquals(40.00, ServicioPrecios::tasaBcvEn('2026-10-11'));
        $this->assertEquals(41.50, ServicioPrecios::tasaBcvEn('2026-10-12'));
        $this->assertEquals(41.50, ServicioPrecios::tasaBcvEn('2026-10-14'));
    }

    #[Test]
    public function corregir_una_tasa_vieja_no_pisa_la_tasa_vigente()
    {
        ServicioPrecios::registrarTasaBcv(41.50, now());
        ServicioPrecios::registrarTasaBcv(39.00, now()->subDays(3));

        $this->assertEquals(41.50, (float) Configuracion::instance()->tasa_bcv);
        $this->assertEquals(2, TasaBcv::count());
    }

    #[Test]
    public function el_admin_guarda_la_tasa_desde_configuracion()
    {
        $this->seed();
        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.configuracion.update'), ['tasa_bcv' => 45.25])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');

        $this->assertEquals(45.25, ServicioPrecios::tasaBcvEn(now()));
        $this->assertEquals('manual', TasaBcv::vigenteEn()->fuente);

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Bs 45,25', false)
            ->assertSee('(manual)', false);
    }

    #[Test]
    public function el_checkout_muestra_la_tasa_bcv_vigente()
    {
        $this->seed();
        $this->configurarDatosDePago();
        ServicioPrecios::registrarTasaBcv(52.75, now());
        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();

        $this->actingAs($cliente)->get(route('web.checkout'))
            ->assertOk()
            ->assertSee('Pago móvil')
            ->assertSee('Tasa oficial BCV: Bs 52,75 por USD')
            ->assertSee('tasa: 52.75', false);
    }
}
