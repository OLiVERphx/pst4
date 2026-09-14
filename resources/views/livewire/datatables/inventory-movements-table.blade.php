<div>
    <div class="p-3 border-b border-[#2A3047] flex items-center gap-2">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
            <input wire:model.live="search" type="text" placeholder="Buscar producto" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm w-56 pl-10 text-[#F1F5F9] placeholder-[#94A3B8] focus:border-[#1D4ED8]" />
        </div>

        <select wire:model="typeFilter" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
            <option value="">Todos los tipos</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
            <option value="ajuste">Ajuste</option>
            <option value="reserva">Reserva</option>
            <option value="liberacion">Liberación</option>
        </select>

        <div class="ml-auto">
            <button wire:click="openCreate" class="bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white px-3 py-2 rounded-lg">Nuevo movimiento</button>
        </div>
    </div>

    <div class="bg-[#131720] border border-[#2A3047] rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-[#2A3047]">
            <thead class="bg-[#1C2130] text-xs uppercase tracking-wide text-[#94A3B8]">
                <tr>
                    <th class="px-4 py-2 text-left">Fecha</th>
                    <th class="px-4 py-2 text-left">Producto</th>
                    <th class="px-4 py-2 text-left">Tipo</th>
                    <th class="px-4 py-2 text-left">Cantidad</th>
                    <th class="px-4 py-2 text-left">Antes</th>
                    <th class="px-4 py-2 text-left">Después</th>
                    <th class="px-4 py-2 text-left">Referencia</th>
                    <th class="px-4 py-2 text-left">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($movements as $m)
                    @php
                        switch($m->tipo) {
                            case 'entrada': $tipoClass = 'bg-[#059669]/10 text-[#059669]'; break;
                            case 'salida': $tipoClass = 'bg-red-500/10 text-red-400'; break;
                            case 'ajuste': $tipoClass = 'bg-[#7C3AED]/10 text-[#7C3AED]'; break;
                            case 'reserva': $tipoClass = 'bg-[#EA580C]/10 text-[#EA580C]'; break;
                            case 'liberacion': $tipoClass = 'bg-[#0D9488]/10 text-[#0D9488]'; break;
                            default: $tipoClass = 'bg-[#94A3B8]/10 text-[#94A3B8]';
                        }
                    @endphp
                    <tr class="hover:bg-[#1C2130]">
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->product?->nombre }}</td>
                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs {{ $tipoClass }}">{{ $m->tipo }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->cantidad }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->cantidad_anterior }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->cantidad_nueva }}</td>
                        <td class="px-4 py-2 text-sm text-[#94A3B8]">{{ $m->referencia }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $m->user?->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">{{ $movements->links() }}</div>
    </div>

    <!-- Modal -->
    <div x-data wire:ignore.self x-show="$wire.showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-[#2B3346] rounded p-4 w-2/3 border border-[#384457] shadow-xl">
            <h3 class="font-semibold mb-2 text-[#F1F5F9]">Nuevo movimiento</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-[#94A3B8]">Producto</label>
                    <select wire:model="form.producto_id" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]">
                        <option value="">--</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-[#94A3B8]">Tipo</label>
                    <select wire:model="form.tipo" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                        <option value="ajuste">Ajuste</option>
                        <option value="reserva">Reserva</option>
                        <option value="liberacion">Liberación</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-[#94A3B8]">Cantidad</label>
                    <input wire:model="form.cantidad" type="number" min="1" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div>
                    <label class="block text-sm text-[#94A3B8]">Referencia</label>
                    <input wire:model="form.referencia" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm text-[#94A3B8]">Notas</label>
                    <textarea wire:model="form.notas" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]"></textarea>
                </div>
            </div>

            <div class="mt-4 text-right">
                <button wire:click.prevent="save" class="bg-[#059669] text-white px-3 py-2 rounded">Guardar</button>
                <button wire:click.prevent="$set('showModal', false)" class="bg-[#2A3047] text-white px-3 py-2 rounded">Cancelar</button>
            </div>
        </div>
    </div>
</div>