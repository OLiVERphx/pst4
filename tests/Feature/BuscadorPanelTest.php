<?php

namespace Tests\Feature;

use App\Livewire\Datatables\ClientsTable;
use App\Livewire\Datatables\OrdersTable;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Buscador de la barra superior del panel. */
class BuscadorPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@swworld.com')->firstOrFail();
        $this->cliente = User::factory()->create([
            'rol_id' => 3, 'name' => 'Pedro', 'apellido' => 'Uzcátegui',
            'email' => 'pedro.uzca@ejemplo.com', 'cedula' => 'V-18765432',
        ]);
    }

    private function pedido(string $numero, string $estado): int
    {
        return DB::table('pedidos')->insertGetId([
            'numero_pedido' => $numero, 'usuario_id' => $this->cliente->id, 'tipo' => 'detal',
            'estado' => $estado, 'tipo_entrega' => 'retiro', 'canal' => 'online',
            'subtotal' => 20, 'descuento' => 0, 'total' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    #[Test]
    public function encuentra_pedidos_productos_y_clientes()
    {
        $this->pedido('SW-2026-0777', 'pendiente');
        $producto = Product::firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.buscar', ['q' => '0777']))
            ->assertOk()->assertSee('SW-2026-0777');

        $this->actingAs($this->admin)->get(route('admin.buscar', ['q' => $producto->codigo]))
            ->assertOk()->assertSee($producto->nombre);

        // Cédula cifrada: se encuentra por coincidencia exacta (índice ciego), con o sin formato
        $this->actingAs($this->admin)->get(route('admin.buscar', ['q' => '18765432']))
            ->assertOk()->assertSee('pedro.uzca@ejemplo.com');

        $this->actingAs($this->admin)->get(route('admin.buscar', ['q' => 'Uzcátegui']))
            ->assertOk()->assertSee('pedro.uzca@ejemplo.com')->assertSee('SW-2026-0777');
    }

    #[Test]
    public function solo_muestra_lo_que_el_rol_puede_ver()
    {
        Role::findByName('vendedor')->revokePermissionTo('clientes.ver');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $vendedor = User::factory()->create(['rol_id' => 4]);
        $vendedor->assignRole('vendedor');
        $this->pedido('SW-2026-0778', 'pendiente');

        $this->actingAs($vendedor)->get(route('admin.buscar', ['q' => 'Pedro']))
            ->assertOk()
            ->assertSee('SW-2026-0778')
            ->assertDontSee('pedro.uzca@ejemplo.com');
    }

    #[Test]
    public function un_termino_corto_no_busca()
    {
        $this->actingAs($this->admin)->get(route('admin.buscar', ['q' => 'a']))
            ->assertOk()->assertSee('al menos 2 caracteres');
    }

    #[Test]
    public function las_tablas_reciben_el_termino_por_la_url()
    {
        Livewire::withQueryParams(['q' => 'pedro.uzca'])
            ->actingAs($this->admin)
            ->test(ClientsTable::class)
            ->assertSet('search', 'pedro.uzca')
            ->assertSee('pedro.uzca@ejemplo.com');
    }

    #[Test]
    public function buscar_pedidos_respeta_el_filtro_de_estado()
    {
        $this->pedido('SW-2026-0901', 'pendiente');
        $this->pedido('SW-2026-0902', 'completado');

        // Antes, el OR sin agrupar quedaba "número LIKE ... OR (cliente ... AND estado = ...)":
        // buscando por número aparecían pedidos de cualquier estado
        Livewire::actingAs($this->admin)
            ->test(OrdersTable::class)
            ->set('search', 'SW-2026-09')
            ->set('statusFilter', 'pendiente')
            ->assertSee('SW-2026-0901')
            ->assertDontSee('SW-2026-0902');
    }
}
