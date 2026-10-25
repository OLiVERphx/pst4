<?php

namespace Tests\Feature;

use App\Http\Controllers\Web\RecuperarContrasenaController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RecuperarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.recuperacion_disponible' => true]);
    }

    public function test_sin_correo_configurado_no_se_ofrece_la_recuperacion(): void
    {
        config(['mail.recuperacion_disponible' => false]);

        $this->get(route('web.login'))->assertDontSee(route('password.request'), false);
        $this->get(route('admin.login'))->assertDontSee(route('password.request'), false);
        $this->get(route('password.request'))->assertNotFound();
    }

    public function test_los_inicios_de_sesion_ofrecen_recuperar_la_contrasena(): void
    {
        $this->get(route('web.login'))->assertSee(route('password.request'), false);
        $this->get(route('admin.login'))->assertSee(route('password.request'), false);
    }

    public function test_envia_el_enlace_sin_revelar_si_el_correo_existe(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@prueba.test']);

        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'ana@prueba.test'])
            ->assertSessionHas('status', RecuperarContrasenaController::MENSAJE_ENVIADO);
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $correo = $n->toMail($user);
            return $correo->subject === 'Restablecer tu contraseña - ' . config('app.name')
                && str_contains($correo->actionUrl, '/restablecer-contrasena/');
        });

        // Mismo mensaje para un correo que no existe
        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nadie@prueba.test'])
            ->assertSessionHas('status', RecuperarContrasenaController::MENSAJE_ENVIADO);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_cambia_la_contrasena_con_un_enlace_valido_una_sola_vez(): void
    {
        $user = User::factory()->create(['email' => 'ana@prueba.test', 'password' => Hash::make('vieja-clave-1')]);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee('Crea tu nueva contraseña');

        $datos = ['token' => $token, 'email' => $user->email, 'password' => 'nueva-clave-2', 'password_confirmation' => 'nueva-clave-2'];
        $this->post(route('password.update'), $datos)
            ->assertRedirect(route('web.login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('nueva-clave-2', $user->fresh()->password));
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'password.restablecida']);

        // El mismo enlace no sirve dos veces
        $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), $datos)
            ->assertSessionHasErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);
    }
}
