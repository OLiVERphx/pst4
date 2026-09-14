<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ControladorCatalogo extends Controller
{
    // Retorna productos activos con filtros: category, brand, search, sort
    public function index(Request $request)
    {
        $q = Product::with(['brand','category'])->where('activo', true);

        if ($request->filled('category')) {
            $q->where('categoria_id', $request->category);
        }
        if ($request->filled('brand')) {
            $q->where('marca_id', $request->brand);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // Intentar FULLTEXT; fallback a LIKE
            try {
                $q->whereRaw("MATCH(nombre, descripcion) AGAINST(? IN NATURAL LANGUAGE MODE)", [$search]);
            } catch (\Exception $e) {
                $q->where(function($qq) use ($search) {
                    $qq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }
        }

        if ($request->filled('sort')) {
            $direction = $request->get('direction', 'asc');
            $q->orderBy($request->sort, $direction);
        }

        return response()->json($q->paginate(20));
    }

    // Retorna un producto con relaciones
    public function show($id)
    {
        $product = Product::with(['brand','category'])->findOrFail($id);
        return response()->json($product);
    }

    // Búsqueda rápida para sugerencias
    public function search(Request $request)
    {
        $q = $request->query('q', '');
        if (!strlen($q)) return response()->json([]);

        $items = Product::where('activo', true)
            ->where(function($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('descripcion', 'like', "%{$q}%");
            })
            ->limit(6)
            ->get(['id','nombre','slug','precio_detal','categoria_id']);

        // Attach category name if possible
        $items->load('category');

        return response()->json($items);
    }
}
