<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    // Muestra la lista de productos (vista que monta el componente Livewire)
    public function index()
    {
        return view('admin.products.index');
    }
}
