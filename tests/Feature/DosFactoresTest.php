<?php

namespace Tests\Feature;

use App\Http\Middleware\MiddlewareDosFactores;
use App\Livewire\Datatables\UsuariosTable;
use App\Models\Role;
use App\Models\User;
use App\Services\ServicioDosFactores;
use Database\Seeders\SeederRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class DosFactoresTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'clave-segura-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeederRoles::class);
    }

    private function usuario(string $rol, string $email = null): User
    {
        $user = User::factory()->create([
            'email' => $email ?? "{$rol}@prueba.test",
            'password' => Hash::make(self::CLAVE),
            'rol_id' => Role::where('nombre', $rol)->value('id'),
            'activo' => true,
        ]);
        $user->assignRole($rol);

        return $user;
    }

    /** Activa 2FA directamente y devuelve [secreto, códigos de recuperación]. */
    private function conDosFactores(User $user): array
    {
        $servicio = app(ServicioDosFactores::class);
        $secreto = $servicio->generarSecreto();
        $codigos = $servicio->activar($user, $secreto, 0);

        return [$secreto, $codigos];
    }

    private function codigoActual(string $secreto): string
    {
        return (new Google2FA())->getCurrentOtp($secreto);
    }

    private function entrarAlPanel(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.login'), ['email' => $email, 'password' => self::CLAVE]);
    }

    // -- Obligatoriedad ------------------------------------------------------

    public function test_admin_sin_2fa_es_llevado_a_activarlo_cuando_es_obligatorio(): void
    {
        config(['auth.dos_factores.obligatorio' => true]);
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.configurar'));
        $this->actingAs($admin)->get(route('admin.2fa.configurar'))->assertOk()->assertSee('<svg', false);
    }

    public function test_vendedor_no_esta_obligado_y_si_no_es_obligatorio_nadie_lo_esta(): void
    {
        config(['auth.dos_factores.obligatorio' => true]);
        $this->actingAs($this->usuario('vendedor'))->get(route('admin.perfil'))->assertOk();

        config(['auth.dos_factores.obligatorio' => false]);
        $this->actingAs($this->usuario('superadmin'))->get(route('admin.perfil'))->assertOk();
    }

    // -- Activación ------------------------------------------------------

    public function test_activar_exige_un_codigo_correcto_y_muestra_los_codigos_una_sola_vez(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->get(route('admin.2fa.configurar'))->assertOk();
        $secreto = session('dos_factores.secreto_pendiente');
        $this->assertNotEmpty($secreto);

        $this->post(route('admin.2fa.activar'), ['codigo' => '000000'])->assertSessionHasErrors('codigo');
        $this->assertFalse($admin->fresh()->tieneDosFactores());

        $this->post(route('admin.2fa.activar'), ['codigo' => $this->codigoActual($secreto)])
            ->assertRedirect(route('admin.2fa.codigos'));

        $admin->refresh();
        $this->assertTrue($admin->tieneDosFactores());
        $this->assertSame($secreto, $admin->two_factor_secret);
        $this->assertNotSame($secreto, \DB::table('users')->where('id', $admin->id)->value('two_factor_secret'), 'El secreto debe guardarse cifrado');

        $codigos = session('dos_factores.codigos_nuevos');
        $this->assertCount(ServicioDosFactores::CANTIDAD_CODIGOS, $codigos);
        $pagina = $this->get(route('admin.2fa.codigos'))->assertOk();
        foreach ($codigos as $codigo) {
            $pagina->assertSee($codigo);
        }
        $this->assertStringNotContainsString($codigos[0], (string) \DB::table('users')->where('id', $admin->id)->value('two_factor_recovery_codes'));

        // Al recargar ya no se muestran
        $this->get(route('admin.2fa.codigos'))->assertRedirect(route('admin.perfil'));
    }

    // -- Inicio de sesión ------------------------------------------------------

    public function test_con_2fa_el_login_pide_el_codigo_antes_del_panel(): void
    {
        $admin = $this->usuario('admin');
        [$secreto] = $this->conDosFactores($admin);

        $this->entrarAlPanel($admin->email)->assertRedirect(route('admin.2fa.desafio'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.desafio'));
        $this->get(route('admin.2fa.desafio'))->assertOk()->assertSee('Verificación en dos pasos');

        $this->post(route('admin.2fa.verificar'), ['codigo' => '123456'])->assertSessionHasErrors('codigo');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.desafio'));

        $this->post(route('admin.2fa.verificar'), ['codigo' => $this->codigoActual($secreto)])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_un_codigo_ya_usado_no_sirve_otra_vez(): void
    {
        $admin = $this->usuario('admin');
        [$secreto] = $this->conDosFactores($admin);
        $codigo = $this->codigoActual($secreto);

        $this->entrarAlPanel($admin->email);
        $this->post(route('admin.2fa.verificar'), ['codigo' => $codigo])->assertSessionHasNoErrors();

        $this->post(route('admin.logout'));
        $this->entrarAlPanel($admin->email);
        $this->post(route('admin.2fa.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.desafio'));
    }

    public function test_el_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        $admin = $this->usuario('admin');
        [, $codigos] = $this->conDosFactores($admin);

        $this->entrarAlPanel($admin->email);
        $this->post(route('admin.2fa.verificar'), ['codigo' => strtoupper($codigos[0]), 'recuperacion' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
        $this->assertSame(ServicioDosFactores::CANTIDAD_CODIGOS - 1, app(ServicioDosFactores::class)->codigosRestantes($admin->fresh()));

        $this->post(route('admin.logout'));
        $this->entrarAlPanel($admin->email);
        $this->post(route('admin.2fa.verificar'), ['codigo' => $codigos[0], 'recuperacion' => 1])->assertSessionHasErrors('codigo');
    }

    public function test_entrar_por_el_login_de_la_tienda_no_salta_el_codigo(): void
    {
        $admin = $this->usuario('admin');
        $this->conDosFactores($admin);

        $this->post('/login', ['email' => $admin->email, 'password' => self::CLAVE]);
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.desafio'));
        $this->get(route('admin.usuarios.index'))->assertRedirect(route('admin.2fa.desafio'));
    }

    public function test_volver_a_entrar_en_la_misma_sesion_pide_el_codigo_de_nuevo(): void
    {
        $admin = $this->usuario('admin');
        [$secreto] = $this->conDosFactores($admin);

        $this->entrarAlPanel($admin->email);
        $this->post(route('admin.2fa.verificar'), ['codigo' => $this->codigoActual($secreto)]);
        $this->assertSame($admin->id, session(MiddlewareDosFactores::SESION));

        // Otro inicio de sesión (p. ej. sin cerrar la anterior) borra la verificación
        $this->entrarAlPanel($admin->email);
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.desafio'));
    }

    public function test_sin_codigo_las_acciones_que_no_son_get_dan_403(): void
    {
        $superadmin = $this->usuario('superadmin');
        $this->conDosFactores($superadmin);

        // Incluye las de Livewire (middleware persistente): no se ejecuta nada a medias
        $this->actingAs($superadmin)
            ->post(route('admin.2fa.regenerar'), ['password_actual' => self::CLAVE])
            ->assertForbidden();
        $this->actingAs($superadmin)
            ->delete(route('admin.2fa.desactivar'), ['password_actual' => self::CLAVE])
            ->assertForbidden();
        $this->assertTrue($superadmin->fresh()->tieneDosFactores());
        $this->assertContains(MiddlewareDosFactores::class, \Livewire\Livewire::getPersistentMiddleware());
    }

    public function test_la_api_no_entrega_token_a_cuentas_con_2fa(): void
    {
        $admin = $this->usuario('admin');
        $this->conDosFactores($admin);

        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => self::CLAVE])->assertForbidden();
        $this->assertSame(0, $admin->tokens()->count());
    }

    // -- Gestión ------------------------------------------------------

    public function test_desactivar_y_regenerar_exigen_la_contrasena(): void
    {
        $vendedor = $this->usuario('vendedor');
        [$secreto] = $this->conDosFactores($vendedor);
        $this->actingAs($vendedor)->withSession([MiddlewareDosFactores::SESION => $vendedor->id]);

        $this->delete(route('admin.2fa.desactivar'), ['password_actual' => 'otra-clave-999'])->assertSessionHasErrors('password_actual', null, 'dosFactores');
        $this->assertTrue($vendedor->fresh()->tieneDosFactores());

        $this->post(route('admin.2fa.regenerar'), ['password_actual' => self::CLAVE])->assertRedirect(route('admin.2fa.codigos'));

        $this->delete(route('admin.2fa.desactivar'), ['password_actual' => self::CLAVE])->assertRedirect(route('admin.perfil'));
        $this->assertFalse($vendedor->fresh()->tieneDosFactores());
    }

    public function test_el_superadmin_restablece_el_2fa_de_otro_usuario(): void
    {
        $superadmin = $this->usuario('superadmin');
        $vendedor = $this->usuario('vendedor');
        $this->conDosFactores($vendedor);

        Livewire::actingAs($superadmin)->test(UsuariosTable::class)
            ->call('restablecerDosFactores', $vendedor->id)
            ->assertHasNoErrors();

        $this->assertFalse($vendedor->fresh()->tieneDosFactores());
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => '2fa.restablecido', 'entidad_id' => $vendedor->id]);
    }

    public function test_comando_de_consola_para_el_ultimo_recurso(): void
    {
        $admin = $this->usuario('superadmin');
        $this->conDosFactores($admin);

        $this->artisan('usuarios:restablecer-2fa', ['email' => $admin->email])->assertSuccessful();
        $this->assertFalse($admin->fresh()->tieneDosFactores());

        $this->artisan('usuarios:restablecer-2fa', ['email' => 'nadie@prueba.test'])->assertFailed();
    }
}
