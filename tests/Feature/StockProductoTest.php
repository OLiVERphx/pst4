<?php

namespace Tests\Feature;

use App\Livewire\Datatables\ProductsTable;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SeederRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El formulario de productos no escribe el stock directamente: todo cambio queda
 * como movimiento de inventario.
 */
class StockProductoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Brand $marca;
    private Category $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeederRoles::class);
        config(['auth.dos_factores.obligatorio' => false]);

        $this->admin = User::factory()->create(['rol_id' => Role::where('nombre', 'admin')->value('id'), 'activo' => true]);
        $this->admin->assignRole('admin');
        $this->marca = Brand::create(['nombre' => 'Anker']);
        $this->categoria = Category::create(['nombre' => 'Cargadores', 'slug' => 'cargadores', 'activo' => true]);
    }

    public function test_editar_un_producto_no_cambia_su_stock(): void
    {
        $producto = Product::create(['codigo' => 'CAR-1', 'nombre' => 'Cargador', 'slug' => 'cargador', 'marca_id' => $this->marca->id,
            'categoria_id' => $this->categoria->id, 'precio_detal' => 8, 'precio_mayor' => 6.5, 'stock' => 10]);

        Livewire::actingAs($this->admin)->test(ProductsTable::class)
            ->call('openEdit', $producto->id)
            ->set('form.stock', 999)
            ->set('form.nombre', 'Cargador USB-C')
            ->call('save')
            ->assertHasNoErrors();

        $producto->refresh();
        $this->assertSame('Cargador USB-C', $producto->nombre);
        $this->assertSame(10, $producto->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_el_stock_inicial_de_un_producto_nuevo_es_una_entrada_de_inventario(): void
    {
        Livewire::actingAs($this->admin)->test(ProductsTable::class)
            ->call('openCreate')
            ->set('form.codigo', 'CAR-2')
            ->set('form.nombre', 'Cargador inalámbrico')
            ->set('form.marca_id', $this->marca->id)
            ->set('form.categoria_id', $this->categoria->id)
            ->set('form.precio_detal', 12)
            ->set('form.precio_mayor', 9.5)
            ->set('form.stock', 18)
            ->call('save')
            ->assertHasNoErrors();

        $producto = Product::where('codigo', 'CAR-2')->firstOrFail();
        $this->assertSame(18, $producto->stock);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 18,
            'cantidad_anterior' => 0, 'cantidad_nueva' => 18, 'usuario_id' => $this->admin->id,
        ]);
    }
}
