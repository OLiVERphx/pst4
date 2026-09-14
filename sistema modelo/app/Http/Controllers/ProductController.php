<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\Category;
use Auth;
use Session;
use ParseCsv\Csv;

class ProductController extends Controller
{
    public function index()
    {
        $titulo = 'Listado de Productos';

        return view('product.list', [
            'titulo' => $titulo
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::select('id', 'name')->get();
        $vendors = Vendor::select('id', 'name')->get();
        $categories = Category::select('id', 'name')->get();
        return view('product.edit', [
            'brands' => $brands,
            'vendors' => $vendors,
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        if (count($data) > 0) {
            unset($data['_token']);
            $data['user_id'] = Auth::user()->id;
            if (isset($data['id']) && trim($data['id']) != '') {
                $id = $data['id'];
                unset($data['id']);
                Product::find($id)->update($data);
            } else {
                Product::create($data);
            }

            Session::flash('success', 'Se ha guardado el registro con exito');
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::find($id);
        $brands = Brand::select('id', 'name')->get();
        $vendors = Vendor::select('id', 'name')->get();
        $categories = Category::select('id', 'name')->get();
        return view('product.edit', [
            'brands' => $brands,
            'vendors' => $vendors,
            'categories' => $categories,
            'product' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function import(Request $request)
    {
        $data = $request->all();
        if (count($data) > 0) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('csv_files', $originalName);
            $filePath = storage_path('app/'.$path);
            $csv = new \ParseCsv\Csv();
            $csv->delimiter = ";";
            $csv->auto($filePath);
            $products = $csv->data;

            foreach ($products as $product) {
                $product = (array)$product;
                $code = trim($product['codigo']);
                if (!isset($code) || $code == '') {
                    continue;
                }
                $brand = Brand::where('name', $product['marca'])->first();
                if (!isset($brand)) {
                    $brand = Brand::create([
                        'name' => $product['marca']
                    ]);
                }
                $vendor = Vendor::where('name', $product['proveedor'])->first();
                if (!isset($vendor)) {
                    $vendor = Vendor::create([
                        'name' => $product['proveedor']
                    ]);
                }
                $category = Category::where('name', $product['categoria'])->first();
                if (!isset($category)) {
                    $category = Category::create([
                        'name' => $product['categoria']
                    ]);
                }
                $descripcion = isset($product['nombre']) ? trim($product['nombre']) : 'indefinido';
                $cantidad = isset($product['cantidad']) ? $product['cantidad'] : 0;
                $cantidad_minima = isset($product['cantidad_minima']) ? $product['cantidad_minima'] : 0;
                $cantidad_maxima = isset($product['cantidad_maxima']) ? $product['cantidad_maxima'] : 0;
                $costo = isset($product['costo']) ? $product['costo'] : 0;
                $precio = isset($product['precio']) ? $product['precio'] : 0;

                $product = Product::where('product_key', $code)->first();
                if (!isset($product)) {
                    Product::create([
                        'user_id' => Auth::user()->id,
                        'product_key' => $code,
                        'description' => $descripcion,
                        'qty' => $cantidad,
                        'min_qty' => $cantidad_minima,
                        'max_qty' => $cantidad_maxima,
                        'cost' => $costo,
                        'price' => $precio,
                        'brand_id' => $brand->id,
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                    ]);
                } else {
                    $product->description = $descripcion;
                    $product->qty = $cantidad;
                    $product->min_qty = $cantidad_minima;
                    $product->max_qty = $cantidad_maxima;
                    $product->cost = $costo;
                    $product->price = $precio;
                    $product->brand_id = $brand->id;
                    $product->vendor_id = $vendor->id;
                    $product->category_id = $category->id;
                    $product->save();
                }
            }
            Session::flash('success', 'Se ha importado el archivo correctamente.');
        }
        return view('product.import', []);
    }
}
