<div>
    @if(session()->has('success'))
        <div class="mb-4 bg-[#059669]/10 border border-[#059669]/30 rounded-lg px-4 py-3 text-sm text-[#059669] flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-sm text-red-400 flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="p-4 border-b border-[#475569] flex flex-wrap items-center justify-between gap-3 bg-[#334155] rounded-t-xl">
        <div>
            <span class="text-xs text-[#A0AEC0] uppercase tracking-wider font-semibold">Almacenamiento Local Seguro</span>
            <div class="text-sm font-semibold text-[#F1F5F9]">storage/app/backups</div>
        </div>

        <button wire:click="generar" wire:loading.attr="disabled" class="bg-[#1D4ED8] hover:bg-[#1e40af] disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 rounded-lg transition flex items-center gap-2 shadow-sm">
            <span wire:loading.remove>💾 Generar Respaldo Manual</span>
            <span wire:loading>⏳ Generando volcado SQL...</span>
        </button>
    </div>

    <div class="bg-[#334155] border border-t-0 border-[#475569] rounded-b-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-[#475569]">
                    <tr>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Archivo</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Formato</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Tamaño</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Fecha y Hora</th>
                        <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#475569]">
                    @forelse($respaldos as $r)
                        <tr class="hover:bg-[#475569]/30 transition">
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-[#475569] border border-[#475569] flex items-center justify-center text-base">
                                        🗄️
                                    </div>
                                    <div>
                                        <div class="font-semibold text-[#F1F5F9]">{{ $r['nombre'] }}</div>
                                        <div class="text-xs text-[#A0AEC0]">Base de datos completa (Estructura y Datos)</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="bg-[#1D4ED8]/15 border border-[#1D4ED8]/30 text-[#60A5FA] px-2.5 py-1 rounded-full text-xs font-bold uppercase">
                                    SQL
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-[#F1F5F9]">
                                {{ $r['tamano_humano'] }}
                            </td>
                            <td class="px-4 py-3 text-xs text-[#A0AEC0]">
                                {{ $r['fecha_modificacion'] }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.respaldos.descargar', ['archivo' => $r['nombre']]) }}" class="bg-[#475569] hover:bg-[#64748B] text-[#F1F5F9] text-xs font-semibold px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5" title="Descargar archivo .sql">
                                        <span>📥</span>
                                        <span>Descargar</span>
                                    </a>
                                    <button wire:click="eliminar('{{ $r['nombre'] }}')" wire:confirm="¿Estás seguro de que deseas eliminar este archivo de respaldo?" class="text-red-400 hover:bg-red-500/10 border border-red-500/30 text-xs font-semibold px-3 py-1.5 rounded-lg transition" title="Eliminar respaldo">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <div class="text-3xl mb-2">💾</div>
                                <div class="text-sm font-semibold text-[#F1F5F9]">No hay respaldos generados aún</div>
                                <div class="text-xs text-[#A0AEC0] mt-1">Haz clic en "Generar Respaldo Manual" para crear tu primer volcado de base de datos.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
