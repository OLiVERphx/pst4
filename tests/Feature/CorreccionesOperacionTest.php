<?php

namespace Tests\Feature;

use App\Livewire\Datatables\ClientsTable;
use App\Livewire\Datatables\InventoryMovementsTable;
use App\Livewire\Datatables\OrdersTable;
use App\Livewire\Datatables\StockAlerts;
use App\Models\Configuracion;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockAlert;
use App\Models\User;
use App\Services\ServicioPedidos;
use Database\Seeders\SeederRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fallas encontradas al revisar el sistema contra el manual de usuario.
 */
class CorreccionesOperacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeederRoles::class);
        config(['auth.dos_factores.obligatorio' => false]);
        Configuracion::create(['minimo_compra_mayor_usd' => 50, 'minimo_unidades_mayor' => 10]);
    }

    private function usuario(string $rol, array $datos = []): User
    {
        $user = User::factory()->create($datos + ['rol_id' => Role::where('nombre', $rol)->value('id'), 'activo' => true]);
        $user->assignRole($rol);

        return $user;
    }

    private function producto(array $datos = []): Product
    {
        return Product::factory()->create($datos + ['activo' => true, 'stock' => 10, 'stock_reservado' => 0, 'stock_minimo' => 2,
            'precio_detal' => 10, 'precio_mayor' => 8, 'min_cantidad_mayor' => 5]);
    }

    private function datosCheckout(Product $p, int $cantidad, array $extra = []): array
    {
        return $extra + [
            'tipo_entrega' => 'retiro', 'entrega_nombre' => 'Ana', 'entrega_apellido' => 'López', 'entrega_telefono' => '04141234567',
            'metodo_pago' => 'fisico',
            'items' => json_encode([['id' => $p->id, 'cantidad' => $cantidad, 'tipo' => 'detal']]),
        ];
    }

    // -- 1. Checkout ------------------------------------------------------------

    public function test_el_checkout_muestra_por_que_no_se_pudo_enviar_el_pedido(): void
    {
        $cliente = $this->usuario('cliente', ['bloqueado' => true]);
        $p = $this->producto();

        $this->actingAs($cliente)->from(route('web.checkout'))
            ->followingRedirects()
            ->post(route('web.checkout.store'), $this->datosCheckout($p, 1))
            ->assertSee('No se pudo enviar el pedido')
            ->assertSee('Su cuenta está bloqueada');
    }

    public function test_se_pueden_comprar_las_ultimas_unidades_reservadas_y_la_reserva_no_se_acumula(): void
    {
        $cliente = $this->usuario('cliente');
        $p = $this->producto(['stock' => 4]);
        $items = [['id' => $p->id, 'cantidad' => 4, 'tipo' => 'detal']];

        // Entrar dos veces al checkout no reserva dos veces
        $this->actingAs($cliente)->postJson(route('web.checkout.reserve'), ['items' => $items])->assertOk();
        $this->actingAs($cliente)->postJson(route('web.checkout.reserve'), ['items' => $items])->assertOk();
        $this->assertSame(4, $p->fresh()->stock_reservado);

        $this->actingAs($cliente)->post(route('web.checkout.store'), $this->datosCheckout($p, 4))
            ->assertRedirect()->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame(0, $p->stock);
        $this->assertSame(0, $p->stock_reservado);
    }

    public function test_al_mayor_se_exige_la_cantidad_minima_del_producto(): void
    {
        $cliente = $this->usuario('cliente');
        $p = $this->producto(['min_cantidad_mayor' => 12, 'precio_mayor' => 8]);

        $this->actingAs($cliente)->post(route('web.checkout.store'), $this->datosCheckout($p, 7, [
            'items' => json_encode([['id' => $p->id, 'cantidad' => 7, 'tipo' => 'mayor']]),
        ]))->assertSessionHasErrors(['stock' => 'Para comprar ' . $p->nombre . ' al mayor se necesitan al menos 12 unidades (llevas 7).']);
    }

    public function test_un_cliente_desactivado_no_puede_iniciar_sesion_ni_comprar(): void
    {
        $cliente = $this->usuario('cliente', ['activo' => false, 'password' => bcrypt('clave-segura-1')]);

        $this->post(route('web.login'), ['email' => $cliente->email, 'password' => 'clave-segura-1'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Comunícate con Smartphone World.']);
        $this->assertGuest();

        // Con una sesión abierta de antes, al desactivarlo se le cierra
        $this->actingAs($cliente)->get(route('web.checkout'))->assertRedirect(route('web.login'));
        $this->assertGuest();
    }

    // -- 2. Pedidos -------------------------------------------------------------

    public function test_el_detalle_del_pedido_muestra_los_datos_de_entrega(): void
    {
        $admin = $this->usuario('admin');
        $p = $this->producto();
        $pedido = app(ServicioPedidos::class)->crearPedidoDesdeLineas([['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 10]], null, $admin->id, 'online', 'Tocar el timbre', [
            'tipo_entrega' => 'delivery', 'entrega_nombre' => 'Ana López', 'entrega_telefono' => '04141234567',
            'entrega_direccion' => 'Av. Bolívar, casa 12', 'entrega_ciudad' => 'Valera',
        ]);

        Livewire::actingAs($admin)->test(OrdersTable::class)->call('openDetail', $pedido->id)
            ->assertSee('Av. Bolívar, casa 12')->assertSee('04141234567')->assertSee('Tocar el timbre');
    }

    public function test_cancelar_exige_motivo_y_no_se_repite_sobre_un_pedido_cancelado(): void
    {
        $admin = $this->usuario('admin');
        $p = $this->producto(['stock' => 10]);
        $pedido = app(ServicioPedidos::class)->crearPedidoDesdeLineas([['producto_id' => $p->id, 'cantidad' => 3, 'precio' => 10]], null, $admin->id);
        $this->assertSame(7, $p->fresh()->stock);

        $tabla = Livewire::actingAs($admin)->test(OrdersTable::class)->call('openDetail', $pedido->id);
        $tabla->call('cancel', $pedido->id)->assertHasErrors('motivoCancelacion');
        $this->assertSame('pendiente', $pedido->fresh()->estado);

        $tabla->set('motivoCancelacion', 'El cliente no retiró')->call('cancel', $pedido->id)->assertHasNoErrors();
        $this->assertSame('cancelado', $pedido->fresh()->estado);
        $this->assertSame(10, $p->fresh()->stock);

        // Cancelarlo otra vez devolvía el stock de nuevo
        $tabla->call('cancel', $pedido->id, 'otra vez')->assertHasErrors('motivoCancelacion');
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_un_pedido_completado_no_se_puede_cancelar(): void
    {
        $admin = $this->usuario('admin');
        $p = $this->producto(['stock' => 10]);
        $pedido = app(ServicioPedidos::class)->crearPedidoDesdeLineas([['producto_id' => $p->id, 'cantidad' => 2, 'precio' => 10]], null, $admin->id);
        $pedido->update(['estado' => 'completado']);

        $this->expectException(\RuntimeException::class);
        app(ServicioPedidos::class)->cancel($pedido->fresh(), $admin, 'error');
    }

    // -- 3. Inventario y clientes -------------------------------------------------

    public function test_marcar_alerta_como_leida(): void
    {
        $admin = $this->usuario('admin');
        $alerta = StockAlert::create(['producto_id' => $this->producto()->id, 'tipo' => 'low_stock', 'leido' => false]);

        Livewire::actingAs($admin)->test(StockAlerts::class)->call('markAsRead', $alerta->id)->assertHasNoErrors();
        $this->assertTrue($alerta->fresh()->leido);
    }

    public function test_ajuste_es_un_conteo_fisico_y_puede_bajar_el_stock(): void
    {
        $admin = $this->usuario('admin');
        $p = $this->producto(['stock' => 10]);

        Livewire::actingAs($admin)->test(InventoryMovementsTable::class)
            ->call('openCreate', $p->id)
            ->set('form.tipo', 'ajuste')->set('form.cantidad', 7)->set('form.notas', 'Conteo físico')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(7, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $p->id, 'tipo' => 'ajuste', 'cantidad' => -3, 'cantidad_nueva' => 7]);
    }

    public function test_la_ficha_del_cliente_muestra_sus_pagos_rechazados(): void
    {
        $admin = $this->usuario('admin');
        $cliente = $this->usuario('cliente');
        $p = $this->producto();
        $pedido = app(ServicioPedidos::class)->crearPedidoDesdeLineas([['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 10]], $cliente->id, $admin->id);
        Payment::create(['pedido_id' => $pedido->id, 'metodo' => 'pagomovil', 'monto' => 10, 'moneda' => 'USD', 'estado' => 'invalido', 'numero_referencia' => 'REF-RECHAZADA']);

        Livewire::actingAs($admin)->test(ClientsTable::class)->call('openDetail', $cliente->id)
            ->assertDontSee('No se encontraron pagos rechazados');
    }

    // -- 4. Botones según permisos --------------------------------------------------

    public function test_el_vendedor_no_ve_acciones_que_no_puede_usar(): void
    {
        $vendedor = $this->usuario('vendedor');
        $this->producto();

        $this->actingAs($vendedor)->get(route('admin.productos.lista'))
            ->assertOk()->assertDontSee('wire:click="openCreate"', false)->assertDontSee('wire:click="delete(', false)->assertDontSee('Importar Excel');
        $this->actingAs($vendedor)->get(route('admin.pedidos.lista'))
            ->assertOk()->assertDontSee(route('admin.reports.index'));
        $this->actingAs($vendedor)->get(route('admin.inventario.lista'))
            ->assertOk()->assertDontSee('wire:click="openCreate"', false);
    }
}
