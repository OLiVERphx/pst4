<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Inyección SQL ciega (booleana): el atacante no ve datos, pero compara la
 * respuesta a una condición verdadera (1=1) con una falsa (1=2) para deducir
 * información. Si las respuestas son idénticas no hay nada que deducir.
 *
 * La variante por tiempo (SLEEP) se verificó manualmente sobre MySQL/MariaDB:
 * SQLite, que usan los tests, no tiene SLEEP.
 */
class InyeccionCiegaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function el_catalogo_responde_igual_a_una_condicion_verdadera_y_a_una_falsa()
    {
        $verdadero = $this->get('/catalogo?buscar=' . urlencode("Infinix' AND 1=1 -- "))->assertOk()->viewData('productos');
        $falso = $this->get('/catalogo?buscar=' . urlencode("Infinix' AND 1=2 -- "))->assertOk()->viewData('productos');

        $this->assertEquals($verdadero->pluck('id')->all(), $falso->pluck('id')->all());
    }

    #[Test]
    public function la_api_de_busqueda_responde_igual_a_una_condicion_verdadera_y_a_una_falsa()
    {
        $verdadero = $this->getJson('/api/catalogo/buscar?q=' . urlencode("Infinix' AND 1=1 -- "))->assertOk()->json();
        $falso = $this->getJson('/api/catalogo/buscar?q=' . urlencode("Infinix' AND 1=2 -- "))->assertOk()->json();

        $this->assertSame($verdadero, $falso);
    }

    #[Test]
    public function el_login_responde_igual_a_una_condicion_verdadera_y_a_una_falsa()
    {
        $verdadero = $this->postJson('/api/auth/login', ['email' => "admin@swworld.com' AND 1=1 -- ", 'password' => 'x']);
        $falso = $this->postJson('/api/auth/login', ['email' => "admin@swworld.com' AND 1=2 -- ", 'password' => 'x']);

        $this->assertSame($verdadero->status(), $falso->status());
        $this->assertSame($verdadero->json(), $falso->json());
        $this->assertGuest();
    }

    #[Test]
    public function un_id_de_producto_con_sql_da_404_y_no_un_error_del_servidor()
    {
        $this->getJson('/api/catalogo/' . rawurlencode('1 AND 1=1'))->assertNotFound();
        $this->getJson('/api/catalogo/' . rawurlencode('1 AND 1=2'))->assertNotFound();
        $this->getJson('/api/catalogo/' . rawurlencode('1 AND 1=1') . '/relacionados')->assertNotFound();
    }
}
