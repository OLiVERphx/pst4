<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use Spatie\Permission\Models\Permission;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que permisos existan
        Permission::firstOrCreate(['name' => 'reportes.ver']);
        Permission::firstOrCreate(['name' => 'pagos.ver']);
    }

    /** @test */
    public function authorized_user_can_view_reports()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reportes.ver');

        $response = $this->actingAs($user)->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.index');
    }

    /** @test */
    public function unauthorized_user_gets_forbidden()
    {
        $user = User::factory()->create();
        // no permission given

        $response = $this->actingAs($user)->get(route('admin.reports.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function stock_projection_handles_zero_sales_gracefully()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reportes.ver');

        // Crear producto sin ventas en últimos 30 días
        $product = Product::create([
            'codigo' => 'TEST-001',
            'nombre' => 'Producto Sin Ventas',
            'slug' => 'producto-sin-ventas',
            'descripcion' => 'Prueba',
            'marca_id' => 1,
            'categoria_id' => 1,
            'precio_detal' => 100,
            'precio_mayor' => 80,
            'precio_costo' => 50,
            'min_cantidad_mayor' => 10,
            'stock' => 20,
            'stock_minimo' => 5,
            'imagenes' => [],
            'activo' => true,
            'destacado' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertViewHas('stockProjection', function ($arr) use ($product) {
            // el producto sin ventas debería aparecer con days_to_deplete == null o no aparecer
            foreach ($arr as $row) {
                if ($row['product_id'] === $product->id) {
                    return is_null($row['days_to_deplete']);
                }
            }
            // Si no aparece en la lista, también es aceptable
            return true;
        });
    }
}
