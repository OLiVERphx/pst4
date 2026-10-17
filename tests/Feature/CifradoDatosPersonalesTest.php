<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Livewire\Livewire;
use App\Models\User;
use App\Models\BitacoraAuditoria;
use App\Livewire\Datatables\ClientsTable;
use App\Services\ServicioAuditoria;

/**
 * Cédula, teléfono y dirección se guardan cifrados: quien lea la base
 * directamente no los ve, pero el sistema sí puede usarlos y buscarlos.
 */
class CifradoDatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function en_la_base_los_datos_personales_no_estan_en_claro()
    {
        $user = User::factory()->create([
            'cedula' => 'V-12345678',
            'telefono' => '0414-1234567',
            'direccion' => 'Av. Bolívar, Valera',
        ]);

        $crudo = DB::table('users')->where('id', $user->id)->first();
        foreach (['cedula' => '12345678', 'telefono' => '1234567', 'direccion' => 'Bolívar'] as $col => $fragmento) {
            $this->assertStringNotContainsString($fragmento, (string) $crudo->{$col}, "{$col} quedó en claro");
        }
        $this->assertNotNull($crudo->cedula_hash);

        // El sistema los lee normalmente
        $user = $user->fresh();
        $this->assertSame('V-12345678', $user->cedula);
        $this->assertSame('0414-1234567', $user->telefono);
        $this->assertSame('Av. Bolívar, Valera', $user->direccion);
    }

    #[Test]
    public function se_busca_por_cedula_o_telefono_exactos_en_cualquier_formato()
    {
        $user = User::factory()->create(['cedula' => 'V-12345678', 'telefono' => '0414-1234567']);

        $this->assertSame($user->id, User::porCedula('12345678')->value('id'));
        $this->assertSame($user->id, User::porCedula('v 12.345.678')->value('id'));
        $this->assertSame($user->id, User::porTelefono('+58 414 1234567')->value('id'));
        $this->assertNull(User::porCedula('87654321')->value('id'));
        // Sin dígitos no debe coincidir con usuarios sin cédula
        User::factory()->create(['cedula' => null]);
        $this->assertNull(User::porCedula('abc')->value('id'));
    }

    #[Test]
    public function no_se_puede_registrar_una_cedula_repetida_aunque_cambie_el_formato()
    {
        User::factory()->create(['cedula' => 'V-12345678']);

        $this->post(route('web.register'), [
            'name' => 'Otro',
            'apellido' => 'Cliente',
            'email' => 'otro@example.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
            'cedula' => '12345678',
        ])->assertSessionHasErrors('cedula');
    }

    #[Test]
    public function el_admin_encuentra_un_cliente_por_su_cedula()
    {
        $this->seed();
        $admin = User::where('email', 'admin@swworld.com')->firstOrFail();

        // Cliente del seeder con cédula V-10123456
        Livewire::actingAs($admin)
            ->test(ClientsTable::class)
            ->set('search', '10123456')
            ->assertSee('carlos.gonzalez@example.com')
            ->assertDontSee('maria.rodriguez@example.com');
    }

    #[Test]
    public function la_bitacora_no_copia_los_datos_personales_en_claro()
    {
        $user = User::factory()->create(['telefono' => '0414-1234567']);
        $antes = $user->only(['name', 'telefono']);
        $user->update(['telefono' => '0424-7654321']);

        ServicioAuditoria::registrar('cliente.editado', $user, $antes, $user->only(['name', 'telefono']));

        $fila = BitacoraAuditoria::latest('id')->first();
        $json = json_encode([$fila->valores_antes, $fila->valores_despues]);
        $this->assertStringNotContainsString('1234567', $json);
        $this->assertStringNotContainsString('7654321', $json);
        $this->assertSame('[protegido: modificado]', $fila->valores_despues['telefono']);
        $this->assertSame($user->name, $fila->valores_despues['name']);
    }
}
