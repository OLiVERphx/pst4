<div>
    <!-- Filtros y barra superior -->
    <div class="p-4 bg-[#131720] border border-[#2A3047] rounded-xl mb-4 flex flex-wrap items-center gap-3">
        <!-- Búsqueda libre -->
        <div class="relative flex-1 min-w-[200px]">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
            <input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                placeholder="Buscar por acción, entidad, IP..." 
                class="w-full bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-2 pl-9 text-sm text-[#F1F5F9] placeholder-[#94A3B8] focus:outline-none focus:border-[#1D4ED8]"
            />
        </div>

        <!-- Filtro por Usuario -->
        <div class="min-w-[180px]">
            <select wire:model.live="usuarioFilter" class="w-full bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:outline-none focus:border-[#1D4ED8]">
                <option value="">Todos los usuarios</option>
                <option value="sistema">Sistema / Invitado</option>
                @foreach($usuarios as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} {{ $user->apellido }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- Filtro por Acción -->
        <div class="min-w-[170px]">
            <select wire:model.live="accionFilter" class="w-full bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] focus:outline-none focus:border-[#1D4ED8]">
                <option value="">Todas las acciones</option>
                @foreach($acciones as $accion)
                    <option value="{{ $accion }}">{{ $accion }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filtro Rango de Fechas -->
        <div class="flex items-center gap-2">
            <input 
                wire:model.live="dateFrom" 
                type="date" 
                class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9] focus:outline-none focus:border-[#1D4ED8]" 
                title="Fecha inicio"
            />
            <span class="text-[#94A3B8] text-xs">a</span>
            <input 
                wire:model.live="dateTo" 
                type="date" 
                class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9] focus:outline-none focus:border-[#1D4ED8]" 
                title="Fecha fin"
            />
        </div>

        <!-- Limpiar filtros -->
        @if($search || $usuarioFilter !== '' || $accionFilter || $dateFrom || $dateTo)
            <button 
                wire:click="limpiarFiltros" 
                class="px-3 py-2 bg-[#2A3047] hover:bg-[#384457] text-[#F1F5F9] text-xs font-medium rounded-lg transition"
                title="Restablecer filtros"
            >
                ✕ Limpiar
            </button>
        @endif
    </div>

    <!-- Tabla de Registros -->
    <div class="bg-[#131720] border border-[#2A3047] rounded-xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#2A3047]">
                <thead class="bg-[#1C2130] text-xs font-semibold uppercase tracking-wider text-[#94A3B8]">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha / Hora</th>
                        <th class="px-4 py-3 text-left">Acción</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Entidad Afectada</th>
                        <th class="px-4 py-3 text-left">IP Origen</th>
                        <th class="px-4 py-3 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2A3047] text-sm">
                    @forelse($registros as $item)
                        @php
                            // Definición visual de badges según tipo de acción
                            $badgeClass = match(true) {
                                str_contains($item->accion, 'aprobado') || str_contains($item->accion, 'exitoso') => 'bg-[#059669]/10 text-[#059669] border border-[#059669]/30',
                                str_contains($item->accion, 'rechazado') || str_contains($item->accion, 'fallido') => 'bg-red-500/10 text-red-400 border border-red-500/30',
                                str_contains($item->accion, 'cancelado') => 'bg-[#EA580C]/10 text-[#FB923C] border border-[#EA580C]/30',
                                str_contains($item->accion, 'inventario') => 'bg-[#7C3AED]/10 text-[#C084FC] border border-[#7C3AED]/30',
                                str_contains($item->accion, 'pedido') => 'bg-[#1D4ED8]/10 text-[#60A5FA] border border-[#1D4ED8]/30',
                                default => 'bg-[#94A3B8]/10 text-[#CBD5E1] border border-[#94A3B8]/30',
                            };

                            // Nombre corto para la entidad
                            $nombreEntidad = $item->entidad_tipo ? class_basename($item->entidad_tipo) : '—';
                        @endphp
                        <tr class="hover:bg-[#1C2130]/80 transition">
                            <!-- Fecha -->
                            <td class="px-4 py-3 text-xs text-[#94A3B8] whitespace-nowrap">
                                <div class="font-medium text-[#F1F5F9]">{{ $item->created_at ? $item->created_at->format('d/m/Y') : '—' }}</div>
                                <div class="text-[11px] text-[#64748B]">{{ $item->created_at ? $item->created_at->format('H:i:s') : '—' }}</div>
                            </td>

                            <!-- Acción -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                    {{ $item->accion }}
                                </span>
                            </td>

                            <!-- Usuario -->
                            <td class="px-4 py-3">
                                @if($item->usuario)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-[#1D4ED8] to-[#0D9488] flex items-center justify-center text-[10px] font-bold text-white uppercase">
                                            {{ substr($item->usuario->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-[#F1F5F9] text-xs leading-tight">{{ $item->usuario->name }} {{ $item->usuario->apellido }}</div>
                                            <div class="text-[11px] text-[#94A3B8]">{{ $item->usuario->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-[#64748B] italic">⚙️ Sistema / Invitado</span>
                                @endif
                            </td>

                            <!-- Entidad Afectada -->
                            <td class="px-4 py-3 text-xs text-[#F1F5F9] whitespace-nowrap">
                                @if($item->entidad_tipo)
                                    <div class="font-mono text-xs text-[#38BDF8] bg-[#38BDF8]/10 px-2 py-0.5 rounded inline-block">
                                        {{ $nombreEntidad }} #{{ $item->entidad_id ?? 'N/A' }}
                                    </div>
                                @else
                                    <span class="text-[#64748B]">—</span>
                                @endif
                            </td>

                            <!-- IP de Origen -->
                            <td class="px-4 py-3 text-xs text-[#94A3B8] font-mono whitespace-nowrap">
                                {{ $item->ip_origen ?? '—' }}
                            </td>

                            <!-- Botón Detalle -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button 
                                    wire:click="verDetalle({{ $item->id }})" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#1C2130] hover:bg-[#2A3047] text-[#38BDF8] hover:text-white border border-[#2A3047] rounded-lg text-xs font-medium transition"
                                >
                                    <span>👁️</span>
                                    <span>Detalle</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-[#94A3B8]">
                                <div class="text-3xl mb-2">📜</div>
                                <div class="font-medium text-[#F1F5F9]">No se encontraron registros de auditoría</div>
                                <div class="text-xs text-[#64748B] mt-1">Prueba cambiando los filtros de búsqueda o fecha.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($registros->hasPages())
            <div class="p-4 border-t border-[#2A3047] bg-[#131720]">
                {{ $registros->links() }}
            </div>
        @endif
    </div>

    <!-- Modal de Detalle de Auditoría -->
    @if($showDetailModal && $selectedAudit)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-[#131720] border border-[#384457] rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
                <!-- Encabezado del Modal -->
                <div class="p-5 border-b border-[#2A3047] flex items-center justify-between bg-[#1C2130]">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🛡️</span>
                        <div>
                            <h3 class="text-base font-bold text-[#F1F5F9]">Detalle de Registro de Auditoría #{{ $selectedAudit->id }}</h3>
                            <p class="text-xs text-[#94A3B8]">Acción: <strong class="text-white">{{ $selectedAudit->accion }}</strong> • Registrado el {{ $selectedAudit->created_at ? $selectedAudit->created_at->format('d/m/Y H:i:s') : '—' }}</p>
                        </div>
                    </div>
                    <button wire:click="cerrarModal" class="text-[#94A3B8] hover:text-[#F1F5F9] p-1.5 rounded-lg hover:bg-[#2A3047] transition">
                        ✕
                    </button>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="p-6 overflow-y-auto space-y-5 text-sm">
                    <!-- Metadatos de Contexto -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-4 bg-[#1C2130] border border-[#2A3047] rounded-xl text-xs">
                        <div>
                            <span class="text-[#94A3B8] block font-semibold mb-0.5">Usuario</span>
                            <span class="text-[#F1F5F9]">
                                @if($selectedAudit->usuario)
                                    {{ $selectedAudit->usuario->name }} {{ $selectedAudit->usuario->apellido }} (ID: {{ $selectedAudit->usuario->id }})
                                @else
                                    Sistema / Invitado
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[#94A3B8] block font-semibold mb-0.5">Dirección IP</span>
                            <span class="text-[#F1F5F9] font-mono">{{ $selectedAudit->ip_origen ?? 'No disponible' }}</span>
                        </div>
                        <div>
                            <span class="text-[#94A3B8] block font-semibold mb-0.5">Entidad Afectada</span>
                            <span class="text-[#38BDF8] font-mono">
                                {{ $selectedAudit->entidad_tipo ? class_basename($selectedAudit->entidad_tipo) : 'N/A' }} 
                                @if($selectedAudit->entidad_id) (ID: {{ $selectedAudit->entidad_id }}) @endif
                            </span>
                        </div>
                        <div class="col-span-full">
                            <span class="text-[#94A3B8] block font-semibold mb-0.5">User Agent</span>
                            <span class="text-[#CBD5E1] font-mono text-[11px] break-all">{{ $selectedAudit->user_agent ?? 'No disponible' }}</span>
                        </div>
                    </div>

                    <!-- Comparativa: Antes y Después -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Valores Antes -->
                        <div class="bg-[#1C2130] border border-[#2A3047] rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-[#2A3047]">
                                <span class="text-amber-400">⏪</span>
                                <h4 class="font-semibold text-xs text-[#F1F5F9] uppercase tracking-wider">Valores Anteriores</h4>
                            </div>
                            @if(!empty($selectedAudit->valores_antes) && is_array($selectedAudit->valores_antes))
                                <pre class="bg-[#0B0E17] p-3 rounded-lg text-xs font-mono text-[#F1F5F9] overflow-x-auto border border-[#2A3047]">{{ json_encode($selectedAudit->valores_antes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <p class="text-xs text-[#64748B] italic p-3 bg-[#0B0E17] rounded-lg border border-[#2A3047]">Sin valores previos registrados.</p>
                            @endif
                        </div>

                        <!-- Valores Después -->
                        <div class="bg-[#1C2130] border border-[#2A3047] rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-[#2A3047]">
                                <span class="text-emerald-400">⏩</span>
                                <h4 class="font-semibold text-xs text-[#F1F5F9] uppercase tracking-wider">Valores Posteriores / Registrados</h4>
                            </div>
                            @if(!empty($selectedAudit->valores_despues) && is_array($selectedAudit->valores_despues))
                                <pre class="bg-[#0B0E17] p-3 rounded-lg text-xs font-mono text-emerald-300 overflow-x-auto border border-[#2A3047]">{{ json_encode($selectedAudit->valores_despues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <p class="text-xs text-[#64748B] italic p-3 bg-[#0B0E17] rounded-lg border border-[#2A3047]">Sin valores posteriores registrados.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Pie del Modal -->
                <div class="p-4 border-t border-[#2A3047] bg-[#1C2130] text-right">
                    <button 
                        wire:click="cerrarModal" 
                        class="px-4 py-2 bg-[#2A3047] hover:bg-[#384457] text-[#F1F5F9] text-xs font-semibold rounded-lg transition"
                    >
                        Cerrar Detalle
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
