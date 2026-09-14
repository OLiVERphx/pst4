<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Payment;
use App\Services\ServicioValidacionPagos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VerificacionPagos extends Component
{
    public $reason = '';

    public function getPaymentsProperty()
    {
        return Payment::with(['order.user'])->where('estado', 'pendiente')->orderByDesc('created_at')->get();
    }

    public function approve($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $svc = new ServicioValidacionPagos();
        $svc->approve($payment, Auth::user());
        session()->flash('success', 'Payment approved.');
        $this->emit('payment-updated');
    }

    public function reject($paymentId, $reason = '')
    {
        $payment = Payment::findOrFail($paymentId);
        $svc = new ServicioValidacionPagos();
        $svc->reject($payment, Auth::user(), $reason ?: 'Rejected by admin');
        session()->flash('success', 'Payment rejected.');
        $this->emit('payment-updated');
    }

    // Helper to run validation on stored file
    protected function computeChecksFor(Payment $payment)
    {
        if (!$payment->ruta_comprobante) {
            return null;
        }

        $full = storage_path('app/' . $payment->ruta_comprobante);
        if (!file_exists($full)) {
            return null;
        }

        // Create a test UploadedFile instance pointing to the stored file
        $uploaded = new UploadedFile($full, basename($full), null, null, true);
        $svc = new ServicioValidacionPagos();
        return $svc->validarComprobante($uploaded);
    }

    public function render()
    {
        $payments = $this->payments;
        $checks = [];
        foreach ($payments as $p) {
            $checks[$p->id] = $this->computeChecksFor($p);
        }

        return view('livewire.verificacion-pagos', compact('payments', 'checks'));
    }
}

