<?php

namespace App\Services\Importacion;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\TasaBcv;
use App\Models\User;
use App\Services\ServicioAuditoria;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importación de productos desde una hoja de cálculo cualquiera.
 *
 * El archivo no tiene un formato fijo: el usuario indica qué columna corresponde a cada campo
 * (el sistema lo sugiere por sinónimos del encabezado). Cada fila se valida antes de guardar y
 * la importación completa va en una transacción: o entra todo lo válido o no entra nada.
 * El stock nunca se escribe directo: se registra como movimiento de inventario.
 */
class ServicioImportacionProductos
{
    public const MODO_CREAR_Y_ACTUALIZAR = 'crear_actualizar';
    public const MODO_SOLO_CREAR = 'solo_crear';
    public const MODO_SOLO_ACTUALIZAR = 'solo_actualizar';

    /**
     * Campos que se pueden importar. Los sinónimos se comparan sin tildes, en minúsculas y
     * sin signos; "etiqueta" es además el encabezado de la plantilla descargable.
     * Se ajustarán con el Excel real de la tienda.
     */
    public const CAMPOS = [
        'codigo' => ['etiqueta' => 'Código', 'requerido' => true, 'sinonimos' => [
            'codigo', 'cod', 'sku', 'referencia', 'ref', 'codigo producto', 'codigo del producto',
            'codigo articulo', 'cod producto', 'cod articulo', 'id producto',
        ]],
        'nombre' => ['etiqueta' => 'Nombre', 'requerido' => true, 'sinonimos' => [
            'nombre', 'producto', 'nombre producto', 'nombre del producto', 'articulo', 'descripcion',
            'descripcion producto', 'descripcion del producto', 'equipo',
        ]],
        'descripcion' => ['etiqueta' => 'Descripción', 'requerido' => false, 'sinonimos' => [
            'descripcion larga', 'detalle', 'detalles', 'caracteristicas', 'especificaciones', 'observaciones',
        ]],
        'marca' => ['etiqueta' => 'Marca', 'requerido' => true, 'sinonimos' => [
            'marca', 'fabricante', 'brand',
        ]],
        'categoria' => ['etiqueta' => 'Categoría', 'requerido' => true, 'sinonimos' => [
            'categoria', 'tipo', 'linea', 'familia', 'departamento', 'grupo', 'rubro', 'clase',
        ]],
        'precio_detal' => ['etiqueta' => 'Precio detal', 'requerido' => true, 'sinonimos' => [
            'precio', 'precio detal', 'precio al detal', 'detal', 'pvp', 'p v p', 'precio venta', 'precio de venta',
            'precio unitario', 'precio usd', 'precio dolares', 'venta',
        ]],
        'precio_mayor' => ['etiqueta' => 'Precio mayor', 'requerido' => false, 'sinonimos' => [
            'precio mayor', 'precio al mayor', 'precio por mayor', 'mayor', 'al mayor', 'mayorista', 'precio mayorista',
        ]],
        'precio_costo' => ['etiqueta' => 'Precio costo', 'requerido' => false, 'sinonimos' => [
            'costo', 'precio costo', 'precio de costo', 'costo unitario', 'precio compra', 'precio de compra', 'compra',
        ]],
        'stock' => ['etiqueta' => 'Stock', 'requerido' => false, 'sinonimos' => [
            'stock', 'existencia', 'existencias', 'cantidad', 'cant', 'inventario', 'disponible', 'unidades', 'qty',
        ]],
        'stock_minimo' => ['etiqueta' => 'Stock mínimo', 'requerido' => false, 'sinonimos' => [
            'stock minimo', 'minimo', 'existencia minima', 'punto de reorden', 'reorden', 'stock min',
        ]],
        'min_cantidad_mayor' => ['etiqueta' => 'Cantidad mínima al mayor', 'requerido' => false, 'sinonimos' => [
            'cantidad minima al mayor', 'cantidad minima mayor', 'minimo mayor', 'min mayor', 'cantidad mayor',
        ]],
        'activo' => ['etiqueta' => 'Activo', 'requerido' => false, 'sinonimos' => [
            'activo', 'estado', 'habilitado', 'visible', 'publicado',
        ]],
    ];

    private const CAMPOS_PRECIO = ['precio_detal', 'precio_mayor', 'precio_costo'];
    private const CAMPOS_ENTEROS = ['stock', 'stock_minimo', 'min_cantidad_mayor'];

    public function __construct(private ServicioInventario $inventario)
    {
    }

    /** Texto comparable: sin tildes, minúsculas, solo letras/números separados por un espacio. */
    public static function normalizar(?string $texto): string
    {
        $texto = Str::lower(Str::ascii((string) $texto));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $texto));
    }

    /**
     * La fila de encabezados es la que más nombres de campo reconoce entre las primeras 15
     * (muchos archivos traen arriba el nombre de la empresa, la fecha o un logo).
     */
    public function detectarFilaEncabezados(array $filas): ?int
    {
        $mejor = null;
        $mejorPuntaje = 0;

        foreach (array_slice($filas, 0, 15, true) as $numero => $celdas) {
            $textos = array_filter($celdas, fn ($c) => is_string($c) && !is_numeric($c));
            $puntaje = count($this->sugerirMapeo($celdas)) * 10 + count($textos);
            if (count($textos) >= 2 && $puntaje > $mejorPuntaje) {
                $mejor = $numero;
                $mejorPuntaje = $puntaje;
            }
        }

        return $mejor ?? array_key_first($filas);
    }

    /**
     * Propone qué columna corresponde a cada campo.
     *
     * @param array<int, mixed> $encabezados textos de la fila de encabezados, por índice de columna
     * @return array<string, int> campo => índice de columna (solo los reconocidos)
     */
    public function sugerirMapeo(array $encabezados): array
    {
        $candidatos = [];
        foreach ($encabezados as $columna => $encabezado) {
            $texto = self::normalizar(is_scalar($encabezado) ? (string) $encabezado : '');
            if ($texto === '') {
                continue;
            }
            foreach (self::CAMPOS as $campo => $definicion) {
                $sinonimos = [...$definicion['sinonimos'], self::normalizar($definicion['etiqueta'])];
                foreach ($sinonimos as $sinonimo) {
                    if ($texto === $sinonimo) {
                        $puntaje = 1000;
                    } elseif (preg_match('/\b' . preg_quote($sinonimo, '/') . '\b/', $texto)) {
                        // "Precio USD" contiene "precio"; un sinónimo más largo es más específico
                        $puntaje = 100 + strlen($sinonimo);
                    } else {
                        continue;
                    }
                    $candidatos[] = [$puntaje, $campo, $columna];
                }
            }
        }

        // Se asigna de mayor a menor coincidencia: cada campo y cada columna se usan una sola vez
        usort($candidatos, fn ($a, $b) => $b[0] <=> $a[0] ?: $a[2] <=> $b[2]);
        $mapeo = [];
        $columnasUsadas = [];
        foreach ($candidatos as [, $campo, $columna]) {
            if (!isset($mapeo[$campo]) && !isset($columnasUsadas[$columna])) {
                $mapeo[$campo] = $columna;
                $columnasUsadas[$columna] = true;
            }
        }

        return $mapeo;
    }

    /**
     * Valida cada fila sin guardar nada.
     *
     * @param array<int, array<int, mixed>> $filas filas del lector (número de fila => celdas)
     * @param array<string, int|string|null> $mapeo campo => índice de columna
     * @param array{modo?: string, moneda?: string, crear_faltantes?: bool, puede_ajustar_stock?: bool} $opciones
     * @return array{errores_generales: string[], filas: array<int, array>, resumen: array<string, int>, marcas_nuevas: string[], categorias_nuevas: string[], tasa: ?float}
     */
    public function analizar(array $filas, int $filaEncabezados, array $mapeo, array $opciones = []): array
    {
        $modo = $opciones['modo'] ?? self::MODO_CREAR_Y_ACTUALIZAR;
        $moneda = $opciones['moneda'] ?? 'USD';
        $crearFaltantes = $opciones['crear_faltantes'] ?? true;

        $mapeo = array_filter($mapeo, fn ($c) => $c !== null && $c !== '');
        $mapeo = array_map('intval', array_intersect_key($mapeo, self::CAMPOS));

        $generales = [];
        if (!isset($mapeo['codigo'])) {
            $generales[] = 'Indique la columna del código: es lo que identifica a cada producto para no duplicarlo.';
        }
        if ($modo !== self::MODO_SOLO_ACTUALIZAR) {
            foreach (['nombre', 'marca', 'categoria', 'precio_detal'] as $campo) {
                if (!isset($mapeo[$campo])) {
                    $generales[] = 'Para crear productos falta indicar la columna "' . self::CAMPOS[$campo]['etiqueta'] . '".';
                }
            }
        }
        if (isset($mapeo['stock']) && !($opciones['puede_ajustar_stock'] ?? false)) {
            $generales[] = 'Su usuario no tiene permiso para ajustar inventario: quite la columna "Stock" del mapeo.';
        }
        if (count($mapeo) !== count(array_unique($mapeo))) {
            $generales[] = 'Hay una misma columna asignada a dos campos.';
        }

        $tasa = TasaBcv::vigenteEn()?->tasa;
        $tasa = $tasa !== null ? (float) $tasa : null;
        if ($moneda === 'VES' && !$tasa) {
            $generales[] = 'Los precios están en bolívares pero no hay tasa BCV registrada para convertirlos.';
        }

        $resultado = [
            'errores_generales' => $generales,
            'filas' => [],
            'resumen' => ['crear' => 0, 'actualizar' => 0, 'omitir' => 0, 'error' => 0],
            'marcas_nuevas' => [],
            'categorias_nuevas' => [],
            'tasa' => $tasa,
        ];
        if ($generales) {
            return $resultado;
        }

        $marcas = Brand::all()->keyBy(fn ($m) => self::normalizar($m->nombre));
        $categorias = Category::all()->keyBy(fn ($c) => self::normalizar($c->nombre));
        $codigosArchivo = [];

        // Productos existentes de una vez (incluye eliminados: su código sigue ocupado en la base de datos)
        $codigos = [];
        foreach ($filas as $numero => $celdas) {
            if ($numero > $filaEncabezados && ($codigo = $this->texto($celdas[$mapeo['codigo']] ?? null)) !== null) {
                $codigos[] = $codigo;
            }
        }
        $existentes = Product::withTrashed()->whereIn('codigo', array_unique($codigos))->get()
            ->keyBy(fn ($p) => Str::lower($p->codigo));

        foreach ($filas as $numero => $celdas) {
            if ($numero <= $filaEncabezados) {
                continue;
            }

            $crudo = [];
            foreach ($mapeo as $campo => $columna) {
                $crudo[$campo] = $celdas[$columna] ?? null;
            }
            if (count(array_filter($crudo, fn ($v) => $v !== null)) === 0) {
                continue;
            }

            $fila = ['fila' => $numero, 'accion' => 'error', 'codigo' => null, 'nombre' => null,
                'datos' => [], 'marca' => null, 'categoria' => null, 'errores' => [], 'avisos' => [], 'producto_id' => null];

            $codigo = $this->texto($crudo['codigo'] ?? null);
            $fila['codigo'] = $codigo;
            $fila['nombre'] = $this->texto($crudo['nombre'] ?? null);

            if ($codigo === null) {
                // Filas de totales o subtítulos sin código no son productos
                if (preg_match('/\btotal(es)?\b/', self::normalizar(implode(' ', array_map('strval', array_filter($celdas, 'is_scalar')))))) {
                    continue;
                }
                $fila['errores'][] = 'Sin código.';
            } elseif (mb_strlen($codigo) > 30) {
                $fila['errores'][] = 'El código tiene más de 30 caracteres.';
            } elseif (isset($codigosArchivo[Str::lower($codigo)])) {
                $fila['errores'][] = 'Código repetido: ya aparece en la fila ' . $codigosArchivo[Str::lower($codigo)] . '.';
            } else {
                $codigosArchivo[Str::lower($codigo)] = $numero;
            }

            $existente = $codigo !== null ? $existentes->get(Str::lower($codigo)) : null;
            if ($existente?->trashed()) {
                $fila['errores'][] = 'El código pertenece a un producto eliminado; restáurelo o use otro código.';
            }

            // Valores
            if ($fila['nombre'] !== null && mb_strlen($fila['nombre']) > 200) {
                $fila['errores'][] = 'El nombre tiene más de 200 caracteres.';
            }
            if ($fila['nombre'] !== null) {
                $fila['datos']['nombre'] = $fila['nombre'];
            }
            if (($descripcion = $this->texto($crudo['descripcion'] ?? null)) !== null) {
                $fila['datos']['descripcion'] = $descripcion;
            }

            foreach (self::CAMPOS_PRECIO as $campo) {
                if (($crudo[$campo] ?? null) === null) {
                    continue;
                }
                $numeroLeido = $this->aNumero($crudo[$campo]);
                if ($numeroLeido === null) {
                    $fila['errores'][] = self::CAMPOS[$campo]['etiqueta'] . ': "' . $crudo[$campo] . '" no es un número.';
                    continue;
                }
                [$valor, $monedaCelda, $aviso] = $numeroLeido;
                if ($aviso) {
                    $fila['avisos'][] = self::CAMPOS[$campo]['etiqueta'] . ': ' . $aviso;
                }
                if (($monedaCelda ?? $moneda) === 'VES') {
                    if (!$tasa) {
                        $fila['errores'][] = self::CAMPOS[$campo]['etiqueta'] . ' en bolívares sin tasa BCV para convertir.';
                        continue;
                    }
                    $valor = $valor / $tasa;
                    $fila['avisos'][] = self::CAMPOS[$campo]['etiqueta'] . ' convertido de Bs a USD con tasa ' . number_format($tasa, 2, ',', '.') . '.';
                }
                if ($valor < 0) {
                    $fila['errores'][] = self::CAMPOS[$campo]['etiqueta'] . ' negativo.';
                    continue;
                }
                $fila['datos'][$campo] = round($valor, 2);
            }

            foreach (self::CAMPOS_ENTEROS as $campo) {
                if (($crudo[$campo] ?? null) === null) {
                    continue;
                }
                $numeroLeido = $this->aNumero($crudo[$campo]);
                $valor = $numeroLeido[0] ?? null;
                if ($valor === null || $valor < 0 || floor($valor) != $valor) {
                    $fila['errores'][] = self::CAMPOS[$campo]['etiqueta'] . ': "' . $crudo[$campo] . '" debe ser un número entero no negativo.';
                    continue;
                }
                $fila['datos'][$campo] = (int) $valor;
            }

            if (($crudo['activo'] ?? null) !== null) {
                $activo = $this->aBooleano($crudo['activo']);
                if ($activo === null) {
                    $fila['errores'][] = 'Activo: "' . $crudo['activo'] . '" no se entiende (use Sí/No).';
                } else {
                    $fila['datos']['activo'] = $activo;
                }
            }

            // Marca y categoría: por nombre, sin importar tildes ni mayúsculas
            foreach (['marca' => [$marcas, 'marcas_nuevas', 'La marca'], 'categoria' => [$categorias, 'categorias_nuevas', 'La categoría']] as $campo => [$catalogo, $clave, $articulo]) {
                $nombre = $this->texto($crudo[$campo] ?? null);
                if ($nombre === null) {
                    continue;
                }
                $fila[$campo] = $nombre;
                if (mb_strlen($nombre) > 80) {
                    $fila['errores'][] = $articulo . ' tiene más de 80 caracteres.';
                } elseif (!$catalogo->has(self::normalizar($nombre))) {
                    if ($crearFaltantes) {
                        // Se avisa una sola vez en el resumen (marcas_nuevas / categorias_nuevas), no en cada fila
                        $resultado[$clave][self::normalizar($nombre)] = $nombre;
                    } else {
                        $fila['errores'][] = $articulo . ' "' . $nombre . '" no existe.';
                    }
                }
            }

            // Acción según el modo y si el código ya existe
            if ($existente && !$existente->trashed()) {
                $fila['producto_id'] = $existente->id;
                $fila['nombre'] ??= $existente->nombre;
                $accion = $modo === self::MODO_SOLO_CREAR ? 'omitir' : 'actualizar';
                if ($accion === 'omitir') {
                    $fila['avisos'][] = 'Ya existe; se omite porque el modo es "solo crear".';
                } elseif (!$this->tieneCambios($existente, $fila, $marcas, $categorias)) {
                    $accion = 'omitir';
                    $fila['avisos'][] = 'Sin cambios respecto a lo registrado.';
                }
            } else {
                $accion = $modo === self::MODO_SOLO_ACTUALIZAR ? 'omitir' : 'crear';
                if ($accion === 'omitir') {
                    $fila['avisos'][] = 'No existe; se omite porque el modo es "solo actualizar".';
                }
                if ($accion === 'crear') {
                    foreach (['nombre' => $fila['nombre'], 'marca' => $fila['marca'], 'categoria' => $fila['categoria'], 'precio_detal' => $fila['datos']['precio_detal'] ?? null] as $campo => $valor) {
                        if ($valor === null) {
                            $fila['errores'][] = 'Falta ' . Str::lower(self::CAMPOS[$campo]['etiqueta']) . ' para crear el producto.';
                        }
                    }
                    if (($fila['datos']['precio_detal'] ?? null) === 0.0) {
                        $fila['errores'][] = 'El precio detal no puede ser 0.';
                    }
                }
            }

            if ($accion === 'actualizar' && isset($fila['datos']['stock']) && $existente->stock_reservado > $fila['datos']['stock']) {
                $fila['avisos'][] = 'Hay ' . $existente->stock_reservado . ' unidades reservadas en pedidos en curso.';
            }

            $detal = $fila['datos']['precio_detal'] ?? $existente?->precio_detal;
            if ($detal !== null && isset($fila['datos']['precio_costo']) && $fila['datos']['precio_costo'] > $detal) {
                $fila['avisos'][] = 'El costo es mayor que el precio detal.';
            }
            if ($detal !== null && isset($fila['datos']['precio_mayor']) && $fila['datos']['precio_mayor'] > $detal) {
                $fila['avisos'][] = 'El precio al mayor es mayor que el precio detal.';
            }

            $fila['accion'] = $fila['errores'] ? 'error' : $accion;
            $resultado['resumen'][$fila['accion']]++;
            $resultado['filas'][] = $fila;
        }

        $resultado['marcas_nuevas'] = array_values($resultado['marcas_nuevas']);
        $resultado['categorias_nuevas'] = array_values($resultado['categorias_nuevas']);

        return $resultado;
    }

    /**
     * Guarda las filas válidas de un análisis. Las filas con error se saltan (el usuario ya las vio
     * en la vista previa); si algo falla al guardar, se revierte todo.
     *
     * @return array{creados: int, actualizados: int, movimientos: int, omitidos: int, con_error: int, referencia: string}
     */
    public function importar(array $analisis, User $usuario, string $nombreArchivo): array
    {
        if ($analisis['errores_generales']) {
            throw new \RuntimeException(implode(' ', $analisis['errores_generales']));
        }

        $referencia = 'IMP-' . now()->format('YmdHis');
        $notas = 'Importación de productos desde "' . Str::limit($nombreArchivo, 80) . '"';

        return DB::transaction(function () use ($analisis, $usuario, $nombreArchivo, $referencia, $notas) {
            $res = ['creados' => 0, 'actualizados' => 0, 'movimientos' => 0,
                'omitidos' => $analisis['resumen']['omitir'], 'con_error' => $analisis['resumen']['error'], 'referencia' => $referencia];

            $marcas = Brand::all()->keyBy(fn ($m) => self::normalizar($m->nombre));
            $categorias = Category::all()->keyBy(fn ($c) => self::normalizar($c->nombre));
            $marcasCreadas = [];
            $categoriasCreadas = [];

            foreach ($analisis['filas'] as $fila) {
                if (!in_array($fila['accion'], ['crear', 'actualizar'], true)) {
                    continue;
                }

                $datos = $fila['datos'];
                $stock = $datos['stock'] ?? null;
                unset($datos['stock']);

                if ($fila['marca'] !== null) {
                    $clave = self::normalizar($fila['marca']);
                    if (!$marcas->has($clave)) {
                        $marcas[$clave] = Brand::create(['nombre' => $fila['marca']]);
                        $marcasCreadas[] = $fila['marca'];
                    }
                    $datos['marca_id'] = $marcas[$clave]->id;
                }
                if ($fila['categoria'] !== null) {
                    $clave = self::normalizar($fila['categoria']);
                    if (!$categorias->has($clave)) {
                        $nombre = $fila['categoria'];
                        $categorias[$clave] = Category::create(['nombre' => $nombre, 'slug' => $this->slugUnico(Category::class, $nombre, 80), 'activo' => true]);
                        $categoriasCreadas[] = $fila['categoria'];
                    }
                    $datos['categoria_id'] = $categorias[$clave]->id;
                }

                if ($fila['accion'] === 'crear') {
                    $producto = Product::create($datos + [
                        'codigo' => $fila['codigo'],
                        'slug' => $this->slugUnico(Product::class, $datos['nombre'], 200),
                        // El esquema exige precio al mayor: si el archivo no lo trae, se usa el detal
                        'precio_mayor' => $datos['precio_detal'],
                        'stock' => 0,
                    ]);
                    ServicioAuditoria::registrar('producto.creado', $producto, null,
                        $producto->only(['codigo', 'nombre', 'precio_detal', 'precio_mayor', 'activo']) + ['origen' => $referencia], $usuario->id);
                    $res['creados']++;

                    if ($stock > 0) {
                        $this->inventario->registrarMovimiento($producto, 'entrada', $stock, $referencia, $notas . ' (carga inicial)', $usuario);
                        $res['movimientos']++;
                    }
                    continue;
                }

                $producto = Product::whereKey($fila['producto_id'])->lockForUpdate()->firstOrFail();
                $producto->fill($datos);
                $cambios = $producto->getDirty();
                if ($cambios) {
                    $antes = array_intersect_key($producto->getOriginal(), $cambios);
                    $producto->save();
                    ServicioAuditoria::registrar('producto.modificado', $producto, $antes, $cambios + ['origen' => $referencia], $usuario->id);
                }

                $diferencia = $stock !== null ? $stock - (int) $producto->stock : 0;
                if ($diferencia !== 0) {
                    $this->inventario->registrarMovimiento($producto, 'ajuste', $diferencia, $referencia, $notas . ' (stock ' . $producto->stock . ' → ' . $stock . ')', $usuario);
                    $res['movimientos']++;
                }
                if ($cambios || $diferencia !== 0) {
                    $res['actualizados']++;
                }
            }

            ServicioAuditoria::registrar('producto.importacion', null, null, [
                'archivo' => $nombreArchivo,
                'referencia' => $referencia,
                'creados' => $res['creados'],
                'actualizados' => $res['actualizados'],
                'movimientos_inventario' => $res['movimientos'],
                'filas_omitidas' => $res['omitidos'],
                'filas_con_error' => $res['con_error'],
                'marcas_creadas' => $marcasCreadas,
                'categorias_creadas' => $categoriasCreadas,
            ], $usuario->id);

            return $res;
        });
    }

    /** Si la fila cambia algo del producto existente (datos, marca, categoría o stock). */
    private function tieneCambios(Product $producto, array $fila, $marcas, $categorias): bool
    {
        foreach ($fila['datos'] as $campo => $valor) {
            $actual = $producto->{$campo};
            $distinto = is_float($valor) || in_array($campo, self::CAMPOS_ENTEROS, true)
                ? $actual === null || abs((float) $actual - $valor) > 0.001
                : $actual !== $valor;
            if ($distinto) {
                return true;
            }
        }
        foreach (['marca' => [$marcas, 'marca_id'], 'categoria' => [$categorias, 'categoria_id']] as $campo => [$catalogo, $columna]) {
            if ($fila[$campo] !== null && $catalogo->get(self::normalizar($fila[$campo]))?->id !== $producto->{$columna}) {
                return true;
            }
        }

        return false;
    }

    /**
     * Interpreta un precio o cantidad escrito de cualquier forma común:
     * 1234.5 (número de Excel), "1.234,56", "1,234.56", "$ 25", "Bs. 900,50", "25,5".
     *
     * @return array{0: float, 1: ?string, 2: ?string}|null [valor, moneda detectada (USD|VES|null), aviso]
     */
    public function aNumero(mixed $valor): ?array
    {
        if (is_int($valor) || is_float($valor)) {
            return [(float) $valor, null, null];
        }
        if (!is_string($valor)) {
            return null;
        }

        $texto = Str::lower(Str::ascii(trim($valor)));
        $moneda = null;
        if (preg_match('/(^|[^a-z])bs([^a-z]|$)|\bves\b|bolivar/', $texto)) {
            $moneda = 'VES';
        } elseif (preg_match('/\$|\busd\b|dolar|\bref\b/', $texto)) {
            $moneda = 'USD';
        }

        $texto = preg_replace('/[^0-9,.\-]/', '', $texto);
        if ($texto === '' || !preg_match('/^-?[0-9.,]*[0-9][0-9.,]*$/', $texto)) {
            return null;
        }

        $aviso = null;
        $ultimaComa = strrpos($texto, ',');
        $ultimoPunto = strrpos($texto, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            // El separador que aparece de último es el decimal
            $texto = $ultimaComa > $ultimoPunto
                ? str_replace(',', '.', str_replace('.', '', $texto))
                : str_replace(',', '', $texto);
        } elseif ($ultimaComa !== false) {
            // Solo comas: una sola es decimal (formato venezolano); varias son de miles
            $texto = substr_count($texto, ',') === 1 ? str_replace(',', '.', $texto) : str_replace(',', '', $texto);
        } elseif ($ultimoPunto !== false && (substr_count($texto, '.') > 1 || preg_match('/^-?\d{1,3}\.\d{3}$/', $texto))) {
            // "1.234.567" o "1.234": en Venezuela el punto separa miles
            $aviso = '"' . $valor . '" se leyó como ' . str_replace('.', '', $texto) . ' (punto como separador de miles).';
            $texto = str_replace('.', '', $texto);
        }

        if (!is_numeric($texto)) {
            return null;
        }

        return [(float) $texto, $moneda, $aviso];
    }

    private function aBooleano(mixed $valor): ?bool
    {
        if (is_bool($valor)) {
            return $valor;
        }
        if (is_int($valor) || is_float($valor)) {
            return in_array((int) $valor, [0, 1], true) ? (bool) $valor : null;
        }
        $texto = self::normalizar((string) $valor);

        return match (true) {
            in_array($texto, ['si', 's', 'x', '1', 'true', 'verdadero', 'activo', 'activa', 'habilitado', 'visible', 'publicado'], true) => true,
            in_array($texto, ['no', 'n', '0', 'false', 'falso', 'inactivo', 'inactiva', 'deshabilitado', 'oculto'], true) => false,
            default => null,
        };
    }

    /** Texto de una celda; los números enteros (códigos de barra) sin ".0" ni notación científica. */
    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if (is_float($valor) && floor($valor) == $valor && abs($valor) < 1e15) {
            $valor = sprintf('%.0f', $valor);
        }
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function slugUnico(string $modelo, string $nombre, int $largo): string
    {
        $base = Str::limit(Str::slug($nombre) ?: 'item', $largo - 6, '');
        $slug = $base;
        $i = 2;
        // Los productos eliminados conservan su slug (índice único), por eso se cuentan
        while (($modelo === Product::class ? Product::withTrashed() : $modelo::query())->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
