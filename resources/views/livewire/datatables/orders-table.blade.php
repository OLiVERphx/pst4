<div>
<div class="p-3 border-b border-[#2A3047] flex items-center gap-2">
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
        <input wire:model.live="search" type="text" placeholder="Buscar pedidos" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm w-56 pl-10 text-[#F1F5F9] placeholder-[#94A3B8] focus:border-[#1D4ED8]" />
    </div>

    <select wire:model="statusFilter" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="pago_subido">Pago subido</option>
        <option value="pago_verificado">Pago verificado</option>
        <option value="procesando">Procesando</option>
        <option value="enviado">Enviado</option>
        <option value="entregado">Entregado</option>
        <option value="cancelado">Cancelado</option>
    </select>
</div>

    <div class="bg-[#131720] border border-[#2A3047] rounded-xl overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-[#1C2130]"><tr>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Nro.</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Cliente</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Tipo</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Items</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Total</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Pago</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Estado</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Fecha</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                </tr></thead>
            <tbody class="divide-y">
                @foreach($orders as $order)
                    @php
                        $st = $order->estado;
                        $badgeClass = 'bg-[#94A3B8]/10 text-[#94A3B8] px-2 py-1 rounded-full text-xs font-semibold';
                        if(in_array($st, ['pendiente','pago_subido'])) $badgeClass = 'bg-[#EA580C]/10 text-[#EA580C] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['pago_verificado','entregado','activo'])) $badgeClass = 'bg-[#059669]/10 text-[#059669] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['cancelado','inactivo'])) $badgeClass = 'bg-red-500/10 text-red-400 px-2 py-1 rounded-full text-xs font-semibold';
                        elseif($st === 'procesando') $badgeClass = 'bg-[#7C3AED]/10 text-[#7C3AED] px-2 py-1 rounded-full text-xs font-semibold';
                        elseif(in_array($st, ['enviado','en_transito','en_transición'])) $badgeClass = 'bg-[#1D4ED8]/10 text-[#1D4ED8] px-2 py-1 rounded-full text-xs font-semibold';
                    @endphp
                    <tr class="hover:bg-[#1C2130]">
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->numero_pedido }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->user?->name }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->tipo }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->items->count() }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-2 text-sm text-[#F1F5F9]">{{ $order->payment?->metodo ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm"><span class="{{ $badgeClass }}">{{ \App\Helpers\LabelHelper::translateStatus($order->estado) }}</span></td>
                        <td class="px-4 py-2 text-sm text-[#94A3B8]">{{ $order->created_at->format('Y-m-d') }}</td>
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
    <div x-data wire:ignore.self x-show="$wire.showDetail" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-end">
        <div class="bg-[#2B3346] rounded p-4 w-1/3 h-full overflow-auto border border-[#384457] shadow-xl">
            @if($selectedOrder)
                <h3 class="font-semibold mb-2 text-[#F1F5F9]">Pedido {{ $selectedOrder->numero_pedido }}</h3>
                <div class="mb-3 text-[#F1F5F9]">
                    <div>Cliente: {{ $selectedOrder->user?->name }}</div>
                    <div>Total: {{ number_format($selectedOrder->total, 2) }}</div>
                <div>Estado: {{ \App\Helpers\LabelHelper::translateStatus($selectedOrder->estado) }}</div>
                </div>

                <h4 class="font-semibold mb-1 text-[#F1F5F9]">Items</h4>
                <ul class="mb-3 text-[#F1F5F9]">
                    @foreach($selectedOrder->items as $it)
                        <li class="text-sm">{{ $it->product?->nombre ?? 'Producto eliminado' }} x{{ $it->cantidad }} - {{ number_format($it->precio_unitario, 2) }}</li>
                    @endforeach
                </ul>

                <div class="flex gap-2">
                    <button wire:click.prevent="advance($selectedOrder)" class="bg-[#1D4ED8] text-white px-3 py-2 rounded">Avanzar estado</button>
                    <button wire:click.prevent="cancel($selectedOrder, 'Cancelado desde admin')" class="bg-red-600 text-white px-3 py-2 rounded">Cancelar</button>
                    <button wire:click.prevent="$set('showDetail', false)" class="bg-[#2A3047] text-white px-3 py-2 rounded">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>