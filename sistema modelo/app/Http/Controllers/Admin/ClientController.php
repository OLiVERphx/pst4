<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public function index()
    {
        return view('admin.clients.index');
    }
}
