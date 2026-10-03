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
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;
use App\Livewire\Datatables\ProductsTable;
use App\Livewire\Datatables\ClientsTable;

/**
 * Suite de pruebas de seguridad, sanitización y protección contra inyecciones SQL/XSS/IDOR.
 */
class SecuritySanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $cliente;
    protected User $admin;
    protected Product $producto;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'superadmin', 'permisos_deprecated' => json_encode(['*'])],
            ['id' => 2, 'nombre' => 'admin', 'permisos_deprecated' => json_encode([])],
            ['id' => 3, 'nombre' => 'cliente', 'permisos_deprecated' => json_encode([])],
            ['id' => 4, 'nombre' => 'vendedor', 'permisos_deprecated' => json_encode([])],
        ]);

        $spatieAdmin = SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $spatieCliente = SpatieRole::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        $permisos = [
            'pagos.ver', 'pagos.aprobar', 'pagos.rechazar',
            'inventario.ver', 'inventario.ajustar',
            'pedidos.ver', 'pedidos.crear', 'pedidos.anular',
            'clientes.ver', 'clientes.editar', 'clientes.bloquear',
            'reportes.ver', 'auditoria.ver',
            'configuracion.editar',
            'productos.ver', 'productos.editar',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $spatieAdmin->syncPermissions(Permission::all());

        $this->cliente = User::create([
            'name' => 'Cliente Seguro',
            'email' => 'cliente.seguro@test.com',
            'rol_id' => 3,
            'password' => bcrypt('password123'),
            'activo' => true,
        ]);
        $this->cliente->assignRole('cliente');

        $this->admin = User::create([
            'name' => 'Admin Seguro',
            'email' => 'admin.seguro@test.com',
            'rol_id' => 2,
            'password' => bcrypt('password123'),
            'activo' => true,
        ]);
        $this->admin->assignRole('admin');

        $brand = Brand::create(['nombre' => 'Apple', 'slug' => 'apple']);
        $cat = Category::create(['nombre' => 'Accesorios', 'slug' => 'accesorios']);

        $this->producto = Product::create([
            'categoria_id' => $cat->id,
            'marca_id' => $brand->id,
            'codigo' => 'PROD-SEC-01',
            'nombre' => 'Funda Blindada iPhone',
            'slug' => 'funda-blindada-iphone',
            'precio_detal' => 20.00,
            'precio_mayor' => 15.00,
            'stock' => 50,
            'stock_minimo' => 5,
            'activo' => true,
        ]);
    }

    /**
     * Prueba: Intento de Inyección SQL en el catálogo público y búsqueda web.
     */
    public function test_catalogo_publico_resiste_inyeccion_sql(): void
    {
        $payloads = [
            "' OR '1'='1",
            "'; DROP TABLE products; --",
            "' UNION SELECT null, null, null, null, null, null, null, null, null, null --",
            "1' AND SLEEP(2) AND '1'='1",
            "admin'--",
            "\" OR \"\"=\"",
        ];

        foreach ($payloads as $payload) {
            $response = $this->get('/catalogo?q=' . urlencode($payload));
            $response->assertOk();
            $response->assertDontSee('SQLSTATE');
            $response->assertDontSee('syntax error');
        }

        // Verificar que la tabla y el producto siguen existiendo intactos
        $this->assertDatabaseHas('products', ['id' => $this->producto->id]);
    }

    /**
     * Prueba: Intento de Inyección SQL en la API de búsqueda de catálogo.
     */
    public function test_api_catalogo_resiste_inyeccion_sql(): void
    {
        $payload = "' UNION SELECT id, email, password FROM users --";

        $response = $this->getJson('/api/catalogo/buscar?q=' . urlencode($payload));
        $response->assertOk();
        $response->assertDontSee('password');
        $response->assertDontSee('SQLSTATE');
    }

    /**
     * Prueba: Intento de Inyección SQL en campos de autenticación Login.
     */
    public function test_login_resiste_inyeccion_sql_de_autenticacion(): void
    {
        $payloads = [
            "' OR '1'='1",
            "admin' --",
            "admin' /*",
            "' OR 1=1 --",
        ];

        foreach ($payloads as $payload) {
            $response = $this->post('/login', [
                'email' => $payload,
                'password' => 'cualquier_clave',
            ]);

            // No debe autenticar al usuario ni arrojar error SQL
            $this->assertGuest();
            $response->assertSessionHasErrors();
        }
    }

    /**
     * Prueba: Intento de Inyección SQL en componente Livewire ProductsTable.
     */
    public function test_livewire_products_table_resiste_inyeccion_sql(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProductsTable::class)
            ->set('search', "' OR '1'='1")
            ->assertDontSeeHtml('SQLSTATE')
            ->set('search', "'; DELETE FROM products; --")
            ->assertDontSeeHtml('SQLSTATE');

        $this->assertDatabaseHas('products', ['id' => $this->producto->id]);
    }

    /**
     * Prueba: Prevención de XSS en datos ingresados por clientes.
     */
    public function test_prevencion_xss_en_renderizado(): void
    {
        $xssPayload = "<script>alert('xss-exploit')</script>";

        // Al consultar una vista con el payload como parámetro
        $response = $this->get('/catalogo?buscar=' . urlencode($xssPayload));
        $response->assertOk();

        // El payload NO debe ejecutarse como HTML ejecutable en la respuesta
        $response->assertDontSeeHtml($xssPayload);
    }

    /**
     * Prueba: Prevención de escalación de privilegios (Mass Assignment en registro).
     */
    public function test_cliente_no_puede_escalar_a_superadmin_en_registro(): void
    {
        $this->post('/registro', [
            'name' => 'Atacante',
            'apellido' => 'Malicioso',
            'email' => 'atacante@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'rol_id' => 1, // Intento de asignarse rol 1 (superadmin)
            'rol' => 'superadmin',
        ]);

        $usuarioCreado = User::where('email', 'atacante@test.com')->first();
        $this->assertNotNull($usuarioCreado);
        // Su rol Spatie debe ser 'cliente' y no 'superadmin'
        $this->assertFalse($usuarioCreado->hasRole('superadmin'));
        $this->assertTrue($usuarioCreado->hasRole('cliente'));
    }

    /**
     * Prueba: Protección de comprobantes y rutas privadas sin autorización.
     */
    public function test_cliente_no_puede_acceder_a_comprobantes_ni_panel_admin(): void
    {
        // Cliente intenta acceder al dashboard admin
        $this->actingAs($this->cliente)
            ->get('/admin/dashboard')
            ->assertRedirect('/admin/login');

        // Cliente intenta acceder a auditoría
        $this->actingAs($this->cliente)
            ->get('/admin/auditoria')
            ->assertRedirect('/admin/login');

        // Invitado intenta acceder a panel administrativo
        $this->get('/admin/orders')
            ->assertRedirect('/admin/login');
    }
}
