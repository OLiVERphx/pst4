<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/**
 * Reportes no debe demorarse esperando al microservicio ML si no existe o está caído.
 */
class ServicioMlReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@swworld.com')->firstOrFail();
    }

    #[Test]
    public function sin_ml_service_url_no_se_intenta_conectar()
    {
        config(['services.ml.url' => null]);
        Http::fake();

        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

        Http::assertNothingSent();
    }

    #[Test]
    public function si_el_servicio_falla_no_se_reintenta_en_cada_carga()
    {
        config(['services.ml.url' => 'http://ml.invalido:8001']);
        Http::fake(['*' => Http::response(null, 503)]);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

        Http::assertSentCount(1);
    }

    private function reporteFalso(int $productoId, int $clienteId): array
    {
        return [
            'generado_en' => '2026-10-19T10:00:00',
            'datos' => ['semanas_historial' => 52, 'lineas_venta' => 500, 'productos' => 1, 'clientes' => 30],
            'demanda' => ['status' => 'ok', 'semanas_analizadas' => 52, 'items' => [[
                'status' => 'ok', 'tipo' => 'regular', 'metodo' => 'ses', 'confianza' => 'alta',
                'pronostico_semanal' => [5.0, 5.0, 5.0, 5.0], 'error_modelo' => 1.2, 'error_ingenuo' => 2.5,
                'semanas_historial' => 40, 'producto_id' => $productoId, 'stock_actual' => 3,
                'stock_minimo' => 2, 'reponer_sugerido' => 19,
            ]]],
            'segmentos' => ['status' => 'ok',
                'resumen' => [['segmento' => 'En riesgo', 'clientes' => 1, 'monto_total' => 950.0,
                    'recencia_promedio_dias' => 120, 'accion' => 'Contactar con una oferta.']],
                'items' => [['cliente_id' => $clienteId, 'segmento' => 'En riesgo', 'r' => 1, 'f' => 4, 'm' => 5,
                    'recencia_dias' => 120, 'frecuencia' => 9, 'monto' => 950.0]],
            ],
        ];
    }

    #[Test]
    public function muestra_el_reporte_con_nombres_y_envia_la_clave()
    {
        config(['services.ml.url' => 'http://ml.local:8001', 'services.ml.key' => 'clave-test']);
        $producto = Product::firstOrFail();
        $cliente = User::factory()->create(['name' => 'Rosa', 'apellido' => 'Quintero']);
        Http::fake(['ml.local:8001/api/ml/reports' => Http::response($this->reporteFalso($producto->id, $cliente->id))]);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reposición sugerida')
            ->assertSee($producto->nombre)
            ->assertSee('Suavizado exponencial')
            ->assertSee('Rosa Quintero')
            ->assertSee('Contactar con una oferta.');

        // La segunda carga usa la copia guardada
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->header('X-Api-Key') === ['clave-test']);
    }

    #[Test]
    public function avisa_si_la_clave_no_coincide()
    {
        config(['services.ml.url' => 'http://ml.local:8001', 'services.ml.key' => 'otra']);
        Http::fake(['*' => Http::response(['detail' => 'Clave inválida'], 401)]);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('ML_API_KEY');
    }

    #[Test]
    public function recalcular_descarta_la_copia_guardada()
    {
        config(['services.ml.url' => 'http://ml.local:8001', 'services.ml.key' => 'clave-test']);
        $reporte = $this->reporteFalso(Product::firstOrFail()->id, $this->admin->id);
        Http::fake([
            'ml.local:8001/api/ml/recalcular' => Http::response(['status' => 'ok']),
            'ml.local:8001/api/ml/reports' => Http::response($reporte),
        ]);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($this->admin)->from(route('admin.reports.index'))
            ->post(route('admin.reports.ml.recalcular'))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

        Http::assertSentCount(3);
    }

    #[Test]
    public function genera_y_elimina_ventas_de_demostracion()
    {
        $this->artisan('demo:ventas', ['--semanas' => 12, '--clientes' => 30, '--pedidos-semana' => 5])
            ->assertSuccessful();

        $pedidos = Order::where('numero_pedido', 'like', 'DEMO-%');
        $this->assertGreaterThan(0, $pedidos->count());
        $this->assertSame(30, User::where('email', 'like', '%@demo.local')->count());
        $this->assertTrue($pedidos->get()->every(fn ($o) => in_array($o->estado, Order::ESTADOS_PAGADOS, true)));

        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertSee('demostración');

        $this->artisan('demo:ventas', ['--limpiar' => true])->assertSuccessful();
        $this->assertSame(0, Order::where('numero_pedido', 'like', 'DEMO-%')->count());
        $this->assertSame(0, User::where('email', 'like', '%@demo.local')->count());
    }

    #[Test]
    public function demo_ventas_no_corre_en_produccion_sin_force()
    {
        $this->app['env'] = 'production';

        $this->artisan('demo:ventas')->assertFailed();
        $this->assertSame(0, Order::where('numero_pedido', 'like', 'DEMO-%')->count());
    }
}
