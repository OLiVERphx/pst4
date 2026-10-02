<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\BitacoraAuditoria;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'superadmin', 'permisos_deprecated' => json_encode(['*'])],
            ['id' => 2, 'nombre' => 'admin', 'permisos_deprecated' => json_encode(['*'])],
            ['id' => 3, 'nombre' => 'cliente', 'permisos_deprecated' => json_encode(['catalog', 'orders.own'])],
        ]);

        $this->cliente = User::create([
            'rol_id'    => 3,
            'name'      => 'Carlos',
            'apellido'  => 'Mendoza',
            'email'     => 'carlos@ejemplo.com',
            'password'  => Hash::make('secreto123'),
            'cedula'    => 'V-20123456',
            'telefono'  => '0412-1234567',
            'ciudad'    => 'Valera',
            'direccion' => 'Calle 5 con Av 3',
            'activo'    => true,
            'estado'    => 'Trujillo',
        ]);
    }

    /**
     * Un invitado no puede acceder a /mi-cuenta ni modificar el perfil.
     */
    public function test_invitado_es_redirigido_al_login()
    {
        $response = $this->get('/mi-cuenta');
        $response->assertRedirect('/login');

        $responsePut = $this->put('/mi-cuenta/perfil', [
            'name' => 'Hack',
            'apellido' => 'User',
            'email' => 'hack@ejemplo.com',
        ]);
        $responsePut->assertRedirect('/login');
    }

    /**
     * Un cliente autenticado puede ver sus datos en /mi-cuenta.
     */
    public function test_cliente_puede_ver_su_cuenta_con_datos_cargados()
    {
        $response = $this->actingAs($this->cliente)->get('/mi-cuenta');

        $response->assertStatus(200);
        $response->assertSee('Mi cuenta');
        $response->assertSee('Carlos');
        $response->assertSee('Mendoza');
        $response->assertSee('carlos@ejemplo.com');
        $response->assertSee('V-20123456');
    }

    /**
     * Un cliente puede actualizar exitosamente sus datos de perfil.
     */
    public function test_cliente_puede_actualizar_sus_datos_exitosamente()
    {
        $response = $this->actingAs($this->cliente)->put('/mi-cuenta/perfil', [
            'name'      => 'Carlos Alberto',
            'apellido'  => 'Mendoza Rivas',
            'email'     => 'carlos.mendoza@ejemplo.com',
            'cedula'    => 'V-20123456',
            'telefono'  => '0414-9876543',
            'ciudad'    => 'Trujillo',
            'direccion' => 'Av. Bolívar, edif. Los Andes, piso 2',
        ]);

        $response->assertRedirect(route('web.account'));
        $response->assertSessionHas('success', 'Tus datos han sido actualizados correctamente.');

        $this->cliente->refresh();
        $this->assertEquals('Carlos Alberto', $this->cliente->name);
        $this->assertEquals('Mendoza Rivas', $this->cliente->apellido);
        $this->assertEquals('carlos.mendoza@ejemplo.com', $this->cliente->email);
        $this->assertEquals('0414-9876543', $this->cliente->telefono);
        $this->assertEquals('Trujillo', $this->cliente->ciudad);
        $this->assertEquals('Av. Bolívar, edif. Los Andes, piso 2', $this->cliente->direccion);

        // Verifica que se haya generado bitácora de auditoría
        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion'      => 'usuario.perfil_actualizado',
            'usuario_id'  => $this->cliente->id,
            'entidad_id'  => $this->cliente->id,
        ]);
    }

    /**
     * Falla de validación si faltan campos obligatorios o formato inválido.
     */
    public function test_falla_validacion_con_datos_invalidos()
    {
        $response = $this->actingAs($this->cliente)->put('/mi-cuenta/perfil', [
            'name'      => '', // Obligatorio
            'apellido'  => '', // Obligatorio
            'email'     => 'correo-no-valido',
        ]);

        $response->assertSessionHasErrors(['name', 'apellido', 'email']);
    }

    /**
     * Falla si intenta usar un email ya registrado por otro usuario.
     */
    public function test_falla_si_email_pertenece_a_otro_usuario()
    {
        User::create([
            'rol_id'    => 3,
            'name'      => 'Otro',
            'apellido'  => 'Usuario',
            'email'     => 'otro@ejemplo.com',
            'password'  => Hash::make('password123'),
            'activo'    => true,
            'estado'    => 'Trujillo',
        ]);

        $response = $this->actingAs($this->cliente)->put('/mi-cuenta/perfil', [
            'name'      => 'Carlos',
            'apellido'  => 'Mendoza',
            'email'     => 'otro@ejemplo.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
