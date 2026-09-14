<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments.index');
    }

    // Descargar/visualizar comprobante privado
    public function receipt($id)
    {
        $payment = Payment::findOrFail($id);
        if (!$payment->ruta_comprobante) {
            abort(404);
        }

        $full = storage_path('app/' . $payment->ruta_comprobante);
        if (!file_exists($full)) {
            abort(404);
        }

        return response()->file($full);
    }
}
