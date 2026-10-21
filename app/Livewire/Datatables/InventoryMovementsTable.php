<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('inventario.ajustar');

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
        Gate::authorize('inventario.ajustar');

        // Reserva y liberación los genera el sistema (checkout y cancelaciones), no se cargan a mano
        $ajuste = ($this->form['tipo'] ?? null) === 'ajuste';
        $this->validate([
            'form.producto_id' => 'required|exists:products,id',
            'form.tipo' => 'required|in:entrada,salida,ajuste',
            'form.cantidad' => ['required', 'integer', $ajuste ? 'min:0' : 'min:1'],
            'form.referencia' => 'nullable|string|max:100',
            'form.notas' => $ajuste ? 'required|string|max:500' : 'nullable|string|max:500',
        ], [
            'form.notas.required' => 'Indica el motivo del ajuste (por ejemplo, conteo físico del día).',
        ]);

        $product = Product::find($this->form['producto_id']);
        if (!$product) {
            $this->addError('form.producto_id', 'Producto inválido');
            return;
        }

        $cantidad = (int) $this->form['cantidad'];
        if ($this->form['tipo'] === 'salida' && $cantidad > $product->stock) {
            $this->addError('form.cantidad', "Solo hay {$product->stock} unidades en stock.");
            return;
        }

        if ($ajuste) {
            // Ajuste = conteo físico: se escribe el stock contado y se registra la diferencia (positiva o negativa)
            if ($cantidad === (int) $product->stock) {
                $this->addError('form.cantidad', 'El stock contado es igual al registrado: no hay nada que ajustar.');
                return;
            }
            $service->ajustarStock($product, $cantidad, $this->form['notas'], Auth::user());
        } else {
            $service->registrarMovimiento($product, $this->form['tipo'], $cantidad, $this->form['referencia'] ?? null, $this->form['notas'] ?? null, Auth::user());
        }

        $this->showModal = false;
        $this->resetPage();
        $this->dispatch('inventory-saved');
    }
}

