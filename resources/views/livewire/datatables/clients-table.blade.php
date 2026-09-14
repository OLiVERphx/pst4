<div>
    <div class="p-3 border-b border-[#2A3047] flex items-center gap-2">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
            <input wire:model.live="search" type="text" placeholder="Buscar clientes" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm w-56 pl-10 text-[#F1F5F9] placeholder-[#94A3B8] focus:border-[#1D4ED8]" />
        </div>

        <select wire:model="statusFilter" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select>
    </div>

    <div class="bg-[#131720] border border-[#2A3047] rounded-xl overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-[#1C2130]"><tr>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Cliente</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Cédula</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Teléfono</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Ciudad</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Pedidos</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Total</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Estado</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                </tr></thead>
            <tbody class="divide-y">
                @foreach($clients as $client)
                    @php $stat = $ordersStats[$client->id] ?? null; @endphp
                    <tr class="hover:bg-[#1C2130]">
                        <td class="px-4 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-sm text-white">{{ strtoupper(substr($client->name,0,1)) }}</div>
                                <div>
                                    <div class="font-semibold text-[#F1F5F9]">{{ $client->name }}</div>
                                    <div class="text-xs text-[#94A3B8]">{{ $client->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ $client->cedula }}</td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ $client->telefono }}</td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ $client->ciudad }}</td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ $stat?->cnt ?? 0 }}</td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ number_format($stat?->total ?? 0, 2) }}</td>
                        <td class="px-4 py-2">
                            @php $badge = $client->activo ? 'bg-[#059669]/10 text-[#059669]' : 'bg-red-500/10 text-red-400'; @endphp
                            <span class="{{ $badge }} px-2 py-1 rounded-full text-xs font-semibold">{{ $client->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <button wire:click="openDetail({{ $client->id }})" class="text-[#1D4ED8] mr-2">Ver</button>
                            <button wire:click="toggleActivo({{ $client->id }})" class="text-[#EA580C]">Toggle</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">{{ $clients->links() }}</div>
    </div>

    <!-- Detail panel -->
    <div x-data wire:ignore.self x-show="$wire.showDetail" class="fixed inset-0 bg-black/65 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-[#2B3346] border border-[#384457] rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
            <div class="p-5 border-b border-[#2A3047] flex items-center justify-between">
                <h3 class="font-bold text-base text-[#F1F5F9]">Ficha de cliente</h3>
                <button wire:click.prevent="$set('showDetail', false)" class="w-8 h-8 bg-[#1C2130] border border-[#2A3047] rounded-lg text-[#94A3B8]">✕</button>
            </div>
            <div class="p-6">
                @if($selectedClient)
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-semibold mb-1 text-[#F1F5F9]">{{ $selectedClient->name }} {{ $selectedClient->apellido }}</h3>
                            <div class="text-sm text-[#94A3B8]">Email: {{ $selectedClient->email }}</div>
                            <div class="text-sm text-[#94A3B8]">Tel: {{ $selectedClient->telefono }} — Ciudad: {{ $selectedClient->ciudad }}</div>
                            <div class="text-sm text-[#94A3B8]">Dirección: {{ $selectedClient->direccion }}</div>
                        </div>
                        <div class="text-right">
                            @can('clientes.editar')
                                <button wire:click.prevent="startEdit({{ $selectedClient->id }})" class="px-3 py-1 bg-blue-600 text-white rounded-md mr-2">Editar</button>
                            @endcan

                            @php // Asegurar que el permiso exista para la comprobación visual
                                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'clientes.bloquear']);
                            @endphp

                            @can('clientes.bloquear')
                                <button wire:click.prevent="toggleBlock({{ $selectedClient->id }})" class="px-3 py-1 rounded-md {{ $selectedClient->bloqueado ? 'bg-red-600 text-white' : 'bg-gray-700 text-white' }}">
                                    {{ $selectedClient->bloqueado ? 'Desbloquear' : 'Bloquear' }}
                                </button>
                            @endcan
                        </div>
                    </div>

                    @if($editMode)
                        <div class="mb-4">
                            <label class="block text-sm text-[#94A3B8]">Nombre</label>
                            <input wire:model.defer="editData.name" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />
                            <label class="block text-sm text-[#94A3B8]">Apellido</label>
                            <input wire:model.defer="editData.apellido" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />
                            <label class="block text-sm text-[#94A3B8]">Email</label>
                            <input wire:model.defer="editData.email" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />
                            <label class="block text-sm text-[#94A3B8]">Teléfono</label>
                            <input wire:model.defer="editData.telefono" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />
                            <label class="block text-sm text-[#94A3B8]">Dirección</label>
                            <input wire:model.defer="editData.direccion" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />
                            <label class="block text-sm text-[#94A3B8]">Ciudad</label>
                            <input wire:model.defer="editData.ciudad" class="w-full px-3 py-2 rounded bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]" />

                            <div class="mt-3">
                                <button wire:click.prevent="saveEdit" class="px-3 py-1 bg-green-600 text-white rounded-md">Guardar</button>
                                <button wire:click.prevent="$set('editMode', false)" class="px-3 py-1 ml-2 bg-gray-600 text-white rounded-md">Cancelar</button>
                            </div>
                        </div>
                    @endif

                    <hr class="border-t border-[#2A3047] my-3" />

                    <div class="mb-3">
                        <h4 class="font-semibold text-[#F1F5F9]">Total histórico comprado</h4>
                        <div class="text-lg text-[#F1F5F9]">{{ number_format($detailTotal ?? 0, 2) }}</div>
                    </div>

                    <div class="mb-3">
                        <h4 class="font-semibold text-[#F1F5F9]">Historial de pedidos</h4>
                        <ul>
                            @foreach($detailOrders as $o)
                                <li class="text-sm text-[#F1F5F9] mb-1">{{ $o->numero_pedido }} · {{ number_format($o->total,2) }} · {{ $o->estado }} · {{ $o->created_at->format('Y-m-d') }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-[#F1F5F9]">Pagos rechazados</h4>
                        @if($detailRejectedPayments->isEmpty())
                            <div class="text-sm text-[#94A3B8]">No se encontraron pagos rechazados recientes.</div>
                        @else
                            <ul>
                                @foreach($detailRejectedPayments as $p)
                                    <li class="text-sm text-[#F1F5F9]">Pedido: {{ optional($p->order)->numero_pedido }} — Monto: {{ number_format($p->monto,2) }} — Motivo: {{ $p->motivo_rechazo ?? 'N/A' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>