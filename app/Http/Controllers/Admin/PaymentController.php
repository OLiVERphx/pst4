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
        // ruta_comprobante es relativa al disco 'local' (storage/app/private)
        $disk = Storage::disk('local');
        if (!$payment->ruta_comprobante || !$disk->exists($payment->ruta_comprobante)) {
            abort(404);
        }
        return response()->file($disk->path($payment->ruta_comprobante));
    }
}
