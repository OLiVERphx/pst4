<div>
    <h3 class="font-semibold mb-2 text-[#F1F5F9]">Alertas de stock</h3>

    <div class="space-y-3">
        @forelse($alerts as $alert)
            @php
                $sinStock = $alert->tipo === 'sin_stock';
                $tipoBadge = $sinStock ? 'bg-red-500/10 text-red-400' : 'bg-[#EA580C]/10 text-[#EA580C]';
            @endphp
            <div class="bg-[#334155] p-3 rounded flex flex-wrap items-center justify-between gap-2 border border-[#475569] {{ $alert->leido ? 'opacity-60' : '' }}">
                <div>
                    <div class="text-sm font-medium text-[#F1F5F9]">{{ $alert->product?->nombre ?? 'Producto eliminado' }}
                        <span class="ml-1 text-[11px] px-2 py-0.5 rounded-full {{ $tipoBadge }}">{{ $sinStock ? 'Sin stock' : 'Stock bajo' }}</span>
                        @if($alert->leido)<span class="ml-1 text-[11px] text-[#A0AEC0]">Leída</span>@endif
                    </div>
                    <div class="text-xs text-[#A0AEC0]">
                        @if($alert->product)Stock actual: {{ $alert->product->stock }} (mínimo {{ $alert->product->stock_minimo }}) · @endif
                        {{ $alert->created_at->diffForHumans() }}
                    </div>
                </div>
                @can('inventario.ajustar')
                    <div class="flex items-center gap-2">
                        @if($alert->product)
                            <button wire:click="openReplenish({{ $alert->id }})" class="bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white px-3 py-1 rounded-lg">Reponer</button>
                        @endif
                        @unless($alert->leido)
                            <button wire:click="markAsRead({{ $alert->id }})" class="bg-[#475569] text-white px-3 py-1 rounded-lg">Marcar como leída</button>
                        @endunless
                    </div>
                @endcan
            </div>
        @empty
            <div class="text-sm text-[#A0AEC0]">No hay alertas de stock.</div>
        @endforelse
    </div>

    <!-- Modal reponer -->
    <div x-data wire:ignore.self x-show="$wire.showModal" class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4">
        <div class="bg-[#2B3346] rounded p-4 w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-[#384457] shadow-xl">
            <h3 class="font-semibold mb-2 text-[#F1F5F9]">Reponer stock</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-[#A0AEC0]">Cantidad</label>
                    <input wire:model="cantidad" type="number" min="1" class="border rounded px-2 py-1 w-full bg-[#475569] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div>
                    <label class="block text-sm text-[#A0AEC0]">Referencia</label>
                    <input wire:model="referencia" class="border rounded px-2 py-1 w-full bg-[#475569] border-[#384457] text-[#F1F5F9]" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-[#A0AEC0]">Notas</label>
                    <textarea wire:model="notas" class="border rounded px-2 py-1 w-full bg-[#475569] border-[#384457] text-[#F1F5F9]"></textarea>
                </div>
            </div>
            <div class="mt-4 text-right">
                <button wire:click.prevent="save" class="bg-[#059669] text-white px-3 py-2 rounded">Guardar</button>
                <button wire:click.prevent="$set('showModal', false)" class="bg-[#475569] text-white px-3 py-2 rounded">Cancelar</button>
            </div>
        </div>
    </div>
</div>