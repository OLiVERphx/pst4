<?php

namespace Tests\Feature;

use App\Livewire\ImportarProductos;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Role;
use App\Models\TasaBcv;
use App\Models\User;
use App\Services\Importacion\LectorHojaCalculo;
use App\Services\Importacion\ServicioImportacionProductos;
use Database\Seeders\SeederRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportacionProductosTest extends TestCase
{
    use RefreshDatabase;

    private ServicioImportacionProductos $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeederRoles::class);
        config(['auth.dos_factores.obligatorio' => false]);
        $this->servicio = app(ServicioImportacionProductos::class);
    }

    private function usuario(string $rol): User
    {
        $user = User::factory()->create([
            'rol_id' => Role::where('nombre', $rol)->value('id'),
            'activo' => true,
        ]);
        $user->assignRole($rol);

        return $user;
    }

    /** Crea un .xlsx temporal con las filas dadas y devuelve su ruta. */
    private function excel(array $filas): string
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray($filas, null, 'A1', true);
        $ruta = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($libro))->save($ruta);

        return $ruta;
    }

    private function analizarArchivo(string $ruta, array $opciones = []): array
    {
        $filas = app(LectorHojaCalculo::class)->leer($ruta)['filas'];
        $encabezados = $this->servicio->detectarFilaEncabezados($filas);
        $mapeo = $this->servicio->sugerirMapeo($filas[$encabezados]);

        return $this->servicio->analizar($filas, $encabezados, $mapeo, $opciones + ['puede_ajustar_stock' => true]);
    }

    // -- Lectura y mapeo -----------------------------------------------------

    public function test_reconoce_encabezados_con_otros_nombres_y_titulo_arriba(): void
    {
        $ruta = $this->excel([
            ['INVENTARIO SMARTPHONE WORLD'],
            ['Fecha: 21/10/2026'],
            [],
            ['Cód.', 'Descripción', 'Marca', 'Línea', 'P.V.P. $', 'Precio al mayor', 'Costo', 'Existencia'],
            ['A15', 'Galaxy A15', 'Samsung', 'Teléfonos', 180, 165, 140, 10],
        ]);
        $filas = app(LectorHojaCalculo::class)->leer($ruta)['filas'];

        $this->assertSame(4, $this->servicio->detectarFilaEncabezados($filas));
        $mapeo = $this->servicio->sugerirMapeo($filas[4]);
        ksort($mapeo);
        $this->assertSame([
            'categoria' => 3, 'codigo' => 0, 'marca' => 2, 'nombre' => 1,
            'precio_costo' => 6, 'precio_detal' => 4, 'precio_mayor' => 5, 'stock' => 7,
        ], $mapeo);
    }

    public function test_interpreta_numeros_en_formatos_comunes(): void
    {
        $casos = [
            '1.234,56' => [1234.56, null],
            '1,234.56' => [1234.56, null],
            '25,5' => [25.5, null],
            '$ 25' => [25.0, 'USD'],
            'Bs. 900,50' => [900.5, 'VES'],
            '1.234' => [1234.0, null],
            '12.5' => [12.5, null],
        ];
        foreach ($casos as $texto => [$valor, $moneda]) {
            [$leido, $monedaLeida] = $this->servicio->aNumero($texto);
            $this->assertEqualsWithDelta($valor, $leido, 0.001, $texto);
            $this->assertSame($moneda, $monedaLeida, $texto);
        }
        $this->assertNull($this->servicio->aNumero('consultar'));
    }

    // -- Validación ------------------------------------------------------------

    public function test_marca_errores_por_fila_sin_detener_las_demas(): void
    {
        $analisis = $this->analizarArchivo($this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio detal', 'Stock'],
            ['OK-1', 'Producto bueno', 'Samsung', 'Teléfonos', 100, 5],
            ['OK-1', 'Repetido', 'Samsung', 'Teléfonos', 100, 5],
            [null, 'Sin código', 'Samsung', 'Teléfonos', 100, 5],
            ['MAL-PRECIO', 'Precio raro', 'Samsung', 'Teléfonos', 'consultar', 5],
            ['MAL-STOCK', 'Stock decimal', 'Samsung', 'Teléfonos', 10, 2.5],
            ['SIN-MARCA', 'Falta marca', null, 'Teléfonos', 10, 1],
            [null, 'TOTAL', null, null, 330, 18],
        ]));

        $this->assertSame(['crear' => 1, 'actualizar' => 0, 'omitir' => 0, 'error' => 5], $analisis['resumen']);
        $this->assertSame(['Samsung'], $analisis['marcas_nuevas']);
        $this->assertStringContainsString('fila 2', $analisis['filas'][1]['errores'][0]);
    }

    public function test_sin_columna_de_codigo_no_analiza_filas(): void
    {
        $analisis = $this->analizarArchivo($this->excel([
            ['Nombre', 'Marca', 'Categoría', 'Precio'],
            ['Producto', 'Samsung', 'Teléfonos', 100],
        ]));

        $this->assertNotEmpty($analisis['errores_generales']);
        $this->assertSame([], $analisis['filas']);
    }

    public function test_sin_permiso_de_inventario_no_acepta_columna_de_stock(): void
    {
        $analisis = $this->analizarArchivo($this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio', 'Stock'],
            ['X1', 'Producto', 'Samsung', 'Teléfonos', 100, 4],
        ]), ['puede_ajustar_stock' => false]);

        $this->assertStringContainsString('ajustar inventario', implode(' ', $analisis['errores_generales']));
    }

    // -- Importación -----------------------------------------------------------

    public function test_crea_y_actualiza_productos_y_registra_el_stock_como_movimiento(): void
    {
        $admin = $this->usuario('admin');
        $marca = Brand::create(['nombre' => 'Samsung']);
        $categoria = Category::create(['nombre' => 'Teléfonos', 'slug' => 'telefonos', 'activo' => true]);
        $existente = Product::create([
            'codigo' => 'A15', 'nombre' => 'Galaxy A15', 'slug' => 'galaxy-a15', 'marca_id' => $marca->id,
            'categoria_id' => $categoria->id, 'precio_detal' => 170, 'precio_mayor' => 160, 'stock' => 3,
        ]);

        $analisis = $this->analizarArchivo($this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio detal', 'Stock'],
            ['A15', null, null, null, '185,00', 8],
            ['FUNDA-1', 'Funda iPhone 15', 'Genérica', 'Accesorios', 5, 20],
        ]));
        $resultado = $this->servicio->importar($analisis, $admin, 'inventario.xlsx');

        $this->assertSame(1, $resultado['creados']);
        $this->assertSame(1, $resultado['actualizados']);
        $this->assertSame(2, $resultado['movimientos']);

        // Las celdas vacías no borran datos del producto existente
        $existente->refresh();
        $this->assertSame('Galaxy A15', $existente->nombre);
        $this->assertEquals(185, $existente->precio_detal);
        $this->assertEquals(160, $existente->precio_mayor);
        $this->assertSame(8, $existente->stock);

        $nuevo = Product::where('codigo', 'FUNDA-1')->firstOrFail();
        $this->assertSame(20, $nuevo->stock);
        $this->assertEquals(5, $nuevo->precio_mayor, 'sin columna de precio mayor se usa el detal');
        $this->assertSame('funda-iphone-15', $nuevo->slug);
        $this->assertSame('Genérica', $nuevo->brand->nombre);

        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $existente->id, 'tipo' => 'ajuste', 'cantidad' => 5, 'cantidad_anterior' => 3, 'cantidad_nueva' => 8]);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $nuevo->id, 'tipo' => 'entrada', 'cantidad' => 20, 'cantidad_nueva' => 20]);
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'producto.importacion']);
    }

    public function test_convierte_precios_en_bolivares_con_la_tasa_bcv(): void
    {
        TasaBcv::create(['fecha' => now()->toDateString(), 'tasa' => 50, 'fuente' => 'manual']);

        $analisis = $this->analizarArchivo($this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio'],
            ['B1', 'Cargador', 'Genérica', 'Accesorios', 1000],
            ['B2', 'Cable', 'Genérica', 'Accesorios', '$ 3'],
        ]), ['moneda' => 'VES']);

        $this->assertEquals(20.0, $analisis['filas'][0]['datos']['precio_detal']);
        $this->assertEquals(3.0, $analisis['filas'][1]['datos']['precio_detal'], 'el "$" de la celda manda sobre la moneda elegida');
    }

    public function test_reimportar_el_mismo_archivo_no_cambia_nada(): void
    {
        $admin = $this->usuario('admin');
        $ruta = $this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio', 'Stock', 'Activo'],
            ['R1', 'Router', 'TP-Link', 'Redes', 30, 7, 'Sí'],
        ]);
        $this->servicio->importar($this->analizarArchivo($ruta), $admin, 'a.xlsx');

        $segundo = $this->analizarArchivo($ruta);

        $this->assertSame(['crear' => 0, 'actualizar' => 0, 'omitir' => 1, 'error' => 0], $segundo['resumen']);
        $this->assertSame(1, InventoryMovement::count());
    }

    public function test_modo_solo_crear_omite_los_existentes(): void
    {
        $marca = Brand::create(['nombre' => 'Samsung']);
        $categoria = Category::create(['nombre' => 'Teléfonos', 'slug' => 'telefonos', 'activo' => true]);
        Product::create(['codigo' => 'A15', 'nombre' => 'Galaxy A15', 'slug' => 'galaxy-a15', 'marca_id' => $marca->id,
            'categoria_id' => $categoria->id, 'precio_detal' => 170, 'precio_mayor' => 160]);

        $analisis = $this->analizarArchivo($this->excel([
            ['Código', 'Nombre', 'Marca', 'Categoría', 'Precio'],
            ['A15', 'Galaxy A15', 'Samsung', 'Teléfonos', 999],
        ]), ['modo' => ServicioImportacionProductos::MODO_SOLO_CREAR]);

        $this->assertSame(1, $analisis['resumen']['omitir']);
    }

    // -- Pantalla y permisos ---------------------------------------------------

    public function test_asistente_completo_desde_la_pantalla(): void
    {
        Storage::fake('local');
        $admin = $this->usuario('admin');
        $archivo = UploadedFile::fake()->createWithContent('inventario.xlsx', file_get_contents($this->excel([
            ['Código', 'Producto', 'Marca', 'Categoría', 'PVP', 'Cantidad'],
            ['C-1', 'Cargador 20W', 'Apple', 'Accesorios', 15, 4],
        ])));

        Livewire::actingAs($admin)->test(ImportarProductos::class)
            ->set('archivo', $archivo)
            ->call('subir')
            ->assertSet('paso', 2)
            ->assertSet('mapeo.precio_detal', '4')
            ->call('analizar')
            ->assertSet('paso', 3)
            ->assertSee('Cargador 20W')
            ->call('importar')
            ->assertHasNoErrors()
            ->assertSet('paso', 4);

        $this->assertSame(4, Product::where('codigo', 'C-1')->value('stock'));
        $this->assertSame([], Storage::disk('local')->files('importaciones'), 'el archivo se borra al terminar');
    }

    public function test_vendedor_no_puede_importar(): void
    {
        $vendedor = $this->usuario('vendedor');

        $this->actingAs($vendedor)->get(route('admin.productos.importar'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('admin.productos.importar.plantilla'))->assertForbidden();
    }

    public function test_plantilla_se_reconoce_sin_ajustes(): void
    {
        $respuesta = $this->actingAs($this->usuario('admin'))->get(route('admin.productos.importar.plantilla'));
        $respuesta->assertOk();

        $ruta = tempnam(sys_get_temp_dir(), 'pla') . '.xlsx';
        file_put_contents($ruta, $respuesta->streamedContent());
        $filas = app(LectorHojaCalculo::class)->leer($ruta)['filas'];

        $this->assertCount(count(ServicioImportacionProductos::CAMPOS), $this->servicio->sugerirMapeo($filas[1]));
        $this->assertSame(['crear' => 2, 'actualizar' => 0, 'omitir' => 0, 'error' => 0], $this->analizarArchivo($ruta)['resumen']);
    }
}
