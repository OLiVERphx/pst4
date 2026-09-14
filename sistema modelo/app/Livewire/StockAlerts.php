<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StockAlert;
use App\Models\Product;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\Auth;

/**
 * Componente que muestra alertas de stock y permite reponer o marcar como leídas.
 */
class StockAlerts extends Component
{
    public $mostrarModal = false;

    public $form = [
        'producto_id' => null,
        'tipo' => 'entrada',
        'cantidad' => 1,
        'referencia' => null,
        'notas' => null,
    ];

    public function getAlertsProperty()
    {
        return StockAlert::with('product')->orderByDesc('created_at')->get();
    }

    public function openReponer($productId)
    {
        $this->reset('form');
        $this->form = [
            'producto_id' => $productId,
            'tipo' => 'entrada',
            'cantidad' => 1,
            'referencia' => 'Reposición por alerta',
            'notas' => null,
        ];
        $this->mostrarModal = true;
    }

    public function saveReponer()
    {
        $this->validate([
            'form.producto_id' => 'required|exists:products,id',
            'form.cantidad' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($this->form['producto_id']);
        $service = new ServicioInventario();
        $service->registrarMovimiento(
            $product,
            $this->form['tipo'],
            (int) $this->form['cantidad'],
            $this->form['referencia'] ?? null,
            $this->form['notas'] ?? null,
            Auth::user()
        );

        $this->mostrarModal = false;
        session()->flash('success', 'Producto repuesto.');
        $this->emit('movement-saved');
    }

    public function markAsRead($id)
    {
        $alert = StockAlert::find($id);
        if ($alert) {
            $alert->leido = true;
            $alert->save();
        }
    }

    public function render()
    {
        $alerts = $this->alerts;
        return view('livewire.stock-alerts', compact('alerts'));
    }
}
