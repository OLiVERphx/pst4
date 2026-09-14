<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Services\ServicioAuditoria;

class ClientsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $selectedClient = null;
    public $showDetail = false;

    // Datos del detalle
    public $detailOrders = [];
    public $detailTotal = 0.0;
    public $detailRejectedPayments = [];

    // Edición
    public $editMode = false;
    public $editData = [];

    protected $listeners = ['client-updated' => '$refresh'];

    public function getClientsProperty()
    {
        $roleId = Role::where('nombre', 'cliente')->value('id');
        $query = User::query();
        if ($roleId) {
            $query->where('rol_id', $roleId);
        }

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($s) use ($q) {
                $s->where('name', 'like', "%{$q}%")
                  ->orWhere('cedula', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('activo', (bool)$this->statusFilter);
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function render()
    {
        $clients = $this->clients;
        $ids = $clients->pluck('id')->toArray();
        $orders = Order::whereIn('usuario_id', $ids)
            ->selectRaw('usuario_id, COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
            ->groupBy('usuario_id')
            ->get()
            ->keyBy('usuario_id');

        return view('livewire.datatables.clients-table', [
            'clients' => $clients,
            'ordersStats' => $orders,
        ]);
    }

    public function toggleActivo($clientId)
    {
        $u = User::find($clientId);
        if (!$u) return;
        $u->activo = !$u->activo;
        $u->save();
        $this->dispatch('client-updated');
    }

    /**
     * Abrir panel de detalle: carga pedidos, total histórico y pagos rechazados.
     */
    public function openDetail($clientId)
    {
        $client = User::find($clientId);
        if (!$client) return;

        $this->selectedClient = $client;
        $this->showDetail = true;

        // Cargar historial de pedidos (online y local)
        $orders = Order::where('usuario_id', $client->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $this->detailOrders = $orders;

        // Total histórico comprado (suma de totales, excluyendo pedidos cancelados)
        $this->detailTotal = Order::where('usuario_id', $client->id)
            ->where('estado', '!=', 'cancelado')
            ->sum('total');

        // Pagos rechazados asociados a los pedidos de este cliente
        $this->detailRejectedPayments = Payment::whereHas('order', function ($q) use ($client) {
            $q->where('usuario_id', $client->id);
        })->where('estado', 'rechazado')->orderByDesc('created_at')->get();
    }

    public function closeDetail()
    {
        $this->selectedClient = null;
        $this->showDetail = false;
        $this->detailOrders = [];
        $this->detailTotal = 0;
        $this->detailRejectedPayments = [];
        $this->editMode = false;
        $this->editData = [];
    }

    /**
     * Iniciar edición de datos de contacto (requiere permiso 'clientes.editar').
     */
    public function startEdit($clientId)
    {
        $user = Auth::user();
        if (! $user || ! $user->can('clientes.editar')) {
            abort(403, 'No autorizado para editar clientes.');
        }

        $client = User::find($clientId);
        if (! $client) return;

        $this->selectedClient = $client;
        $this->editMode = true;
        $this->editData = [
            'name' => $client->name,
            'apellido' => $client->apellido,
            'email' => $client->email,
            'telefono' => $client->telefono,
            'direccion' => $client->direccion,
            'ciudad' => $client->ciudad,
        ];
    }

    /**
     * Guardar datos editados de contacto.
     */
    public function saveEdit()
    {
        $user = Auth::user();
        if (! $user || ! $user->can('clientes.editar')) {
            abort(403, 'No autorizado para editar clientes.');
        }

        $this->validate([
            'editData.name' => 'required|string|max:100',
            'editData.apellido' => 'required|string|max:100',
            'editData.email' => 'required|email|max:150',
            'editData.telefono' => 'nullable|string|max:30',
            'editData.direccion' => 'nullable|string|max:255',
            'editData.ciudad' => 'nullable|string|max:80',
        ]);

        $client = User::find($this->selectedClient->id);
        if (! $client) return;

        DB::transaction(function () use ($client, $user) {
            $antes = $client->only(['name','apellido','email','telefono','direccion','ciudad']);

            $client->fill([
                'name' => $this->editData['name'],
                'apellido' => $this->editData['apellido'],
                'email' => $this->editData['email'],
                'telefono' => $this->editData['telefono'],
                'direccion' => $this->editData['direccion'],
                'ciudad' => $this->editData['ciudad'],
            ]);

            $client->save();

            $despues = $client->only(['name','apellido','email','telefono','direccion','ciudad']);

            ServicioAuditoria::registrar('cliente.contacto_editado', $client, $antes, $despues, $user->id);
        });

        $this->editMode = false;
        $this->dispatch('client-updated');
        $this->openDetail($this->selectedClient->id);
    }

    /**
     * Bloquear / Desbloquear cliente (requiere permiso 'clientes.bloquear').
     * Crea el permiso si no existe.
     */
    public function toggleBlock($clientId)
    {
        $actor = Auth::user();
        // Asegurar existencia del permiso
        Permission::firstOrCreate(['name' => 'clientes.bloquear']);

        if (! $actor || ! $actor->can('clientes.bloquear')) {
            abort(403, 'No autorizado para bloquear clientes.');
        }

        $client = User::find($clientId);
        if (! $client) return;

        DB::transaction(function () use ($client, $actor) {
            $antes = $client->only(['bloqueado','activo']);
            $client->bloqueado = ! (bool) $client->bloqueado;
            $client->save();
            $despues = $client->only(['bloqueado','activo']);

            $accion = $client->bloqueado ? 'cliente.bloqueado' : 'cliente.desbloqueado';
            ServicioAuditoria::registrar($accion, $client, $antes, $despues, $actor->id);
        });

        $this->dispatch('client-updated');

        // Refrescar detalle si estaba abierto
        if ($this->showDetail && $this->selectedClient && $this->selectedClient->id === $client->id) {
            $this->openDetail($client->id);
        }
    }
}
