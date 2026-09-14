<div>
    <h3 class="font-semibold mb-2 text-[#F1F5F9]">Alertas de stock</h3>

    <div class="space-y-3">
        @foreach($alerts as $alert)
            @php $tipoBadge = $alert->tipo === 'bajo' ? 'bg-[#EA580C]/10 text-[#EA580C]' : 'bg-[#059669]/10 text-[#059669]'; @endphp
            <div class="bg-[#131720] p-3 rounded flex items-center justify-between border border-[#2A3047] hover:bg-[#1C2130]">
                <div>
                    <div class="text-sm font-medium text-[#F1F5F9]">{{ $alert->product?->nombre ?? 'Producto eliminado' }}</div>
                    <div class="text-xs text-[#94A3B8]">Tipo: {{ $alert->tipo }} - {{ $alert->created_at->diffForHumans() }}</div>
                </div>
                <div class="flex items-center gap-2">
                            <button wire:click="openReplenish({{ $alert->id }})" class="bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white px-3 py-1 rounded-lg">Reponer</button>
                            <button wire:click="markAsRead(App\\Models\\StockAlert::find({{ $alert->id }}))" class="bg-[#2A3047] text-white px-3 py-1 rounded-lg">Marcar como leída</button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal reponer -->
    <div x-data wire:ignore.self x-show="$wire.showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-[#2B3346] rounded p-4 w-2/3 border border-[#384457] shadow-xl">
            <h3 class="font-semibold mb-2 text-[#F1F5F9]">Reponer stock</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-[#94A3B8]">Cantidad</label>
                    <input wire:model="cantidad" type="number" min="1" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div>
                    <label class="block text-sm text-[#94A3B8]">Referencia</label>
                    <input wire:model="referencia" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm text-[#94A3B8]">Notas</label>
                    <textarea wire:model="notas" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]"></textarea>
                </div>
            </div>
            <div class="mt-4 text-right">
                <button wire:click.prevent="save" class="bg-[#059669] text-white px-3 py-2 rounded">Guardar</button>
                <button wire:click.prevent="$set('showModal', false)" class="bg-[#2A3047] text-white px-3 py-2 rounded">Cancelar</button>
            </div>
        </div>
    </div>
</div>