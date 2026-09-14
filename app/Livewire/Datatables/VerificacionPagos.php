<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use App\Models\Payment;
use App\Services\ServicioValidacionPagos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Componente Livewire para verificación de pagos.
 */
class VerificacionPagos extends Component
{
    public $rejectReason = '';
    public $showRejectModal = false;
    public $selectedPayment = null;

    protected $listeners = ['payment-updated' => '$refresh'];

    public function render()
    {
        $payments = Payment::where('estado', 'pendiente')->with('order.user')->get();
        return view('livewire.datatables.verificacion-pagos', ['payments' => $payments]);
    }

    public function approve($paymentId, ServicioValidacionPagos $service)
    {
        Gate::authorize('pagos.aprobar');

        $payment = Payment::find($paymentId);
        if (!$payment) {
            $this->addError('global', 'Pago no encontrado');
            return;
        }
        $service->approve($payment, Auth::user());
        $this->dispatch('payment-updated');
    }

    public function openReject($paymentId)
    {
        Gate::authorize('pagos.rechazar');
        
        $this->selectedPayment = $paymentId;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function reject(ServicioValidacionPagos $service)
    {
        Gate::authorize('pagos.rechazar');

        $this->validate(['rejectReason' => 'required|string|max:500']);
        $payment = Payment::find($this->selectedPayment);
        if (!$payment) {
            $this->addError('global', 'Pago no encontrado');
            return;
        }
        $service->reject($payment, Auth::user(), $this->rejectReason);
        $this->showRejectModal = false;
        $this->selectedPayment = null;
        $this->rejectReason = '';
        $this->dispatch('payment-updated');
    }
}

