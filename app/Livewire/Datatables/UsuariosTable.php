<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role as CustomRole;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\ServicioAuditoria;

/**
 * Livewire component para la gestión de usuarios administrativos y clientes.
 * Exclusivo para superadmin (permiso 'usuarios.gestionar').
 */
class UsuariosTable extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $showModal = false;
    public $editingUserId = null;

    public $form = [
        'name' => '',
        'apellido' => '',
        'email' => '',
        'cedula' => '',
        'telefono' => '',
        'rol' => 'vendedor',
        'password' => '',
        'activo' => true,
    ];

    protected $listeners = ['usuario-saved' => '$refresh'];

    public function mount()
    {
        Gate::authorize('usuarios.gestionar');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function getUsuariosProperty()
    {
        $query = User::query()->with('roles');

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('cedula', 'like', "%{$q}%");
            });
        }

        if ($this->roleFilter) {
            $role = $this->roleFilter;
            $query->whereHas('roles', function ($rq) use ($role) {
                $rq->where('name', $role);
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('activo', (bool) $this->statusFilter);
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function render()
    {
        Gate::authorize('usuarios.gestionar');

        $rolesDisponibles = SpatieRole::orderBy('name')->pluck('name')->toArray();

        return view('livewire.datatables.usuarios-table', [
            'usuarios' => $this->usuarios,
            'rolesDisponibles' => $rolesDisponibles,
        ]);
    }

    public function openCreate()
    {
        Gate::authorize('usuarios.gestionar');

        $this->resetErrorBag();
        $this->editingUserId = null;
        $this->form = [
            'name' => '',
            'apellido' => '',
            'email' => '',
            'cedula' => '',
            'telefono' => '',
            'rol' => 'vendedor',
            'password' => '',
            'activo' => true,
        ];
        $this->showModal = true;
    }

    public function openEdit($userId)
    {
        Gate::authorize('usuarios.gestionar');

        $this->resetErrorBag();
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;

        $rolName = $user->roles->first()?->name ?? 'cliente';

        $this->form = [
            'name' => $user->name,
            'apellido' => $user->apellido ?? '',
            'email' => $user->email,
            'cedula' => $user->cedula ?? '',
            'telefono' => $user->telefono ?? '',
            'rol' => $rolName,
            'password' => '',
            'activo' => (bool) $user->activo,
        ];

        $this->showModal = true;
    }

    public function save()
    {
        Gate::authorize('usuarios.gestionar');

        $rules = [
            'form.name' => 'required|string|max:100',
            'form.apellido' => 'nullable|string|max:100',
            'form.email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'form.cedula' => 'nullable|string|max:30',
            'form.telefono' => 'nullable|string|max:30',
            'form.rol' => 'required|in:superadmin,admin,vendedor,cliente',
            'form.password' => $this->editingUserId ? 'nullable|min:6' : 'required|min:6',
            'form.activo' => 'boolean',
        ];

        $this->validate($rules);

        DB::transaction(function () {
            $actorId = Auth::id();
            $roleCustomId = CustomRole::where('nombre', $this->form['rol'])->value('id');

            $userData = [
                'name' => $this->form['name'],
                'apellido' => $this->form['apellido'] ?: null,
                'email' => $this->form['email'],
                'cedula' => $this->form['cedula'] ?: null,
                'telefono' => $this->form['telefono'] ?: null,
                'activo' => (bool) $this->form['activo'],
                'rol_id' => $roleCustomId,
            ];

            if (!empty($this->form['password'])) {
                $userData['password'] = Hash::make($this->form['password']);
            }

            if ($this->editingUserId) {
                $user = User::where('id', $this->editingUserId)->lockForUpdate()->firstOrFail();
                $antes = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->roles->first()?->name,
                    'activo' => $user->activo,
                ];

                $user->update($userData);
                $user->syncRoles([$this->form['rol']]);

                $despues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $this->form['rol'],
                    'activo' => $user->activo,
                ];

                ServicioAuditoria::registrar('usuario.modificado', $user, $antes, $despues, $actorId);
                session()->flash('success', 'Usuario actualizado exitosamente.');
            } else {
                $user = User::create($userData);
                $user->syncRoles([$this->form['rol']]);

                $despues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $this->form['rol'],
                    'activo' => $user->activo,
                ];

                ServicioAuditoria::registrar('usuario.creado', $user, null, $despues, $actorId);
                session()->flash('success', 'Usuario creado exitosamente.');
            }
        });

        $this->showModal = false;
        $this->editingUserId = null;
        $this->dispatch('usuario-saved');
    }

    public function toggleActivo($userId)
    {
        Gate::authorize('usuarios.gestionar');

        $actor = Auth::user();
        if ($actor->id == $userId) {
            $this->addError('general', 'No puedes desactivar tu propia cuenta de usuario.');
            return;
        }

        $user = User::findOrFail($userId);

        // Si es el único superadmin activo, no permitir desactivar
        if ($user->hasRole('superadmin') && $user->activo) {
            $superadminsActivos = User::role('superadmin')->where('activo', true)->count();
            if ($superadminsActivos <= 1) {
                $this->addError('general', 'No puedes desactivar al único superadministrador activo del sistema.');
                return;
            }
        }

        DB::transaction(function () use ($user, $actor) {
            $antes = ['activo' => (bool)$user->activo];
            $user->activo = !(bool)$user->activo;
            $user->save();
            $despues = ['activo' => (bool)$user->activo];

            ServicioAuditoria::registrar('usuario.estado_cambiado', $user, $antes, $despues, $actor->id);
        });

        $this->dispatch('usuario-saved');
    }
}
