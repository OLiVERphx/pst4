<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    public function index()
    {
        return view('admin.inventory.index');
    }

    public function alerts()
    {
        return view('admin.inventory.alerts');
    }
}
