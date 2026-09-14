<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BitacoraAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Componente Livewire para la tabla interactiva de Bitácora de Auditoría.
 * Permite filtrar por usuario, tipo de acción, rango de fechas y ver el detalle antes/después.
 */
class BitacoraAuditoriaTable extends Component
{
    use WithPagination;

    public $search = '';
    public $usuarioFilter = '';
    public $accionFilter = '';
    public $dateFrom = null;
    public $dateTo = null;

    public $showDetailModal = false;
    public $selectedAudit = null;

    protected $listeners = ['audit-refresh' => '$refresh'];

    public function mount(): void
    {
        if (!Auth::user() || !Auth::user()->can('auditoria.ver')) {
            abort(403, 'No tienes permiso para consultar la bitácora de auditoría.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUsuarioFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAccionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function verDetalle(int $id): void
    {
        if (!Auth::user() || !Auth::user()->can('auditoria.ver')) {
            abort(403);
        }

        $audit = BitacoraAuditoria::with('usuario')->find($id);
        if ($audit) {
            $this->selectedAudit = $audit;
            $this->showDetailModal = true;
        }
    }

    public function cerrarModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedAudit = null;
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'usuarioFilter', 'accionFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        if (!Auth::user() || !Auth::user()->can('auditoria.ver')) {
            abort(403, 'No tienes permiso para consultar la bitácora de auditoría.');
        }

        $query = BitacoraAuditoria::with('usuario');

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('accion', 'like', "%{$q}%")
                    ->orWhere('entidad_tipo', 'like', "%{$q}%")
                    ->orWhere('ip_origen', 'like', "%{$q}%")
                    ->orWhere('user_agent', 'like', "%{$q}%")
                    ->orWhereHas('usuario', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($this->usuarioFilter !== '') {
            if ($this->usuarioFilter === 'sistema') {
                $query->whereNull('usuario_id');
            } else {
                $query->where('usuario_id', $this->usuarioFilter);
            }
        }

        if ($this->accionFilter) {
            $query->where('accion', $this->accionFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $registros = $query->orderByDesc('created_at')->paginate(15);

        $usuarios = User::orderBy('name')->get(['id', 'name', 'apellido', 'email']);
        $acciones = BitacoraAuditoria::select('accion')->distinct()->orderBy('accion')->pluck('accion');

        return view('livewire.datatables.bitacora-auditoria-table', [
            'registros' => $registros,
            'usuarios' => $usuarios,
            'acciones' => $acciones,
        ]);
    }
}
