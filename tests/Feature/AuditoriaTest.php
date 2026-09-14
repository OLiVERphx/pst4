<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\BitacoraAuditoria;
use App\Services\ServicioValidacionPagos;
use App\Services\ServicioInventario;
use App\Services\ServicioPedidos;
use App\Services\ServicioAuditoria;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clienteUser;
    protected Role $adminRole;
    protected Role $clienteRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles del sistema con ID explícito
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'superadmin', 'permisos_deprecated' => json_encode(['*'])],
            ['id' => 2, 'nombre' => 'admin', 'permisos_deprecated' => json_encode(['orders', 'products', 'payments', 'inventory'])],
            ['id' => 3, 'nombre' => 'cliente', 'permisos_deprecated' => json_encode(['catalog', 'orders.own'])],
        ]);
        $this->adminRole = Role::find(2);
        $this->clienteRole = Role::find(3);

        // Crear roles y permisos Spatie
        $spatieAdmin = SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $spatieCliente = SpatieRole::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);
        $permisoAuditoria = Permission::firstOrCreate(['name' => 'auditoria.ver', 'guard_name' => 'web']);
        $spatieAdmin->givePermissionTo($permisoAuditoria);

        // Crear usuario admin
        $this->adminUser = User::create([
            'name' => 'Admin',
            'apellido' => 'Tester',
            'email' => 'admin@test.com',
            'rol_id' => $this->adminRole->id,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->adminUser->assignRole('admin');

        // Crear usuario cliente
        $this->clienteUser = User::create([
            'name' => 'Cliente',
            'apellido' => 'Tester',
            'email' => 'cliente@test.com',
            'rol_id' => $this->clienteRole->id,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->clienteUser->assignRole('cliente');
    }

    /**
     * Helper para crear un producto y un pedido con pago.
     */
    protected function crearPedidoConPago(): array
    {
        $brand = Brand::create(['nombre' => 'Apple', 'slug' => 'apple']);
        $cat = Category::create(['nombre' => 'Accesorios', 'slug' => 'accesorios']);

        $product = Product::create([
            'categoria_id' => $cat->id,
            'marca_id' => $brand->id,
            'codigo' => 'PROD-001',
            'nombre' => 'Funda Silicona iPhone',
            'slug' => 'funda-silicona-iphone',
            'precio_detal' => 15.00,
            'precio_mayor' => 10.00,
            'stock' => 50,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $order = Order::create([
            'numero_pedido' => 'SW-2026-0001',
            'usuario_id' => $this->clienteUser->id,
            'tipo' => 'detal',
            'estado' => 'pago_subido',
            'subtotal' => 15.00,
            'total' => 15.00,
            'entrega_nombre' => 'Cliente Tester',
            'entrega_telefono' => '0414-1234567',
            'entrega_direccion' => 'Av. Bolívar, Local 1',
            'entrega_ciudad' => 'Valera',
        ]);

        $orderItem = OrderItem::create([
            'pedido_id' => $order->id,
            'producto_id' => $product->id,
            'cantidad' => 1,
            'precio_unitario' => 15.00,
            'subtotal' => 15.00,
        ]);

        $payment = Payment::create([
            'pedido_id' => $order->id,
            'metodo' => 'pagomovil',
            'monto' => 15.00,
            'moneda' => 'USD',
            'numero_referencia' => 'REF-987654',
            'estado' => 'pendiente',
        ]);

        return [$product, $order, $payment];
    }

    /**
     * 1. Verificar que aprobar un pago genera una fila en bitacora_auditoria con datos correctos.
     */
    public function test_aprobar_pago_genera_fila_en_bitacora_auditoria(): void
    {
        [$product, $order, $payment] = $this->crearPedidoConPago();

        $servicioPagos = app(ServicioValidacionPagos::class);
        $servicioPagos->approve($payment, $this->adminUser);

        // Verificar cambio en el modelo Payment
        $payment->refresh();
        $this->assertEquals('valido', $payment->estado);
        $this->assertEquals($this->adminUser->id, $payment->verificado_por);

        // Verificar que existe el registro en bitacora_auditoria
        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'pago.aprobado',
            'entidad_tipo' => Payment::class,
            'entidad_id' => $payment->id,
            'usuario_id' => $this->adminUser->id,
        ]);

        $bitacora = BitacoraAuditoria::where('accion', 'pago.aprobado')
            ->where('entidad_id', $payment->id)
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertEquals('pendiente', $bitacora->valores_antes['estado']);
        $this->assertEquals('valido', $bitacora->valores_despues['estado']);
        $this->assertEquals($this->adminUser->id, $bitacora->valores_despues['verificado_por']);
    }

    /**
     * 2. Verificar que rechazar un pago genera una fila en bitacora_auditoria.
     */
    public function test_rechazar_pago_genera_fila_en_bitacora_auditoria(): void
    {
        [$product, $order, $payment] = $this->crearPedidoConPago();

        $servicioPagos = app(ServicioValidacionPagos::class);
        $servicioPagos->reject($payment, $this->adminUser, 'Referencia bancaria inválida');

        $payment->refresh();
        $this->assertEquals('invalido', $payment->estado);
        $this->assertEquals('Referencia bancaria inválida', $payment->motivo_rechazo);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'pago.rechazado',
            'entidad_tipo' => Payment::class,
            'entidad_id' => $payment->id,
            'usuario_id' => $this->adminUser->id,
        ]);

        $bitacora = BitacoraAuditoria::where('accion', 'pago.rechazado')->first();
        $this->assertEquals('Referencia bancaria inválida', $bitacora->valores_despues['motivo_rechazo']);
    }

    /**
     * 3. Verificar que ajuste manual de stock genera auditoría.
     */
    public function test_ajuste_inventario_genera_auditoria(): void
    {
        [$product, $order, $payment] = $this->crearPedidoConPago();

        $servicioInventario = app(ServicioInventario::class);
        $servicioInventario->ajustarStock($product, 80, 'Ajuste de inventario físico anual', $this->adminUser);

        $product->refresh();
        $this->assertEquals(80, $product->stock);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'inventario.ajustado',
            'entidad_tipo' => Product::class,
            'entidad_id' => $product->id,
            'usuario_id' => $this->adminUser->id,
        ]);

        $bitacora = BitacoraAuditoria::where('accion', 'inventario.ajustado')->first();
        $this->assertEquals(50, $bitacora->valores_antes['stock']);
        $this->assertEquals(80, $bitacora->valores_despues['stock']);
        $this->assertEquals('Ajuste de inventario físico anual', $bitacora->valores_despues['motivo']);
    }

    /**
     * 4. Verificar que cambios de estado de pedido y cancelación generan auditoría.
     */
    public function test_cambio_estado_pedido_y_cancelacion_generan_auditoria(): void
    {
        [$product, $order, $payment] = $this->crearPedidoConPago();

        $servicioPedidos = app(ServicioPedidos::class);
        $servicioPedidos->avanzarEstado($order, $this->adminUser);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'pedido.estado_cambiado',
            'entidad_tipo' => Order::class,
            'entidad_id' => $order->id,
            'usuario_id' => $this->adminUser->id,
        ]);

        $servicioPedidos->cancel($order, $this->adminUser, 'Cliente solicitó cancelación.');

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'pedido.cancelado',
            'entidad_tipo' => Order::class,
            'entidad_id' => $order->id,
            'usuario_id' => $this->adminUser->id,
        ]);
    }

    /**
     * 5. Verificar que usuario sin rol administrativo es redirigido a login.
     */
    public function test_usuario_no_admin_es_redirigido(): void
    {
        $response = $this->actingAs($this->clienteUser)->get('/admin/auditoria');
        $response->assertRedirect('/admin/login');
    }

    /**
     * 6. Verificar que usuario admin sin permiso 'auditoria.ver' recibe 403 Forbidden.
     */
    public function test_admin_sin_permiso_auditoria_recibe_403(): void
    {
        // Crear un rol spatie sin permiso de auditoría
        $spatieVendedor = SpatieRole::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        
        $adminSinPermiso = User::create([
            'name' => 'Vendedor',
            'apellido' => 'Mostrador',
            'email' => 'vendedor@test.com',
            'rol_id' => $this->adminRole->id, // Pasa MiddlewareAdmin
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $adminSinPermiso->assignRole('vendedor'); // No tiene 'auditoria.ver'

        $response = $this->actingAs($adminSinPermiso)->get('/admin/auditoria');
        $response->assertStatus(403);
    }

    /**
     * 7. Verificar que usuario con permiso 'auditoria.ver' recibe 200 OK.
     */
    public function test_usuario_con_permiso_auditoria_recibe_200(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/auditoria');
        $response->assertStatus(200);
        $response->assertSeeLivewire('datatables.bitacora-auditoria-table');
    }

    /**
     * 7. Caso límite: Fallo en BD de auditoría no tumba la operación de negocio.
     */
    public function test_fallo_en_auditoria_no_interrumpe_operacion_de_negocio(): void
    {
        [$product, $order, $payment] = $this->crearPedidoConPago();

        // Spy o listening en Log::critical
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Fallo crítico al registrar auditoría');
            });
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        // Simular excepción al llamar ServicioAuditoria::registrar directamente con payload inválido que falle en BD
        // O llamando con una tabla inexistente temporalmente
        $resultado = ServicioAuditoria::registrar(
            'accion.invalida',
            null,
            null,
            null,
            999999999 // Usuario inexistente que disparará fallo por FK si está en modo estricto, o lanzar excepción
        );

        // La aprobación de pago debe completarse satisfactoriamente
        $servicioPagos = app(ServicioValidacionPagos::class);
        $servicioPagos->approve($payment, $this->adminUser);

        $payment->refresh();
        $this->assertEquals('valido', $payment->estado);
    }

    /**
     * 8. Verificar que el modelo BitacoraAuditoria es inmutable (bloquea updates y deletes).
     */
    public function test_modelo_bitacora_es_inmutable(): void
    {
        $audit = BitacoraAuditoria::create([
            'usuario_id' => $this->adminUser->id,
            'accion' => 'test.inmutable',
            'entidad_tipo' => User::class,
            'entidad_id' => $this->adminUser->id,
            'created_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $audit->update(['accion' => 'modificado']);
    }

    public function test_modelo_bitacora_no_permite_eliminar(): void
    {
        $audit = BitacoraAuditoria::create([
            'usuario_id' => $this->adminUser->id,
            'accion' => 'test.inmutable.delete',
            'entidad_tipo' => User::class,
            'entidad_id' => $this->adminUser->id,
            'created_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $audit->delete();
    }
}
