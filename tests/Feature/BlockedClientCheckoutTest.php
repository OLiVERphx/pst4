<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class BlockedClientCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un cliente bloqueado debe recibir un mensaje claro al intentar pagar.
     */
    public function test_blocked_client_receives_clear_message_on_checkout_attempt()
    {
        // Asegurar existencia del rol 'cliente'
        // Crear rol 'cliente' asegurando un id para tablas que usan id no auto-incremental
        $role = Role::where('nombre', 'cliente')->first();
        if (! $role) {
            $role = Role::create(['id' => 99, 'nombre' => 'cliente']);
        }

        // Crear usuario con bloqueo activo en memoria (no persistir en BD) — suficiente para la comprobación de bloqueo
        $user = User::factory()->make([
            'rol_id' => $role->id,
            'bloqueado' => true,
        ]);

        $response = $this->actingAs($user)->post(route('web.checkout.store'), [
            'entrega_nombre' => 'Juan',
            'entrega_apellido' => 'Perez',
            'entrega_telefono' => '04141234567',
            'entrega_direccion' => 'Calle Falsa 123',
            'entrega_ciudad' => 'Caracas',
            'metodo_pago' => 'fisico',
        ]);

        $response->assertSessionHasErrors('blocked');
    }
}
