<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Configuracion;
use App\Models\Product;
use App\Models\User;
use App\Services\ServicioPedidos;

class ValidarMinimoMayorTest extends TestCase
{
    use RefreshDatabase;

    protected $clienteId;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear configuración explícita por defecto
        Configuracion::create([
            'minimo_compra_mayor_usd' => 50.00,
            'minimo_unidades_mayor' => 10,
            'tasa_bcv' => null,
            'tasa_binance' => null,
        ]);

        // Asegurar existencia de marca y categoría referenciadas por FK
        \DB::table('marcas')->insert(['nombre' => 'Marca prueba']);
        \DB::table('categorias')->insert(['nombre' => 'Categoria prueba', 'slug' => 'categoria-prueba', 'activo' => true]);

        // Asegurar existencia del rol requerido por FK
        if (!\DB::table('roles')->where('id',1)->exists()) {
            \DB::table('roles')->insert(['id' => 1, 'nombre' => 'Vendedor', 'created_at' => now(), 'updated_at' => now()]);
        }

        // Crear un vendedor mínimo
        if (!\DB::table('users')->where('email','vendedor@example.com')->exists()) {
            \DB::table('users')->insert([
                'name' => 'Vendedor',
                'email' => 'vendedor@example.com',
                'password' => bcrypt('secret'),
                'rol_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear cliente de prueba y almacenar su id
        $cliente = \DB::table('users')->where('email','cliente@example.com')->first();
        if (! $cliente) {
            $this->clienteId = \DB::table('users')->insertGetId([
                'name' => 'Cliente',
                'email' => 'cliente@example.com',
                'password' => bcrypt('secret'),
                'rol_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $this->clienteId = $cliente->id;
        }
    }

    public function test_carrito_mayor_con_3_unidades_12usd_es_rechazado_al_crear_pedido()
    {
        // Crear producto con precio mayor de US$12
        $product = Product::create([
            'codigo' => 'P001',
            'nombre' => 'Producto mayor',
            'slug' => 'producto-mayor',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 15.00,
            'precio_mayor' => 12.00,
            'precio_costo' => 8.00,
            'min_cantidad_mayor' => 10,
            'stock' => 100,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        $lineas = [
            ['producto_id' => $product->id, 'cantidad' => 3, 'precio' => 12.00, 'tipo' => 'mayor']
        ];

        $servicio = app(ServicioPedidos::class);

        try {
            $servicio->crearPedidoDesdeLineas($lineas, $this->clienteId, 1, 'online', null, [
                'entrega_nombre' => 'Cliente Prueba',
                'entrega_telefono' => '04121234567',
                'entrega_direccion' => 'Calle Falsa 123',
                'entrega_ciudad' => 'Caracas',
            ]);
            $this->fail('Expected exception due to mínimo mayor not met');
        } catch (\Exception $e) {
            $this->assertStringContainsString('no cumple el mínimo', strtolower($e->getMessage()));
        }
    }

    public function test_carrito_con_detall_y_mayor_no_suma_detal_para_validacion_mayor()
    {
        // Producto detal caro
        $prodDetal = Product::create([
            'codigo' => 'P002',
            'nombre' => 'Producto detal',
            'slug' => 'producto-detal',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 200.00,
            'precio_mayor' => 180.00,
            'precio_costo' => 100.00,
            'min_cantidad_mayor' => 10,
            'stock' => 50,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        // Producto mayor que no alcanza el mínimo por sí solo
        $prodMayor = Product::create([
            'codigo' => 'P003',
            'nombre' => 'Producto pequeño mayor',
            'slug' => 'producto-mayor-peq',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 30.00,
            'precio_mayor' => 10.00,
            'precio_costo' => 5.00,
            'min_cantidad_mayor' => 10,
            'stock' => 50,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        $lineas = [
            ['producto_id' => $prodDetal->id, 'cantidad' => 1, 'precio' => 200.00, 'tipo' => 'detal'],
            ['producto_id' => $prodMayor->id, 'cantidad' => 2, 'precio' => 10.00, 'tipo' => 'mayor'],
        ];

        $servicio = app(ServicioPedidos::class);

        try {
            $servicio->crearPedidoDesdeLineas($lineas, $this->clienteId, 1, 'online', null, [
                'entrega_nombre' => 'Cliente Prueba',
                'entrega_telefono' => '04121234567',
                'entrega_direccion' => 'Calle Falsa 123',
                'entrega_ciudad' => 'Caracas',
            ]);
            $this->fail('Expected exception because mayor items alone do not meet minimum');
        } catch (\Exception $e) {
            $this->assertStringContainsString('no cumple el mínimo', strtolower($e->getMessage()));
        }
    }

    public function test_carrito_con_10_unidades_mayor_completa_checkout()
    {
        $prodMayor = Product::create([
            'codigo' => 'P004',
            'nombre' => 'Producto mayor grande',
            'slug' => 'producto-mayor-grande',
            'descripcion' => 'Descripción',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 6.00,
            'precio_mayor' => 5.00,
            'precio_costo' => 3.00,
            'min_cantidad_mayor' => 10,
            'stock' => 100,
            'stock_minimo' => 1,
            'imagenes' => null,
            'activo' => true,
            'destacado' => false,
        ]);

        $lineas = [
            ['producto_id' => $prodMayor->id, 'cantidad' => 10, 'precio' => 5.00, 'tipo' => 'mayor'],
        ];

        $servicio = app(ServicioPedidos::class);

        $order = $servicio->crearPedidoDesdeLineas($lineas, $this->clienteId, 1, 'online', null, [
            'entrega_nombre' => 'Cliente Prueba',
            'entrega_telefono' => '04121234567',
            'entrega_direccion' => 'Calle Falsa 123',
            'entrega_ciudad' => 'Caracas',
        ]);

        $this->assertNotNull($order);
        $this->assertEquals('pendiente', $order->estado);
        $this->assertEquals(50.00, $order->subtotal);
    }
}
