<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Services\ServicioPedidos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

    public function proximoEstadoLabel(Order $order, ?ServicioPedidos $service = null): ?string
    {
        $service = $service ?? app(ServicioPedidos::class);
        $flow = $service->obtenerFlujoParaPedido($order);
        $pos = array_search($order->estado, $flow, true);
        if ($pos === false || $pos >= count($flow) - 1) {
            return null;
        }
        $next = $flow[$pos + 1];
        $estados = $service->obtenerFlujoEstados();
        return $estados[$next] ?? ucfirst(str_replace('_', ' ', $next));
    }

    public function advance(Order $order, ServicioPedidos $service)
    {
        $service = $service ?? app(ServicioPedidos::class);
        $flow = $service->obtenerFlujoParaPedido($order);
        $pos = array_search($order->estado, $flow, true);
        $next = ($pos !== false && isset($flow[$pos + 1])) ? $flow[$pos + 1] : null;

        // Si la transición aprueba o confirma un pago, exigir permiso específico pagos.aprobar
        if ($next && in_array($next, ['pago_confirmado', 'pago_confirmado_retirar'])) {
            Gate::authorize('pagos.aprobar');
        } else {
            Gate::authorize('pedidos.ver');
        }

        $updated = $service->avanzarEstado($order, Auth::user());
        if ($this->selectedOrder && $this->selectedOrder->id === $order->id) {
            $this->selectedOrder = $updated->load('items.product', 'user', 'payment');
        }
        $this->dispatch('order-updated');
    }

    public function cancel(Order $order, $reason, ServicioPedidos $service)
    {
        Gate::authorize('pedidos.anular');

        $updated = $service->cancel($order, Auth::user(), $reason);
        if ($this->selectedOrder && $this->selectedOrder->id === $order->id) {
            $this->selectedOrder = $updated->load('items.product', 'user', 'payment');
        }
        $this->dispatch('order-updated');
    }
}

