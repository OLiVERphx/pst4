<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
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
}
