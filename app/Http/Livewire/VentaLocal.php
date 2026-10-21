<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\User;
use App\Services\ServicioPedidos;
use Illuminate\Support\Str;

/**
 * Componente Livewire para registrar ventas en el local (mostrador).
 * Requiere permiso 'pedidos.crear'.
 */
class VentaLocal extends Component
{
    public $query = '';
    public $results = [];
    public $items = []; // cada item: ['producto_id','codigo','nombre','cantidad','precio','tipo']
    public $cliente_cedula = null;
    public $cliente_telefono = null;
    public $notas = null;

    public function mount()
    {
        if (!auth()->check() || !auth()->user()->can('pedidos.crear')) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.venta-local');
    }

    public function updatedQuery()
    {
        $this->searchProducts();
    }

    public function searchProducts()
    {
        $q = trim($this->query);
        if ($q === '') {
            $this->results = [];
            return;
        }

        // Solo productos activos: los inactivos no se venden
        $this->results = Product::where('activo', true)
            ->where(fn ($sub) => $sub->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"))
            ->limit(20)
            ->get(['id','nombre','codigo','precio_detal','precio_mayor','stock'])
            ->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo' => $p->codigo,
                'precio_detal' => $p->precio_detal,
                'precio_mayor' => $p->precio_mayor,
                'stock' => $p->stock,
            ])->toArray();
    }

    public function addItem(int $productoId, string $tipo = 'detal', int $cantidad = 1)
    {
        $producto = Product::find($productoId);
        if (!$producto) {
            $this->addError('items', 'Producto no encontrado');
            return;
        }

        // Al mayor se agrega directamente con la cantidad mínima del producto
        if ($tipo === 'mayor') {
            $cantidad = max($cantidad, $this->minimoMayor($producto));
        }

        $precio = $tipo === 'mayor' ? $producto->precio_mayor : $producto->precio_detal;

        $this->items[] = [
            'producto_id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'cantidad' => (int)$cantidad,
            'precio' => $precio,
            'tipo' => $tipo,
        ];

        $this->query = '';
        $this->results = [];
    }

    public function removeItem(int $index)
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
        }
    }

    public function updateQuantity(int $index, int $cantidad)
    {
        if (!isset($this->items[$index])) return;
        $minimo = 1;
        if ($this->items[$index]['tipo'] === 'mayor' && ($producto = Product::find($this->items[$index]['producto_id']))) {
            $minimo = $this->minimoMayor($producto);
            if ($cantidad < $minimo) {
                $this->addError('items', "Al mayor, {$producto->nombre} se vende desde {$minimo} unidades.");
            }
        }
        $this->items[$index]['cantidad'] = max($minimo, (int)$cantidad);
    }

    private function minimoMayor(Product $producto): int
    {
        return (int) ($producto->min_cantidad_mayor ?: (\App\Models\Configuracion::instance()?->minimo_unidades_mayor ?? 10));
    }

    /**
     * Confirma la venta: delega en ServicioPedidos para crear el pedido y ajustar stock.
     */
    public function confirmSale()
    {
        if (empty($this->items)) {
            $this->addError('items', 'No hay items en la venta');
            return;
        }

        // Intentar identificar cliente por cédula o teléfono (si existe columna en usuarios)
        $clienteId = null;
        if ($this->cliente_cedula) {
            $u = User::porCedula($this->cliente_cedula)->first();
            $clienteId = $u?->id;
        }
        if (!$clienteId && $this->cliente_telefono) {
            $u = User::porTelefono($this->cliente_telefono)->first();
            $clienteId = $u?->id;
        }

        $lineas = array_map(function($it) {
            return ['producto_id' => $it['producto_id'], 'cantidad' => $it['cantidad'], 'precio' => $it['precio'], 'tipo' => $it['tipo']];
        }, $this->items);

        try {
            /** @var ServicioPedidos $servicioPedidos */
            $servicioPedidos = app(ServicioPedidos::class);
            $order = $servicioPedidos->crearPedidoDesdeLineas($lineas, $clienteId, auth()->id(), 'local', $this->notas);

            session()->flash('success', 'Venta registrada con número: ' . $order->numero_pedido);

            // limpiar estado del componente para una nueva venta
            $this->items = [];
            $this->cliente_cedula = null;
            $this->cliente_telefono = null;
            $this->notas = null;

            $this->dispatch('ventaLocalRegistrada', orderId: $order->id);
        } catch (\Exception $e) {
            $this->addError('server', 'Error al registrar la venta: ' . $e->getMessage());
        }
    }
}
