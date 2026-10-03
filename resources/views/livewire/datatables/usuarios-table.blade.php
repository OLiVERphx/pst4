<div>
    @if(session()->has('success'))
        <div class="mb-4 bg-[#059669]/10 border border-[#059669]/30 rounded-lg px-4 py-3 text-sm text-[#059669] flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @error('general')
        <div class="mb-4 bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-sm text-red-400">
            {{ $message }}
        </div>
    @enderror

    <div class="p-3 border-b border-[#475569] flex flex-wrap items-center justify-between gap-3 bg-[#334155] rounded-t-xl">
        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#A0AEC0]">🔍</span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre, email o cédula..." class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm w-72 pl-10 text-[#F1F5F9] placeholder-[#A0AEC0] focus:border-[#1D4ED8] focus:outline-none" />
            </div>

            <select wire:model.live="roleFilter" class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9] focus:outline-none">
                <option value="">Todos los roles</option>
                @foreach($rolesDisponibles as $r)
                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9] focus:outline-none">
                <option value="">Todos los estados</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <div>
            <button wire:click="openCreate" class="bg-[#1D4ED8] hover:bg-[#1e40af] text-white text-sm font-semibold px-4 py-2 rounded-lg transition flex items-center gap-2 shadow-sm">
                <span>➕</span>
                <span>Nuevo Usuario</span>
            </button>
        </div>
    </div>

    <div class="bg-[#334155] border border-t-0 border-[#475569] rounded-b-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-[#475569]">
                    <tr>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Usuario</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Rol</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Cédula / Teléfono</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Estado</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Último Acceso</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#475569]">
                    @forelse($usuarios as $user)
                        @php
                            $roleName = $user->roles->first()?->name ?? 'cliente';
                            $roleBadgeColor = match($roleName) {
                                'superadmin' => 'bg-[#7C3AED]/20 text-[#A78BFA] border-[#7C3AED]/30',
                                'admin' => 'bg-[#1D4ED8]/20 text-[#60A5FA] border-[#1D4ED8]/30',
                                'vendedor' => 'bg-[#0D9488]/20 text-[#2DD4BF] border-[#0D9488]/30',
                                default => 'bg-[#64748B]/20 text-[#94A3B8] border-[#64748B]/30',
                            };
                        @endphp
                        <tr class="hover:bg-[#475569]/30 transition">
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#475569] to-[#334155] border border-[#475569] flex items-center justify-center text-sm font-bold text-[#F1F5F9]">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-[#F1F5F9]">{{ $user->name }} {{ $user->apellido }}</div>
                                        <div class="text-xs text-[#A0AEC0]">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $roleBadgeColor }}">
                                    {{ ucfirst($roleName) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#F1F5F9]">
                                <div>{{ $user->cedula ?: '—' }}</div>
                                <div class="text-xs text-[#A0AEC0]">{{ $user->telefono ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->activo)
                                    <span class="bg-[#059669]/15 border border-[#059669]/30 text-[#10B981] px-2.5 py-1 rounded-full text-xs font-semibold">
                                        Activo
                                    </span>
                                @else
                                    <span class="bg-red-500/15 border border-red-500/30 text-red-400 px-2.5 py-1 rounded-full text-xs font-semibold">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[#A0AEC0]">
                                {{ $user->ultimo_acceso ? $user->ultimo_acceso->format('d/m/Y H:i') : 'Nunca' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openEdit({{ $user->id }})" class="bg-[#475569] hover:bg-[#64748B] text-[#F1F5F9] text-xs font-semibold px-3 py-1.5 rounded-lg transition" title="Editar usuario">
                                        ✏️ Editar
                                    </button>
                                    @if(auth()->id() !== $user->id)
                                        <button wire:click="toggleActivo({{ $user->id }})" class="{{ $user->activo ? 'text-red-400 hover:bg-red-500/10 border-red-500/30' : 'text-[#10B981] hover:bg-[#059669]/10 border-[#059669]/30' }} border text-xs font-semibold px-3 py-1.5 rounded-lg transition" title="{{ $user->activo ? 'Desactivar acceso' : 'Activar acceso' }}">
                                            {{ $user->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-[#A0AEC0] text-sm">
                                No se encontraron usuarios con los filtros especificados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-[#475569]">
            {{ $usuarios->links() }}
        </div>
    </div>

    <!-- Modal Crear / Editar Usuario -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-[#334155] border border-[#475569] rounded-xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-4 border-b border-[#475569] flex items-center justify-between">
                    <h3 class="text-base font-bold text-[#F1F5F9]">
                        {{ $editingUserId ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-[#A0AEC0] hover:text-[#F1F5F9] text-lg font-bold">✕</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Nombre *</label>
                            <input type="text" wire:model="form.name" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                            @error('form.name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Apellido</label>
                            <input type="text" wire:model="form.apellido" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                            @error('form.apellido') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Correo Electrónico *</label>
                        <input type="email" wire:model="form.email" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                        @error('form.email') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Cédula</label>
                            <input type="text" wire:model="form.cedula" placeholder="Ej: V-12345678" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                            @error('form.cedula') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Teléfono</label>
                            <input type="text" wire:model="form.telefono" placeholder="Ej: 0414-1234567" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                            @error('form.telefono') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Rol Asignado *</label>
                            <select wire:model="form.rol" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none">
                                <option value="superadmin">Superadmin (Control Total)</option>
                                <option value="admin">Admin (Dueño / Operación)</option>
                                <option value="vendedor">Vendedor (Tienda / Ventas)</option>
                                <option value="cliente">Cliente (Tienda Web)</option>
                            </select>
                            @error('form.rol') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">Estado de Acceso</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="checkbox" id="user_activo" wire:model="form.activo" class="rounded bg-[#475569] border-[#475569] text-[#1D4ED8] focus:ring-0 w-4 h-4 cursor-pointer" />
                                <label for="user_activo" class="text-sm text-[#F1F5F9] cursor-pointer">Usuario Activo</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#A0AEC0] uppercase tracking-wider mb-1">
                            {{ $editingUserId ? 'Nueva Contraseña (dejar en blanco para conservar)' : 'Contraseña *' }}
                        </label>
                        <input type="password" wire:model="form.password" placeholder="Mínimo 6 caracteres" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] focus:outline-none" />
                        @error('form.password') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-4 bg-[#1E293B]/50 border-t border-[#475569] flex justify-end gap-3">
                    <button wire:click="$set('showModal', false)" class="bg-[#475569] hover:bg-[#64748B] text-[#F1F5F9] text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Cancelar
                    </button>
                    <button wire:click="save" class="bg-[#1D4ED8] hover:bg-[#1e40af] text-white text-sm font-semibold px-5 py-2 rounded-lg transition shadow-sm">
                        {{ $editingUserId ? 'Guardar Cambios' : 'Crear Usuario' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
