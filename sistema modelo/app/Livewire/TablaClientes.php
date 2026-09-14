<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TablaClientes extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $selectedClient = null;
    public $mostrarDetalle = false;

    public function getClientsProperty()
    {
        $query = User::clientes()->with('orders')->orderBy('name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('cedula', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('activo', $this->statusFilter);
        }

        return $query;
    }

    public function toggleActivo($id)
    {
        $user = User::findOrFail($id);
        $user->activo = !$user->activo;
        $user->save();
        session()->flash('success', 'Client status updated.');
    }

    public function abrirDetalle($id)
    {
        $this->selectedClient = User::with('orders')->findOrFail($id);
        $this->mostrarDetalle = true;
    }

    public function render()
    {
        $clients = $this->clients->paginate(10);
        return view('livewire.tabla-clientes', compact('clients'));
    }
}
