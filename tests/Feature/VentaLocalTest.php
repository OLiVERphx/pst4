<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use Livewire\Livewire;
use Illuminate\Support\Facades\DB;

class VentaLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_mount_component()
    {
        // Asegurar que exista un rol con id=1 para las fábricas de usuario
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'test-role']);
        $user = User::factory()->create(['rol_id' => 1]);
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
                // Instanciar el componente y ejecutar mount() para validar autorización explicita
                $component = new \App\Http\Livewire\VentaLocal();
                $component->mount();
    }

    public function test_local_sale_decrements_stock_same_as_service()
    {
        // Crear vendedor con permiso
        // Asegurar rol
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'test-role']);
        $vendedor = User::factory()->create(['rol_id' => 1]);
        // Crear permiso si no existe y asignarlo
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            \Spatie\Permission\Models\Permission::findOrCreate('pedidos.crear');
        }
        $vendedor->givePermissionTo('pedidos.crear');

        // Crear producto
        DB::table('marcas')->insert(['nombre' => 'Marca prueba']);
        DB::table('categorias')->insert(['nombre' => 'Categoria prueba', 'slug' => 'categoria-prueba', 'activo' => true]);
        $product = Product::create([
            'codigo' => 'TL001',
            'nombre' => 'Producto test',
            'slug' => 'producto-test',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 100.00,
            'precio_mayor' => 90.00,
            'precio_costo' => 60.00,
            'min_cantidad_mayor' => 10,
            'stock' => 10,
            'stock_reservado' => 0,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        $this->actingAs($vendedor);

        // Ejecutar venta local vía Livewire
        Livewire::test(\App\Http\Livewire\VentaLocal::class)
            ->call('addItem', $product->id, 'detal', 3)
            ->call('confirmSale');

        $product->refresh();
        $this->assertEquals(7, $product->stock);

        // Ejecutar otra venta simulando proceso online (servicio)
        $servicio = app(\App\Services\ServicioPedidos::class);
        $order = $servicio->crearPedidoDesdeLineas([
            ['producto_id' => $product->id, 'cantidad' => 2, 'precio' => $product->precio_detal]
        ], null, $vendedor->id, 'online', 'Venta prueba');

        $product->refresh();
        $this->assertEquals(5, $product->stock);
    }

    public function test_vendedor_role_can_access_route()
    {
        // Crear rol 'vendedor' y usuario con permiso
        DB::table('roles')->insert(['id' => 2, 'nombre' => 'vendedor']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        $vendedor = User::factory()->create(['rol_id' => 2, 'activo' => true]);

        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            \Spatie\Permission\Models\Permission::findOrCreate('pedidos.crear');
        }
        $vendedor->givePermissionTo('pedidos.crear');
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor);

        $response = $this->get(route('admin.ventalocal.index'));
        $response->assertStatus(200);
    }

    public function test_user_without_pedidos_crear_gets_403_on_route()
    {
        // Crear rol distinto y usuario sin permiso
        DB::table('roles')->insert(['id' => 3, 'nombre' => 'cliente']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        $user = User::factory()->create(['rol_id' => 3, 'activo' => true]);

        // No se le da el permiso 'pedidos.crear'
        $user->assignRole('vendedor');
        $this->actingAs($user);

        $response = $this->get(route('admin.ventalocal.index'));
        $response->assertStatus(403);
    }

    public function test_confirmSale_wholesale_minimum_error()
    {
        // Preparar rol y usuario con permiso
        DB::table('roles')->insert(['id' => 4, 'nombre' => 'vendedor2']);
        $vendedor = User::factory()->create(['rol_id' => 4]);

        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            \Spatie\Permission\Models\Permission::findOrCreate('pedidos.crear');
        }
        $vendedor->givePermissionTo('pedidos.crear');

        // Crear producto con min_cantidad_mayor = 10
        DB::table('marcas')->insert(['nombre' => 'Marca prueba']);
        DB::table('categorias')->insert(['nombre' => 'Categoria prueba', 'slug' => 'categoria-prueba', 'activo' => true]);
        $product = Product::create([
            'codigo' => 'TL002',
            'nombre' => 'Producto mayor test',
            'slug' => 'producto-mayor-test',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 60.00,
            'precio_mayor' => 50.00,
            'precio_costo' => 30.00,
            'min_cantidad_mayor' => 10,
            'stock' => 100,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        $this->actingAs($vendedor);

        // Usar Livewire para agregar item tipo 'mayor' cantidad 2 y confirmar
        Livewire::test(\App\Http\Livewire\VentaLocal::class)
            ->call('addItem', $product->id, 'mayor', 2)
            ->call('confirmSale')
            ->assertSee('se requieren al menos');
    }
}
