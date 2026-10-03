<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;
use App\Livewire\Datatables\VerificacionPagos;
use App\Livewire\Datatables\ProductsTable;
use App\Livewire\Datatables\InventoryMovementsTable;
use App\Livewire\Datatables\UsuariosTable;
use App\Livewire\Datatables\RespaldosTable;

class VendedorPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadminUser;
    protected User $adminUser;
    protected User $vendedorUser;
    protected User $clienteUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles del sistema con ID explícito
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'superadmin', 'permisos_deprecated' => json_encode(['*'])],
            ['id' => 2, 'nombre' => 'admin', 'permisos_deprecated' => json_encode(['orders', 'products', 'payments', 'inventory'])],
            ['id' => 3, 'nombre' => 'cliente', 'permisos_deprecated' => json_encode(['catalog', 'orders.own'])],
            ['id' => 4, 'nombre' => 'vendedor', 'permisos_deprecated' => json_encode(['orders', 'products', 'inventory'])],
        ]);

        // Crear roles Spatie
        $spatieSuperadmin = SpatieRole::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $spatieAdmin = SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $spatieVendedor = SpatieRole::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        // Crear permisos granulares
        $permisos = [
            'pagos.ver', 'pagos.aprobar', 'pagos.rechazar',
            'inventario.ver', 'inventario.ajustar',
            'pedidos.ver', 'pedidos.crear', 'pedidos.anular',
            'clientes.ver', 'clientes.editar', 'clientes.bloquear',
            'reportes.ver', 'auditoria.ver',
            'configuracion.editar',
            'productos.ver', 'productos.editar',
            'usuarios.gestionar',
            'respaldos.gestionar',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Superadmin recibe TODO
        $spatieSuperadmin->syncPermissions(Permission::all());

        // Admin recibe todo EXCEPTO usuarios y respaldos (solo superadmin)
        $spatieAdmin->syncPermissions(
            collect($permisos)->reject(fn($p) => in_array($p, ['usuarios.gestionar', 'respaldos.gestionar']))->values()->all()
        );

        // Vendedor recibe ventas, catálogo y verificación de pagos en local
        $spatieVendedor->syncPermissions([
            'pedidos.crear', 'pedidos.ver',
            'inventario.ver',
            'productos.ver',
            'clientes.ver',
            'pagos.ver', 'pagos.aprobar', 'pagos.rechazar',
        ]);

        // Crear superadmin
        $this->superadminUser = User::create([
            'name' => 'Super',
            'apellido' => 'Admin',
            'email' => 'superadmin@test.com',
            'rol_id' => 1,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->superadminUser->assignRole('superadmin');

        // Crear admin (dueño del negocio)
        $this->adminUser = User::create([
            'name' => 'Admin',
            'apellido' => 'Dueno',
            'email' => 'admin@test.com',
            'rol_id' => 2,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->adminUser->assignRole('admin');

        // Crear usuario vendedor
        $this->vendedorUser = User::create([
            'name' => 'Vendedor',
            'apellido' => 'Local',
            'email' => 'vendedor@test.com',
            'rol_id' => 4,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->vendedorUser->assignRole('vendedor');

        // Crear usuario cliente (dueño del pedido)
        $this->clienteUser = User::create([
            'name' => 'Cliente',
            'apellido' => 'Tester',
            'email' => 'cliente@test.com',
            'rol_id' => 3,
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);
        $this->clienteUser->assignRole('cliente');
    }

    protected function crearPedidoConPago(): array
    {
        $brand = Brand::create(['nombre' => 'Samsung', 'slug' => 'samsung']);
        $cat = Category::create(['nombre' => 'Fundas', 'slug' => 'fundas']);

        $product = Product::create([
            'categoria_id' => $cat->id,
            'marca_id' => $brand->id,
            'codigo' => 'PROD-V01',
            'nombre' => 'Funda Galaxy',
            'slug' => 'funda-galaxy',
            'precio_detal' => 10.00,
            'precio_mayor' => 7.00,
            'stock' => 30,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $order = Order::create([
            'numero_pedido' => 'SW-2026-V001',
            'usuario_id' => $this->clienteUser->id,
            'tipo' => 'detal',
            'estado' => 'pago_subido',
            'subtotal' => 10.00,
            'total' => 10.00,
            'entrega_nombre' => 'Cliente Tester',
            'entrega_telefono' => '0414-9999999',
            'entrega_direccion' => 'Av. Principal',
            'entrega_ciudad' => 'Caracas',
        ]);

        OrderItem::create([
            'pedido_id' => $order->id,
            'producto_id' => $product->id,
            'cantidad' => 1,
            'precio_unitario' => 10.00,
            'subtotal' => 10.00,
        ]);

        $payment = Payment::create([
            'pedido_id' => $order->id,
            'metodo' => 'pagomovil',
            'monto' => 10.00,
            'moneda' => 'USD',
            'numero_referencia' => 'REF-VEND-001',
            'estado' => 'pendiente',
        ]);

        return [$product, $order, $payment];
    }

    /**
     * Verificar que un vendedor SÍ puede verificar y aprobar pagos en local/tienda.
     */
    public function test_vendedor_puede_aprobar_pago(): void
    {
        [, , $payment] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(VerificacionPagos::class)
            ->call('approve', $payment->id)
            ->assertHasNoErrors();

        $this->assertEquals('valido', $payment->fresh()->estado);
    }

    /**
     * Verificar que un vendedor SÍ puede rechazar un pago.
     */
    public function test_vendedor_puede_rechazar_pago(): void
    {
        [, , $payment] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(VerificacionPagos::class)
            ->call('openReject', $payment->id)
            ->assertSet('selectedPayment', $payment->id)
            ->assertSet('showRejectModal', true);
    }

    /**
     * Verificar que un vendedor NO puede editar ni eliminar productos (requiere productos.editar).
     */
    public function test_vendedor_recibe_403_al_intentar_editar_o_borrar_producto(): void
    {
        [$product] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(ProductsTable::class)
            ->call('delete', $product->id)
            ->assertForbidden();

        Livewire::actingAs($this->vendedorUser)
            ->test(ProductsTable::class)
            ->call('openEdit', $product->id)
            ->assertForbidden();
    }

    /**
     * Verificar que un vendedor NO puede registrar ajustes de inventario (requiere inventario.ajustar).
     */
    public function test_vendedor_recibe_403_al_intentar_ajustar_inventario(): void
    {
        [$product] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(InventoryMovementsTable::class)
            ->call('openCreate', $product->id)
            ->assertForbidden();
    }

    /**
     * Verificar que Admin y Vendedor reciben 403 al intentar acceder a usuarios o respaldos.
     */
    public function test_admin_y_vendedor_no_pueden_gestionar_usuarios_ni_respaldos(): void
    {
        // Admin intenta acceder a usuarios
        $this->actingAs($this->adminUser)
            ->get('/admin/usuarios')
            ->assertForbidden();

        // Admin intenta acceder a respaldos
        $this->actingAs($this->adminUser)
            ->get('/admin/respaldos')
            ->assertForbidden();

        // Vendedor intenta acceder a usuarios
        $this->actingAs($this->vendedorUser)
            ->get('/admin/usuarios')
            ->assertForbidden();

        // Vendedor intenta acceder a respaldos
        $this->actingAs($this->vendedorUser)
            ->get('/admin/respaldos')
            ->assertForbidden();
    }

    /**
     * Verificar que Superadmin tiene acceso a la gestión de usuarios y respaldos.
     */
    public function test_superadmin_puede_gestionar_usuarios_y_respaldos(): void
    {
        $this->actingAs($this->superadminUser)
            ->get('/admin/usuarios')
            ->assertOk();

        $this->actingAs($this->superadminUser)
            ->get('/admin/respaldos')
            ->assertOk();

        // Probar componente Livewire de Respaldos
        Livewire::actingAs($this->superadminUser)
            ->test(RespaldosTable::class)
            ->call('generar')
            ->assertHasNoErrors();

        // Probar componente Livewire de Usuarios
        Livewire::actingAs($this->superadminUser)
            ->test(UsuariosTable::class)
            ->assertSee($this->superadminUser->name);
    }
}
