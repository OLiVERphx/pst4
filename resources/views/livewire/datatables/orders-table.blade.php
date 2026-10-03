<div>
<div class="p-3 border-b border-[#475569] flex items-center gap-2">
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#A0AEC0]">🔍</span>
        <input wire:model.live="search" type="text" placeholder="Buscar pedidos" class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm w-56 pl-10 text-[#F1F5F9] placeholder-[#A0AEC0] focus:border-[#1D4ED8]" />
    </div>

    <select wire:model.live="statusFilter" class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
        <option value="">Todos los estados</option>
        @foreach(app(\App\Services\ServicioPedidos::class)->obtenerFlujoEstados() as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </select>
</div>

    <div class="bg-[#334155] border border-[#475569] rounded-xl overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-[#475569]"><tr>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Nro.</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Cliente</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Tipo</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Items</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Total</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Pago</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Estado</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-left">Fecha</th>
                    <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                </tr></thead>
            <tbody class="divide-y">
                @foreach($orders as $order)
                    @php
                        $st = $order->estado;
                        $badgeClass = 'bg-[#A0AEC0]/10 text-[#A0AEC0] px-2 py-1 rounded-full text-xs font-semibold';
                        if(in_array($st, ['pendiente','pago_subido','pago_a_confirmar'])) $badgeClass = 'bg-[#EA580C]/10 text-[#EA580C] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['pago_verificado','entregado','completado','activo'])) $badgeClass = 'bg-[#059669]/10 text-[#059669] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['cancelado','inactivo'])) $badgeClass = 'bg-red-500/10 text-red-400 px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['procesando','listo_para_retirar'])) $badgeClass = 'bg-[#7C3AED]/10 text-[#7C3AED] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['enviado','en_camino','pago_confirmado','pago_confirmado_retirar','en_transito','en_transición'])) $badgeClass = 'bg-[#1D4ED8]/10 text-[#1D4ED8] px-2 py-1 rounded-full text-xs font-semibold';
                    @endphp
                    <tr class="hover:bg-[#475569]">
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->numero_pedido }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->user?->name }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->tipo }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->items->count() }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->payment?->metodo ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm"><span class="{{ $badgeClass }}">{{ \App\Helpers\LabelHelper::translateStatus($order->estado) }}</span></td>
                        <td class="px-4 py-2 text-sm text-[#A0AEC0]">{{ $order->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 text-sm">
                            <button wire:click="openDetail({{ $order->id }})" class="text-[#1D4ED8]">Ver</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">{{ $orders->links() }}</div>
    </div>

    <!-- Detail panel/modal -->
    <div x-data wire:ignore.self x-show="$wire.showDetail" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-end z-50">
        <div class="bg-[#334155] w-full max-w-md h-full overflow-y-auto border-l border-[#475569] shadow-2xl flex flex-col">
            @if($selectedOrder)
                @php
                    $st = $selectedOrder->estado;
                    $badgeClass = 'bg-[#94A3B8]/10 text-[#94A3B8]';
                    if(in_array($st, ['pendiente','pago_subido','pago_a_confirmar'])) $badgeClass = 'bg-[#EA580C]/10 text-[#EA580C]';
                    elseif(in_array($st, ['pago_verificado','entregado','completado','activo'])) $badgeClass = 'bg-[#059669]/10 text-[#059669]';
                    elseif(in_array($st, ['procesando','listo_para_retirar'])) $badgeClass = 'bg-[#7C3AED]/10 text-[#7C3AED]';
                    elseif(in_array($st, ['enviado','en_camino','pago_confirmado','pago_confirmado_retirar','en_transito','en_transición'])) $badgeClass = 'bg-[#1D4ED8]/10 text-[#1D4ED8]';
                    $siguienteLabel = $this->proximoEstadoLabel($selectedOrder);
                @endphp

                <!-- Header -->
                <div class="flex items-start justify-between p-5 border-b border-[#475569]">
                    <div>
                        <div class="text-xs text-[#A0AEC0] font-semibold uppercase tracking-wide mb-1">Pedido</div>
                        <div class="text-lg font-bold text-[#F1F5F9]">{{ $selectedOrder->numero_pedido }}</div>
                        <span class="inline-block mt-2 px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            {{ \App\Helpers\LabelHelper::translateStatus($st) }}
                        </span>
                    </div>
                    <button wire:click.prevent="$set('showDetail', false)" class="text-[#A0AEC0] hover:text-[#F1F5F9] text-xl leading-none">✕</button>
                </div>

                <!-- Datos -->
                <div class="grid grid-cols-2 gap-4 p-5 border-b border-[#475569]">
                    <div>
                        <div class="text-xs text-[#A0AEC0] mb-1">Cliente</div>
                        <div class="text-sm text-[#F1F5F9] font-medium">{{ $selectedOrder->user?->name ?? 'Invitado' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#A0AEC0] mb-1">Tipo de entrega</div>
                        <div class="text-sm text-[#F1F5F9] font-medium">{{ $selectedOrder->tipo_entrega === 'delivery' ? '🚚 Delivery' : '🏪 Retiro' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#A0AEC0] mb-1">Método de pago</div>
                        <div class="text-sm text-[#F1F5F9] font-medium">{{ ucfirst($selectedOrder->payment?->metodo ?? 'fisico') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#A0AEC0] mb-1">Fecha</div>
                        <div class="text-sm text-[#F1F5F9] font-medium">{{ $selectedOrder->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <!-- Items -->
                <div class="p-5 flex-1">
                    <div class="text-xs text-[#A0AEC0] font-semibold uppercase tracking-wide mb-3">Items</div>
                    <div class="space-y-2">
                        @foreach($selectedOrder->items as $it)
                            <div class="flex items-center justify-between bg-[#475569]/30 rounded-lg px-3 py-2.5">
                                <div>
                                    <div class="text-sm text-[#F1F5F9] font-medium">{{ $it->product?->nombre ?? 'Producto eliminado' }}</div>
                                    <div class="text-xs text-[#A0AEC0]">× {{ $it->cantidad }} — ${{ number_format($it->precio_unitario, 2) }} c/u</div>
                                </div>
                                <div class="text-sm text-[#F1F5F9] font-semibold">${{ number_format($it->cantidad * $it->precio_unitario, 2) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-[#475569]">
                        <span class="text-sm text-[#A0AEC0] font-semibold">Total</span>
                        <span class="text-lg text-[#F1F5F9] font-bold">${{ number_format($selectedOrder->total, 2) }}</span>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="p-5 border-t border-[#475569] flex flex-col gap-2">
                    @if($siguienteLabel)
                        <button wire:click.prevent="advance({{ $selectedOrder->id }})" class="w-full bg-[#1D4ED8] text-white px-3 py-2.5 rounded-lg text-sm hover:bg-[#1e40af] transition font-semibold">
                            Avanzar a: {{ $siguienteLabel }}
                        </button>
                    @endif
                    <button wire:click.prevent="cancel({{ $selectedOrder->id }}, 'Cancelado desde admin')" class="w-full bg-red-600/90 text-white px-3 py-2.5 rounded-lg text-sm hover:bg-red-600 transition font-medium">Cancelar pedido</button>
                </div>
            @endif
        </div>
    </div>
</div>