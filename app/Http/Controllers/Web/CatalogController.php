<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;

class CatalogController extends Controller
{
    public function home()
    {
        $destacados = Product::with(['brand','category'])
            ->where('activo', true)
            ->where('stock', '>', 0)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $categorias = Category::where('activo', true)
            ->whereHas('products', fn($q) => $q->where('activo', true)->where('stock', '>', 0))
            ->get();

        // Recomendaciones para home: si hay destacados, obtener relacionados del primero
        $recomendados = collect();
        if ($destacados->isNotEmpty()) {
            try {
                $svc = new \App\Services\ServicioBusquedaInteligente();
                $ids = $svc->relatedProducts($destacados->first()->id, ['tfidf'=>0.5,'buscado'=>0.2,'vendido'=>0.2,'stock'=>0.1], 6);
                if (!empty($ids)) {
                    $found = Product::with(['brand','category'])->whereIn('id', $ids)->get()->keyBy('id');
                    $recomendados = collect($ids)->filter(fn($id)=>isset($found[$id]))->map(fn($id)=>$found[$id])->values();
                }
            } catch (\Exception $e) {
                // silent fail: no recomendaciones
            }
        }

        return view('web.home', compact('destacados', 'categorias','recomendados'));
    }

    public function catalog(Request $request)
    {
        $emojis = [
            'Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌',
            'Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗',
        ];

        $query = Product::with(['brand','category'])
            ->where('activo', true)
            ->where('stock', '>', 0);

        if ($request->categoria) {
            $query->whereHas('category', fn($c) => $c->where('nombre', $request->categoria));
        }
        if ($request->marca) {
            $query->whereHas('brand', fn($b) => $b->where('nombre', $request->marca));
        }

        if ($request->buscar) {
            $term = $request->buscar;
            try {
                $svc = new \App\Services\ServicioBusquedaInteligente();
                $ids = $svc->search($term, 500);
                if (!empty($ids)) {
                    $query->whereIn('id', $ids);
                } else {
                    $query->where(fn($w) =>
                        $w->where('nombre', 'like', "%{$term}%")
                          ->orWhere('codigo', 'like', "%{$term}%")
                          ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$term}%"))
                    );
                }
            } catch (\Exception $e) {
                $query->where(fn($w) =>
                    $w->where('nombre', 'like', "%{$term}%")
                      ->orWhere('codigo', 'like', "%{$term}%")
                      ->orWhereHas('brand', fn($b) => $b->where('nombre', 'like', "%{$term}%"))
                );
            }
        }

        $orden = $request->get('orden', 'default');
        match($orden) {
            'precio-asc'  => $query->orderBy('precio_detal','asc'),
            'precio-desc' => $query->orderBy('precio_detal','desc'),
            'nombre'      => $query->orderBy('nombre','asc'),
            default       => $query->latest(),
        };

        $productos  = $query->get();
        $categorias = \App\Models\Category::where('activo',true)->get();
        $marcas     = \App\Models\Brand::all();

        $productosJson = $productos->map(fn($p) => [
            'id'           => $p->id,
            'codigo'       => $p->codigo,
            'nombre'       => $p->nombre,
            'slug'         => $p->slug ?? \Str::slug($p->nombre).'-'.$p->id,
            'marca'        => $p->brand?->nombre,
            'categoria'    => $p->category?->nombre,
            'precio_detal' => (float)$p->precio_detal,
            'precio_mayor' => (float)$p->precio_mayor,
            'stock'        => (int)$p->stock,
            'stock_minimo' => (int)$p->stock_minimo,
            'emoji'        => $emojis[$p->category?->nombre] ?? '📦',
            'es_nuevo'     => $p->created_at?->diffInDays(now()) <= 30,
            'stock_bajo'   => $p->stock <= $p->stock_minimo && $p->stock > 0,
        ]);

        return view('web.catalog', compact('productos','categorias','marcas','productosJson','emojis'));
    }

    public function show(Product $product)
    {
        $emojis = [
            'Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌',
            'Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗',
        ];
        $product->load(['brand','category']);
        // Obtener productos relacionados usando ServicioBusquedaInteligente
        $relacionados = Product::where('id','!=',$product->id)->where('activo', true)->where('stock','>',0)->limit(4)->get();
        try {
            $svc = new \App\Services\ServicioBusquedaInteligente();
            $ids = $svc->relatedProducts($product->id, ['tfidf'=>0.6,'buscado'=>0.15,'vendido'=>0.15,'stock'=>0.1], 4);
            if (!empty($ids)) {
                $found = Product::with(['brand','category'])->whereIn('id', $ids)->get()->keyBy('id');
                $relacionados = collect($ids)->filter(fn($id)=>isset($found[$id]))->map(fn($id)=>$found[$id])->values();
            }
        } catch (\Exception $e) {
            // fallback a los relacionados por categoría si algo falla
        }

        return view('web.product', compact('product','relacionados','emojis'));
    }
}
