<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class ControladorCatalogo extends Controller
{
    // Home with featured products
    public function index()
    {
        $featured = Product::with(['brand','category'])->where('activo', true)->where('destacado', true)->take(8)->get();
        $categories = Category::where('activo', true)->take(10)->get();
        return view('web.home', compact('featured','categories'));
    }

    // Catalog listing
    public function catalog(Request $request)
    {
        $q = Product::with(['brand','category'])->where('activo', true);
        if ($request->filled('category')) $q->where('categoria_id', $request->category);
        if ($request->filled('brand')) $q->where('marca_id', $request->brand);
        if ($request->filled('search')) {
            $search = $request->search;
            try {
                $q->whereRaw("MATCH(nombre, descripcion) AGAINST(? IN NATURAL LANGUAGE MODE)", [$search]);
            } catch (\Exception $e) {
                $q->where(function($qq) use ($search) {
                    $qq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }
        }
        $products = $q->paginate(24)->withQueryString();
        return view('web.catalog', compact('products'));
    }

    // Product detail
    public function show(Product $product)
    {
        return view('web.product', compact('product'));
    }
}
