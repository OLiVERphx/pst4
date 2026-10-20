<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Instalación en un servidor nuevo: sin contraseñas conocidas ni datos de prueba. */
class InicializarProduccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 500)]); // la tasa BCV no debe bloquear la instalación
    }

    private function configurar(?string $email = 'dueno@tienda.com', ?string $password = 'ClaveSegura2026', string $nombre = 'Dueño'): void
    {
        config(['app.admin_email' => $email, 'app.admin_password' => $password, 'app.admin_nombre' => $nombre]);
    }

    #[Test]
    public function crea_el_superadmin_con_los_datos_del_entorno_y_nada_de_prueba()
    {
        $this->configurar();

        $this->artisan('produccion:inicializar')->assertSuccessful();

        $admin = User::where('email', 'dueno@tienda.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('superadmin'));
        $this->assertTrue($admin->can('configuracion.editar'));
        $this->assertTrue(Hash::check('ClaveSegura2026', $admin->password));

        $this->assertNull(User::where('email', 'admin@swworld.com')->first());
        $this->assertSame(1, User::count());
        $this->assertSame(0, Product::count());
        $this->assertGreaterThan(0, Brand::count());
        $this->assertGreaterThan(0, Category::count());
    }

    #[Test]
    public function el_superadmin_puede_entrar_al_panel()
    {
        $this->configurar();
        $this->artisan('produccion:inicializar')->assertSuccessful();

        $this->actingAs(User::where('email', 'dueno@tienda.com')->first())
            ->get(route('admin.dashboard'))->assertOk();
    }

    #[Test]
    public function se_niega_sin_contrasena_o_con_una_debil()
    {
        $this->configurar(password: null);
        $this->artisan('produccion:inicializar')->assertFailed();

        $this->configurar(password: 'corta1');
        $this->artisan('produccion:inicializar')->assertFailed();

        $this->configurar(password: 'sololetrassinnumeros');
        $this->artisan('produccion:inicializar')->assertFailed();

        $this->configurar(email: null);
        $this->artisan('produccion:inicializar')->assertFailed();

        $this->assertSame(0, User::count());
    }

    #[Test]
    public function repetirlo_no_cambia_contrasena_ni_permisos()
    {
        $this->configurar();
        $this->artisan('produccion:inicializar')->assertSuccessful();

        $admin = User::where('email', 'dueno@tienda.com')->first();
        $admin->update(['password' => 'OtraClave2027Nueva']);

        // Otro arranque con otra contraseña en el entorno: no debe pisarla ni duplicar usuarios
        $this->configurar(password: 'ClaveDistinta2028');
        $this->artisan('produccion:inicializar')->assertSuccessful();

        $this->assertTrue(Hash::check('OtraClave2027Nueva', $admin->fresh()->password));
        $this->assertSame(1, User::count());
    }
}
