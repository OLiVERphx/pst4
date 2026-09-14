<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use App\Models\StockAlert;
use App\Models\Product;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\Auth;

/**
 * Livewire: Lista de alertas de stock.
 */
class StockAlerts extends Component
{
    public $showModal = false;
    public $productId = null;
    public $cantidad = 1;
    public $referencia = null;
    public $notas = null;

    protected $listeners = ['inventory-saved' => '$refresh'];

    public function render()
    {
        $alerts = StockAlert::with('product')->orderByDesc('created_at')->get();
        return view('livewire.datatables.stock-alerts', [
            'alerts' => $alerts,
        ]);
    }

    public function markAsRead(StockAlert $alert)
    {
        $alert->leido = true;
        $alert->save();
        $this->dispatch('inventory-saved');
    }

    public function openReplenish(StockAlert $alert)
    {
        $this->productId = $alert->producto_id;
        $this->cantidad = 1;
        $this->referencia = null;
        $this->notas = null;
        $this->showModal = true;
    }

    public function save(ServicioInventario $service)
    {
        $this->validate([
            'productId' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $product = Product::find($this->productId);
        if (!$product) {
            $this->addError('productId', 'Producto inválido');
            return;
        }

        $service->registrarMovimiento($product, 'entrada', (int)$this->cantidad, $this->referencia, $this->notas, Auth::user());

        // Marcar alertas del producto como leídas
        StockAlert::where('producto_id', $product->id)->update(['leido' => true]);

        $this->showModal = false;
        $this->dispatch('inventory-saved');
    }
}

