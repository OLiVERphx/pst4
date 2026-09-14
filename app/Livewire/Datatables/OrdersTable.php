<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Services\ServicioPedidos;
use Illuminate\Support\Facades\Auth;

/**
 * Livewire: Tabla de pedidos para el admin.
 */
class OrdersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $selectedOrder = null;
    public $showDetail = false;

    protected $listeners = ['order-updated' => '$refresh'];

    public function getOrdersProperty()
    {
        $query = Order::with(['user', 'items.product', 'payment']);

        if ($this->search) {
            $q = $this->search;
            $query->where('numero_pedido', 'like', "%{$q}%")
                ->orWhereHas('user', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%");
                });
        }

        if ($this->statusFilter) {
            $query->where('estado', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('tipo', $this->typeFilter);
        }

        return $query->orderByDesc('created_at')->paginate(10);
    }

    public function render()
    {
        return view('livewire.datatables.orders-table', [
            'orders' => $this->orders,
        ]);
    }

    public function openDetail(Order $order)
    {
        $this->selectedOrder = $order->load('items.product', 'user', 'payment');
        $this->showDetail = true;
    }

    public function advance(Order $order, ServicioPedidos $service)
    {
        $service->avanzarEstado($order, Auth::user());
        $this->dispatch('order-updated');
    }

    public function cancel(Order $order, $reason, ServicioPedidos $service)
    {
        $service->cancel($order, Auth::user(), $reason);
        $this->dispatch('order-updated');
    }
}

