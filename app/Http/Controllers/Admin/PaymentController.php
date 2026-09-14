<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments.index');
    }

    public function receipt(Payment $payment)
    {
        $path = storage_path('app/' . $payment->ruta_comprobante);
        if (!file_exists($path)) {
            abort(404);
        }
        // For now, stream file directly (private storage)
        return response()->file($path);
    }
}
