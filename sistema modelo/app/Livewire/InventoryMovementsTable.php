<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ServicioInventario;
use Illuminate\Support\Facades\Auth;

/**
 * Livewire component que muestra y crea movimientos de inventario.
 */
class InventoryMovementsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    public $dateFrom = null;
    public $dateTo = null;

    public $mostrarModal = false;
    public $form = [
        'producto_id' => null,
        'tipo' => 'entrada',
        'cantidad' => 1,
        'referencia' => null,
        'notas' => null,
    ];

    protected $rules = [
        'form.producto_id' => 'required|exists:products,id',
        'form.tipo' => 'required|in:entrada,salida,ajuste,reserva,liberacion',
        'form.cantidad' => 'required|integer|min:1',
        'form.referencia' => 'nullable|string|max:100',
        'form.notas' => 'nullable|string',
    ];

    public function getMovementsProperty()
    {
        $query = InventoryMovement::with('product', 'user')->orderByDesc('created_at');

        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('codigo', 'like', '%' . $this->search . '%');
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

        return $query;
    }

    public function abrirCrear()
    {
        $this->reset('form');
        $this->form = ['producto_id' => null, 'tipo' => 'entrada', 'cantidad' => 1, 'referencia' => null, 'notas' => null];
        $this->mostrarModal = true;
    }

    public function save()
    {
        $this->validate();

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
        session()->flash('success', 'Movimiento registrado.');
        $this->emit('movement-saved');
        $this->resetPage();
    }

    public function render()
    {
        $movements = $this->getMovementsProperty()->paginate(10);
        $products = Product::orderBy('nombre')->get();

        return view('livewire.inventory-movements-table', compact('movements', 'products'));
    }
}
