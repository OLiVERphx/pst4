<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ServicioBusquedaInteligente;

class CatalogController extends Controller
{
    // Lista de productos con filtros, paginación y datos del emoji por categoría
    public function index(Request $request)
    {
        $emojis = [
            'Audífonos'   => '🎧',
            'Fundas'      => '📱',
            'Cargadores'  => '🔌',
            'Cables'      => '🔗',
            'Protectores' => '🛡️',
            'Baterías'    => '🔋',
            'Soportes'    => '🚗',
        ];

        $q = Product::with(['brand', 'category'])
            ->where('activo', true)
            ->where('stock', '>', 0);

        if ($request->categoria) {
            $q->whereHas('category', fn($c) => $c->where('nombre', $request->categoria));
        }
        if ($request->marca) {
            $q->whereHas('brand', fn($b) => $b->where('nombre', $request->marca));
        }
        if ($request->buscar) {
            $term = $request->buscar;

            // Intentar usar búsqueda TF-IDF si el índice está disponible.
            try {
                $svc = new ServicioBusquedaInteligente();
                $ids = $svc->search($term, 500); // buscar hasta 500 coincidencias

                if (!empty($ids)) {
                    // Aplicar filtros de categoría/marca sobre el conjunto resultante
                    $q->whereIn('id', $ids);
                } else {
                    // Fallback a LIKE si el índice no devuelve nada
                    $q->where(fn($w) =>
                        $w->where('nombre', 'like', "%{$term}%")
                          ->orWhere('codigo', 'like', "%{$term}%")
                          ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$term}%"))
                    );
                }
            } catch (\Exception $e) {
                // En caso de error con el servicio, fallback a búsqueda LIKE (segura)
                $q->where(fn($w) =>
                    $w->where('nombre', 'like', "%{$term}%")
                      ->orWhere('codigo', 'like', "%{$term}%")
                      ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$term}%"))
                );
            }
        }

        $sort = $request->get('orden', 'default');
        match ($sort) {
            'precio-asc'  => $q->orderBy('precio_detal', 'asc'),
            'precio-desc' => $q->orderBy('precio_detal', 'desc'),
            'nombre'      => $q->orderBy('nombre', 'asc'),
            default       => $q->orderBy('created_at', 'desc'),
        };

        $products = $q->get()->map(fn($p) => [
            'id'           => $p->id,
            'codigo'       => $p->codigo,
            'nombre'       => $p->nombre,
            'slug'         => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
            'marca'        => $p->brand?->nombre,
            'categoria'    => $p->category?->nombre,
            'precio_detal' => (float) $p->precio_detal,
            'precio_mayor' => (float) $p->precio_mayor,
            'stock'        => (int) $p->stock,
            'stock_minimo' => (int) $p->stock_minimo,
            'destacado'    => (bool) $p->destacado,
            'emoji'        => $emojis[$p->category?->nombre] ?? '📦',
            'es_nuevo'     => $p->created_at?->diffInDays(now()) <= 30,
            'stock_bajo'   => $p->stock <= $p->stock_minimo && $p->stock > 0,
        ]);

        // Registrar búsqueda en registros_busqueda si se proporcionó el parámetro buscar
        if ($request->buscar) {
            try {
                DB::table('registros_busqueda')->insert([
                    'usuario_id' => auth()->id(),
                    'sesion_id' => session()->getId(),
                    'busqueda' => $request->buscar,
                    'total_resultados' => $products->count(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // silent fail if table missing
            }
        }

        return response()->json($products);
    }

    // Búsqueda para sugerencias del navbar (máx 6 resultados, rápida)
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen(trim($q)) < 2) return response()->json([]);

        $emojis = [
            'Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌',
            'Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗',
        ];

        try {
            $svc = new ServicioBusquedaInteligente();
            $ids = $svc->search($q, 6);
            if (!empty($ids)) {
                $found = Product::with(['brand','category'])
                    ->where('activo', true)
                    ->where('stock', '>', 0)
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                $results = collect($ids)
                    ->filter(fn($id) => isset($found[$id]))
                    ->map(fn($id) => $found[$id])
                    ->map(fn($p) => [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'slug' => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
                        'marca' => $p->brand?->nombre,
                        'categoria' => $p->category?->nombre,
                        'precio_detal' => (float) $p->precio_detal,
                        'emoji' => $emojis[$p->category?->nombre] ?? '📦',
                    ])
                    ->values();
            } else {
                // Fallback a LIKE
                $results = Product::with(['brand', 'category'])
                    ->where('activo', true)
                    ->where('stock', '>', 0)
                    ->where(fn($w) =>
                        $w->where('nombre', 'like', "%{$q}%")
                          ->orWhere('codigo', 'like', "%{$q}%")
                          ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$q}%"))
                          ->orWhereHas('category', fn($c) => $c->where('nombre', 'like', "%{$q}%"))
                    )
                    ->limit(6)
                    ->get()
                    ->map(fn($p) => [
                        'id'          => $p->id,
                        'nombre'      => $p->nombre,
                        'slug'        => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
                        'marca'       => $p->brand?->nombre,
                        'categoria'   => $p->category?->nombre,
                        'precio_detal'=> (float) $p->precio_detal,
                        'emoji'       => $emojis[$p->category?->nombre] ?? '📦',
                    ]);
            }
        } catch (\Exception $e) {
            // Si falla el servicio, fallback a LIKE
            $results = Product::with(['brand', 'category'])
                ->where('activo', true)
                ->where('stock', '>', 0)
                ->where(fn($w) =>
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%")
                      ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$q}%"))
                      ->orWhereHas('category', fn($c) => $c->where('nombre', 'like', "%{$q}%"))
                )
                ->limit(6)
                ->get()
                ->map(fn($p) => [
                    'id'          => $p->id,
                    'nombre'      => $p->nombre,
                    'slug'        => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
                    'marca'       => $p->brand?->nombre,
                    'categoria'   => $p->category?->nombre,
                    'precio_detal'=> (float) $p->precio_detal,
                    'emoji'       => $emojis[$p->category?->nombre] ?? '📦',
                ]);
        }

        // Registrar búsqueda en la tabla registros_busqueda
        try {
            DB::table('registros_busqueda')->insert([
                'usuario_id'    => auth()->id(),
                'sesion_id'     => session()->getId(),
                'busqueda'      => $q,
                'total_resultados' => $results->count(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Exception $e) {
            // silent fail if table doesn't exist
        }

        return response()->json($results);
    }

    // Productos relacionados / de interés para un producto dado
    public function related(int $id, Request $request)
    {
        // Pesos configurables: tfidf, buscado, vendido, stock
        // Ajustar aquí según negocio. Comentados para la sustentación: el componente tfidf
        // tiene mayor peso por su relación semántica; ventas y búsquedas refuerzan popularidad,
        // stock incentiva priorizar liquidación.
        $weights = [
            'tfidf' => 0.5,
            'buscado' => 0.2,
            'vendido' => 0.2,
            'stock' => 0.1,
        ];

        $limit = (int) $request->get('limit', 6);

        try {
            $svc = new ServicioBusquedaInteligente();
            $ids = $svc->relatedProducts($id, $weights, $limit);

            if (empty($ids)) return response()->json([]);

            $found = Product::with(['brand','category'])->whereIn('id', $ids)->get()->keyBy('id');
            $emojis = [
                'Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌',
                'Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗',
            ];

            $results = collect($ids)
                ->filter(fn($pid) => isset($found[$pid]))
                ->map(fn($pid) => $found[$pid])
                ->map(fn($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'slug' => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
                    'marca' => $p->brand?->nombre,
                    'categoria' => $p->category?->nombre,
                    'precio_detal' => (float) $p->precio_detal,
                    'stock' => (int) $p->stock,
                    'emoji' => $emojis[$p->category?->nombre] ?? '📦',
                ])
                ->values();

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    // Detalle de un producto por ID
    public function show(int $id)
    {
        $emojis = [
            'Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌',
            'Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗',
        ];

        $p = Product::with(['brand', 'category'])
            ->where('activo', true)
            ->findOrFail($id);

        return response()->json([
            'id'           => $p->id,
            'codigo'       => $p->codigo,
            'nombre'       => $p->nombre,
            'slug'         => $p->slug ?? Str::slug($p->nombre) . '-' . $p->id,
            'descripcion'  => $p->descripcion,
            'marca'        => $p->brand?->nombre,
            'categoria'    => $p->category?->nombre,
            'precio_detal' => (float) $p->precio_detal,
            'precio_mayor' => (float) $p->precio_mayor,
            'stock'        => (int) $p->stock,
            'stock_minimo' => (int) $p->stock_minimo,
            'emoji'        => $emojis[$p->category?->nombre] ?? '📦',
            'es_nuevo'     => $p->created_at?->diffInDays(now()) <= 30,
        ]);
    }
}
