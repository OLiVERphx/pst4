<div class="panel">
    <h3 class="text-lg font-bold text-[#F1F5F9] mb-4">Venta en Local</h3>

    <div class="mb-5">
        <label class="text-xs text-[#A0AEC0] font-semibold uppercase tracking-wide mb-1 block">Buscar producto (nombre o código)</label>
        <div class="relative max-w-md">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#A0AEC0]">🔍</span>
            <input type="text" wire:model.live.debounce.300ms="query" placeholder="Buscar..."
                class="w-full pl-10 pr-4 py-2 bg-[#475569] border border-[#475569] rounded-lg text-sm text-[#F1F5F9] placeholder-[#A0AEC0] focus:border-[#1D4ED8] focus:outline-none" />
        </div>

        @if(count($results))
        <div class="mt-2 space-y-2 max-w-md">
            @foreach($results as $r)
                <div class="flex items-center justify-between bg-[#475569]/30 border border-[#475569] rounded-lg px-3 py-2">
                    <div>
                        <div class="text-sm font-semibold text-[#F1F5F9]">{{ $r['nombre'] }}</div>
                        <div class="text-xs text-[#A0AEC0]">{{ $r['codigo'] }} — Stock: {{ $r['stock'] }}</div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click.prevent="addItem({{ $r['id'] }}, 'detal', 1)" class="bg-[#1D4ED8] hover:bg-[#1e40af] text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">Detal</button>
                        <button wire:click.prevent="addItem({{ $r['id'] }}, 'mayor', 1)" class="bg-[#334155] hover:bg-[#475569] border border-[#475569] text-[#F1F5F9] text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">Mayor</button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <h4 class="text-xs text-[#A0AEC0] font-semibold uppercase tracking-wide mb-2">Items</h4>
    <div class="bg-[#334155] border border-[#475569] rounded-xl overflow-hidden mb-5">
        <table class="min-w-full">
            <thead class="bg-[#475569]">
                <tr>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Producto</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Cantidad</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Precio</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Subtotal</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#475569]">
                @forelse($items as $i => $it)
                    <tr class="hover:bg-[#475569]/30">
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $it['nombre'] }} <span class="text-[#A0AEC0]">({{ $it['codigo'] }})</span></td>
                        <td class="px-4 py-2">
                            <input type="number" value="{{ $it['cantidad'] }}" wire:change="updateQuantity({{ $i }}, $event.target.value)"
                                class="w-16 bg-[#475569] border border-[#475569] rounded px-2 py-1 text-sm text-[#F1F5F9]" />
                        </td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">${{ number_format($it['precio'], 2) }}</td>
                        <td class="px-4 py-2 text-sm font-semibold text-[#F1F5F9]">${{ number_format($it['precio'] * $it['cantidad'], 2) }}</td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click.prevent="removeItem({{ $i }})" class="text-red-400 hover:text-red-300 text-xs font-semibold">Quitar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-[#A0AEC0]">Agrega productos con el buscador de arriba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h4 class="text-xs text-[#A0AEC0] font-semibold uppercase tracking-wide mb-2">Cliente (opcional)</h4>
    <div class="grid grid-cols-2 gap-4 max-w-md mb-4">
        <div>
            <label class="text-xs text-[#A0AEC0] mb-1 block">Cédula</label>
            <input type="text" wire:model.lazy="cliente_cedula" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
        </div>
        <div>
            <label class="text-xs text-[#A0AEC0] mb-1 block">Teléfono</label>
            <input type="text" wire:model.lazy="cliente_telefono" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
        </div>
    </div>

    <div class="max-w-md mb-5">
        <label class="text-xs text-[#A0AEC0] mb-1 block">Notas</label>
        <textarea wire:model.lazy="notas" rows="2" class="w-full bg-[#475569] border border-[#475569] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]"></textarea>
    </div>

    <button wire:click.prevent="confirmSale" class="bg-[#059669] hover:bg-[#047857] text-white font-semibold px-4 py-2.5 rounded-lg transition">
        Confirmar venta (Dejar pendiente de pago)
    </button>

    @if($errors->any())
        <div class="mt-4 bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-sm text-red-400 space-y-1 max-w-md">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if(session()->has('success'))
        <div class="mt-4 bg-[#059669]/10 border border-[#059669]/30 rounded-lg px-4 py-3 text-sm text-[#059669] max-w-md">{{ session('success') }}</div>
    @endif
</div>
