<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\Auth;

/**
 * Livewire: Tabla de movimientos de inventario.
 */
class InventoryMovementsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    public $dateFrom = null;
    public $dateTo = null;
    public $showModal = false;
    public $form = [];

    protected $listeners = ['openInventoryModal' => 'openCreate'];

    public function getMovementsProperty()
    {
        $query = InventoryMovement::with(['product', 'user']);

        if ($this->search) {
            $q = $this->search;
            $query->whereHas('product', function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        if ($this->typeFilter) {
            $query->where('tipo', $this->typeFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->orderByDesc('created_at')->paginate(10);
    }

    public function render()
    {
        $products = Product::orderBy('nombre')->get();
        return view('livewire.datatables.inventory-movements-table', [
            'movements' => $this->movements,
            'products' => $products,
        ]);
    }

    public function openCreate($productId = null)
    {
        $this->reset(['form']);
        $this->form = [
            'producto_id' => $productId,
            'tipo' => 'entrada',
            'cantidad' => 1,
            'referencia' => null,
            'notas' => null,
        ];
        $this->showModal = true;
    }

    public function save(ServicioInventario $service)
    {
        $this->validate([
            'form.producto_id' => 'required|exists:productos,id',
            'form.tipo' => 'required|in:entrada,salida,ajuste,reserva,liberacion',
            'form.cantidad' => 'required|integer|min:1',
            'form.referencia' => 'nullable|string|max:100',
            'form.notas' => 'nullable|string',
        ]);

        $product = Product::find($this->form['producto_id']);
        if (!$product) {
            $this->addError('form.producto_id', 'Producto inválido');
            return;
        }

        $service->registrarMovimiento($product, $this->form['tipo'], (int)$this->form['cantidad'], $this->form['referencia'] ?? null, $this->form['notas'] ?? null, Auth::user());

        $this->showModal = false;
        $this->resetPage();
        $this->dispatch('inventory-saved');
    }
}

