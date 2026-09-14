<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class TiendaController extends Controller
{
    public function index()
    {
        $products = Product::with(['brand', 'category'])->get();
        $productsData = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->product_key ?? '',
                'name' => $p->description ?? '',
                'brand' => optional($p->brand)->name ?? '',
                'category' => optional($p->category)->name ?? '',
                'price' => $p->price ?? 0,
                'priceMayor' => $p->price ?? 0,
                'cost' => $p->cost ?? 0,
                'qty' => $p->qty ?? 0,
                'minQty' => $p->min_qty ?? 0,
            ];
        });

        $possible = [
            base_path('html/SmartphoneWorld_Tienda.html'),
            base_path('../html/SmartphoneWorld_Tienda.html'),
            base_path('public/tienda/SmartphoneWorld_Tienda.html'),
        ];
        $htmlPath = null;
        foreach ($possible as $p) {
            if (file_exists($p)) { $htmlPath = $p; break; }
        }
        if (!$htmlPath) {
            abort(404, 'Prototype HTML not found. Tried: ' . implode(', ', $possible));
        }

        $html = file_get_contents($htmlPath);

        // We'll extract body and let Blade render assets and fetch products via JSON
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $matches)) {
            $body = $matches[1];
        } else {
            $body = $html;
        }

        // Remove inline <style> and inline <script> blocks
        $body = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $body);
        $body = preg_replace('/<script(?![^>]*src)[^>]*>.*?<\/script>/s', '', $body);

        // Return Blade view
        return view('tienda.index', ['body' => $body]);
    }

    public function productsJson()
    {
        $products = Product::with(['brand', 'category'])->get();
        $productsData = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->product_key ?? '',
                'name' => $p->description ?? '',
                'brand' => optional($p->brand)->name ?? '',
                'category' => optional($p->category)->name ?? '',
                'price' => $p->price ?? 0,
                'priceMayor' => $p->price ?? 0,
                'cost' => $p->cost ?? 0,
                'qty' => $p->qty ?? 0,
                'minQty' => $p->min_qty ?? 0,
            ];
        });

        return response()->json($productsData->values()->all());
    }

    public function product($id)
    {
        $p = Product::with(['brand', 'category'])->findOrFail($id);
        $data = [
            'id' => $p->id,
            'code' => $p->product_key ?? '',
            'name' => $p->description ?? '',
            'brand' => optional($p->brand)->name ?? '',
            'category' => optional($p->category)->name ?? '',
            'price' => $p->price ?? 0,
            'priceMayor' => $p->price ?? 0,
            'cost' => $p->cost ?? 0,
            'qty' => $p->qty ?? 0,
            'minQty' => $p->min_qty ?? 0,
        ];

        return response()->json($data);
    }
}
