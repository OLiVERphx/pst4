<?php

namespace Tests\Feature;

use App\Mail\ReporteSemanal;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Elementos de Configuración: Mi perfil (contraseña), Notificaciones y Limpiar caché.
 */
class ConfiguracionCuentaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@swworld.com')->firstOrFail();
        $this->vendedor = User::factory()->create(['rol_id' => 4, 'email' => 'vendedor@swworld.com']);
        $this->vendedor->assignRole('vendedor');
    }

    #[Test]
    public function cambia_la_contrasena_solo_con_la_actual_correcta()
    {
        $this->actingAs($this->admin)->from(route('admin.settings.index'))
            ->put(route('admin.perfil.password'), [
                'password_actual' => 'incorrecta',
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasErrorsIn('password', 'password_actual');
        $this->assertTrue(Hash::check('Admin123!', $this->admin->fresh()->password));

        $this->actingAs($this->admin)->from(route('admin.settings.index'))
            ->put(route('admin.perfil.password'), [
                'password_actual' => 'Admin123!',
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NuevaClave2026', $this->admin->fresh()->password));
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'usuario.password_cambiada', 'usuario_id' => $this->admin->id]);
    }

    #[Test]
    public function rechaza_contrasenas_debiles()
    {
        $this->actingAs($this->admin)->put(route('admin.perfil.password'), [
            'password_actual' => 'Admin123!',
            'password' => 'solo-letras',
            'password_confirmation' => 'solo-letras',
        ])->assertSessionHasErrorsIn('password', 'password');
    }

    #[Test]
    public function el_vendedor_tiene_su_cuenta_aunque_no_vea_configuracion()
    {
        $this->actingAs($this->vendedor)->get(route('admin.settings.index'))->assertForbidden();

        $this->actingAs($this->vendedor)->get(route('admin.perfil'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('Pago recibido')
            ->assertDontSee('Reporte semanal'); // no tiene reportes.ver
    }

    #[Test]
    public function las_preferencias_controlan_la_campana()
    {
        $stats = ['pedidos_pendientes' => 2, 'pagos_por_verificar' => 1, 'alertas_stock' => 3];

        $this->assertCount(3, AppServiceProvider::alertasCampana($this->admin, $stats));

        // Solo deja activo "pago recibido"
        $this->actingAs($this->admin)->put(route('admin.perfil.notificaciones'), [
            'notificaciones' => ['pago_recibido'],
        ])->assertSessionHasNoErrors();

        $alertas = AppServiceProvider::alertasCampana($this->admin->fresh(), $stats);
        $this->assertCount(1, $alertas);
        $this->assertSame('1 pago por verificar', $alertas[0]['texto']);
        $this->assertFalse($this->admin->fresh()->quiereNotificacion('reporte_semanal'));
    }

    #[Test]
    public function no_se_activan_avisos_sin_permiso()
    {
        $this->actingAs($this->vendedor)->put(route('admin.perfil.notificaciones'), [
            'notificaciones' => ['reporte_semanal', 'pedido_nuevo'],
        ]);

        $this->assertFalse($this->vendedor->fresh()->quiereNotificacion('reporte_semanal'));
        $this->assertTrue($this->vendedor->fresh()->quiereNotificacion('pedido_nuevo'));
        $this->assertSame([], AppServiceProvider::alertasCampana($this->vendedor->fresh(), ['alertas_stock' => 0]));
    }

    #[Test]
    public function el_reporte_semanal_llega_solo_a_quien_lo_tiene_activo()
    {
        Mail::fake();
        $otroAdmin = User::factory()->create(['rol_id' => 2, 'preferencias_notificacion' => ['reporte_semanal' => false]]);
        $otroAdmin->assignRole('admin');

        $this->artisan('reportes:semanal')->assertSuccessful();

        Mail::assertSent(ReporteSemanal::class, fn ($m) => $m->hasTo('admin@swworld.com'));
        Mail::assertNotSent(ReporteSemanal::class, fn ($m) => $m->hasTo($otroAdmin->email));
        Mail::assertNotSent(ReporteSemanal::class, fn ($m) => $m->hasTo($this->vendedor->email));
    }

    #[Test]
    public function el_correo_del_reporte_semanal_se_genera()
    {
        $resumen = app(\App\Console\Commands\EnviarReporteSemanal::class)->resumen();

        $html = (new ReporteSemanal($resumen))->render();

        $this->assertStringContainsString('Reporte semanal', $html);
        $this->assertStringContainsString('Ticket promedio', $html);
    }

    #[Test]
    public function limpiar_cache_borra_la_cache_y_reconstruye_la_busqueda()
    {
        Cache::put('dato_viejo', 'x', 600);

        $this->actingAs($this->admin)->from(route('admin.settings.index'))
            ->post(route('admin.settings.limpiar_cache'))
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');

        $this->assertFalse(Cache::has('dato_viejo'));
        $this->assertTrue(Cache::has('tfidf_index_products_v1'));
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'sistema.cache_limpiada']);
    }

    #[Test]
    public function el_vendedor_no_puede_limpiar_la_cache()
    {
        $this->actingAs($this->vendedor)->post(route('admin.settings.limpiar_cache'))->assertForbidden();
    }

    #[Test]
    public function configuracion_muestra_los_formularios_y_la_base_real()
    {
        $this->actingAs($this->admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('Guardar notificaciones')
            ->assertSee('Limpiar caché')
            ->assertDontSee('MySQL 8.0');
    }
}
