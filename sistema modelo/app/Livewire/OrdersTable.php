<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Services\ServicioPedidos;
use Illuminate\Support\Facades\Auth;

/**
 * Componente Livewire para gestión de pedidos en el panel admin.
 */
class OrdersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';

    public $selectedOrder = null;
    public $mostrarDetalle = false;

    public function getOrdersProperty()
    {
        $query = Order::with('user', 'items.product', 'payment')->orderByDesc('created_at');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('numero_pedido', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function($q2) {
                        $q2->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('estado', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('tipo', $this->typeFilter);
        }

        return $query;
    }

    public function abrirDetalle($id)
    {
        $this->selectedOrder = Order::with('user', 'items.product', 'payment')->findOrFail($id);
        $this->mostrarDetalle = true;
    }

    public function advance($id)
    {
        $order = Order::findOrFail($id);
        $service = new ServicioPedidos();
        $service->avanzarEstado($order, Auth::user());
        $this->abrirDetalle($order->id);
        session()->flash('success', 'Estado avanzado.');
    }

    public function cancel($id, $reason = '')
    {
        $order = Order::findOrFail($id);
        $service = new ServicioPedidos();
        $service->cancel($order, Auth::user(), $reason ?: 'Cancelado desde panel');
        $this->abrirDetalle($order->id);
        session()->flash('success', 'Pedido cancelado.');
    }

    public function render()
    {
        $orders = $this->getOrdersProperty()->paginate(10);
        return view('livewire.orders-table', compact('orders'));
    }
}
