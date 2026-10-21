<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaTurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const CAMPO = 'cf-turnstile-response';

    private function activarCaptcha(): void
    {
        config([
            'services.turnstile.site_key' => 'site-key-prueba',
            'services.turnstile.secret_key' => 'secret-key-prueba',
        ]);
    }

    private function cliente(): User
    {
        if (! Role::where('nombre', 'cliente')->exists()) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(['id' => 99, 'nombre' => 'cliente']);
        }

        return User::factory()->create([
            'email' => 'cliente@prueba.test',
            'password' => Hash::make('clave-segura-1'),
            'rol_id' => Role::where('nombre', 'cliente')->value('id'),
        ]);
    }

    private function datosLogin(array $extra = []): array
    {
        return ['email' => 'cliente@prueba.test', 'password' => 'clave-segura-1'] + $extra;
    }

    public function test_apagado_no_exige_nada_y_no_llama_a_cloudflare(): void
    {
        Http::fake();
        $this->cliente();

        $this->post('/login', $this->datosLogin())->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_activo_sin_token_rechaza_el_login(): void
    {
        $this->activarCaptcha();
        Http::fake();
        $this->cliente();

        $this->post('/login', $this->datosLogin())->assertSessionHasErrors(self::CAMPO);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_activo_con_token_rechazado_por_cloudflare_no_deja_entrar(): void
    {
        $this->activarCaptcha();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
        $this->cliente();

        $this->post('/login', $this->datosLogin([self::CAMPO => 'token-falso']))
            ->assertSessionHasErrors(self::CAMPO);

        $this->assertGuest();
    }

    public function test_activo_con_token_valido_deja_entrar_y_envia_clave_e_ip(): void
    {
        $this->activarCaptcha();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->cliente();

        $this->post('/login', $this->datosLogin([self::CAMPO => 'token-bueno']))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        Http::assertSent(fn ($r) => $r['secret'] === 'secret-key-prueba'
            && $r['response'] === 'token-bueno'
            && ! empty($r['remoteip']));
    }

    public function test_si_cloudflare_no_responde_se_deja_pasar_por_defecto(): void
    {
        $this->activarCaptcha();
        Http::fake(fn () => throw new ConnectionException('sin red'));
        $this->cliente();

        $this->post('/login', $this->datosLogin([self::CAMPO => 'token']))->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_si_cloudflare_no_responde_y_esta_cerrado_se_bloquea(): void
    {
        $this->activarCaptcha();
        config(['services.turnstile.fail_closed' => true]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response('', 503)]);
        $this->cliente();

        $this->post('/login', $this->datosLogin([self::CAMPO => 'token']))
            ->assertSessionHasErrors(self::CAMPO);

        $this->assertGuest();
    }

    public function test_el_registro_exige_captcha(): void
    {
        $this->activarCaptcha();
        Http::fake();
        $this->cliente();

        $this->post('/registro', [
            'name' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@prueba.test',
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1',
        ])->assertSessionHasErrors(self::CAMPO);

        $this->assertDatabaseMissing('users', ['email' => 'ana@prueba.test']);
    }

    public function test_el_checkout_exige_captcha_aunque_el_cliente_ya_este_logueado(): void
    {
        $this->activarCaptcha();
        Http::fake();
        $user = $this->cliente();

        $this->actingAs($user)->post(route('web.checkout.store'), [
            'tipo_entrega' => 'retiro',
            'entrega_nombre' => 'Ana',
            'entrega_apellido' => 'Pérez',
            'entrega_telefono' => '04141234567',
            'metodo_pago' => 'fisico',
        ])->assertSessionHasErrors(self::CAMPO);

        $this->assertSame(0, \App\Models\Order::count());
    }

    public function test_el_widget_solo_aparece_con_el_captcha_activo(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('cf-turnstile', false);

        $this->activarCaptcha();

        $this->get('/login')->assertOk()
            ->assertSee('class="cf-turnstile"', false)
            ->assertSee('data-sitekey="site-key-prueba"', false)
            ->assertDontSee('secret-key-prueba', false);
    }
}
