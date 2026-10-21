<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccesoPanelTest extends TestCase
{
    use RefreshDatabase;

    private function rutaPanelCon(?string $valor): string
    {
        $anterior = $_SERVER['ADMIN_PATH'] ?? null;
        if ($valor === null) {
            unset($_SERVER['ADMIN_PATH'], $_ENV['ADMIN_PATH']);
        } else {
            $_SERVER['ADMIN_PATH'] = $_ENV['ADMIN_PATH'] = $valor;
        }

        try {
            return (require base_path('config/app.php'))['admin_path'];
        } finally {
            if ($anterior === null) {
                unset($_SERVER['ADMIN_PATH'], $_ENV['ADMIN_PATH']);
            } else {
                $_SERVER['ADMIN_PATH'] = $_ENV['ADMIN_PATH'] = $anterior;
            }
        }
    }

    public function test_la_ruta_del_panel_se_normaliza_y_un_valor_invalido_vuelve_a_admin(): void
    {
        $this->assertSame('admin', $this->rutaPanelCon(null));
        $this->assertSame('gestion-sw-7f3k', $this->rutaPanelCon('/Gestion-SW-7f3k/'));
        $this->assertSame('admin', $this->rutaPanelCon('con espacios'));
        $this->assertSame('admin', $this->rutaPanelCon('../otra'));
        $this->assertSame('admin', $this->rutaPanelCon('ab'));
    }

    public function test_las_rutas_del_panel_usan_la_ruta_configurada(): void
    {
        $this->assertStringEndsWith('/'.config('app.admin_path').'/login', route('admin.login'));
        $this->assertStringEndsWith('/'.config('app.admin_path').'/dashboard', route('admin.dashboard'));
    }

    public function test_el_login_del_panel_no_se_indexa_y_exige_captcha_si_esta_activo(): void
    {
        $this->get(route('admin.login'))->assertOk()
            ->assertSee('noindex', false)
            ->assertDontSee('cf-turnstile', false);

        config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secreta']);
        Http::fake();

        $this->get(route('admin.login'))->assertSee('class="cf-turnstile"', false);
        $this->post(route('admin.login'), ['email' => 'admin@prueba.test', 'password' => 'una-clave-123'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_el_login_de_la_api_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotSame(429, $this->postJson('/api/auth/login', ['email' => 'x@prueba.test', 'password' => 'clave-mala-1'])->status());
        }

        $this->postJson('/api/auth/login', ['email' => 'x@prueba.test', 'password' => 'clave-mala-1'])->assertStatus(429);
    }
}
