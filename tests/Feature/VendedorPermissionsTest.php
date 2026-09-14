<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
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

class VendedorPermissionsTest extends TestCase
{
    use RefreshDatabase;

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

        // Crear roles y permisos Spatie
        $spatieAdmin = SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $spatieVendedor = SpatieRole::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        // Crear permisos granulares
        $permisos = [
            'pagos.ver', 'pagos.aprobar', 'pagos.rechazar',
            'inventario.ver', 'inventario.ajustar',
            'pedidos.ver', 'pedidos.crear', 'pedidos.anular',
            'clientes.ver', 'clientes.editar',
            'reportes.ver', 'auditoria.ver',
            'configuracion.editar',
            'productos.ver', 'productos.editar',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Admin recibe todo
        $spatieAdmin->syncPermissions(Permission::all());

        // Vendedor recibe solo lo permitido (NO incluye pagos.aprobar ni pagos.rechazar)
        $spatieVendedor->syncPermissions([
            'pedidos.crear', 'pedidos.ver',
            'inventario.ver',
            'productos.ver',
            'clientes.ver',
        ]);

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
     * Verificar que un vendedor recibe 403 al intentar aprobar un pago.
     */
    public function test_vendedor_recibe_403_al_intentar_aprobar_pago(): void
    {
        [, , $payment] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(VerificacionPagos::class)
            ->call('approve', $payment->id)
            ->assertForbidden();
    }

    /**
     * Verificar que un vendedor recibe 403 al intentar rechazar un pago.
     */
    public function test_vendedor_recibe_403_al_intentar_rechazar_pago(): void
    {
        [, , $payment] = $this->crearPedidoConPago();

        Livewire::actingAs($this->vendedorUser)
            ->test(VerificacionPagos::class)
            ->call('openReject', $payment->id)
            ->assertForbidden();
    }
}

