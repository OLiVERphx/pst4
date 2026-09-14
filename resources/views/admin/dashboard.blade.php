@extends('layouts.admin')

@section('pageTitle', 'Dashboard')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4 text-[#F1F5F9]">Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-transform hover:shadow-xl">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#1D4ED8]"></div>
            <div class="text-3xl mb-2">💰</div>
            <div class="text-3xl font-black tracking-tight text-[#F1F5F9]">{{ number_format($stats['total_ventas_mes'] ?? 0, 2) }} USD</div>
            <div class="text-xs text-[#94A3B8] mt-1">Ventas (mes)</div>
            @if(isset($stats['ventas_mes_trend']))
                @if($stats['ventas_mes_trend'] >= 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#059669]/10 text-[#34D399] mt-2">↑ {{ $stats['ventas_mes_trend'] }}%</span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#DC2626]/10 text-[#F87171] mt-2">↓ {{ abs($stats['ventas_mes_trend']) }}%</span>
                @endif
            @endif
        </div>

        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-transform hover:shadow-xl">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#0D9488]"></div>
            <div class="text-3xl mb-2">🧾</div>
            <div class="text-3xl font-black tracking-tight text-[#F1F5F9]">{{ $stats['pedidos_mes'] ?? 0 }}</div>
            <div class="text-xs text-[#94A3B8] mt-1">Pedidos (mes)</div>
            @if(isset($stats['pedidos_mes_trend']))
                @if($stats['pedidos_mes_trend'] >= 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#059669]/10 text-[#34D399] mt-2">↑ {{ $stats['pedidos_mes_trend'] }}%</span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#DC2626]/10 text-[#F87171] mt-2">↓ {{ abs($stats['pedidos_mes_trend']) }}%</span>
                @endif
            @endif
        </div>

        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-transform hover:shadow-xl">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#059669]"></div>
            <div class="text-3xl mb-2">👥</div>
            <div class="text-3xl font-black tracking-tight text-[#F1F5F9]">{{ $stats['total_clientes'] ?? 0 }}</div>
            <div class="text-xs text-[#94A3B8] mt-1">Clientes</div>
        </div>

        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-transform hover:shadow-xl">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#EA580C]"></div>
            <div class="text-3xl mb-2">⚠️</div>
            <div class="text-3xl font-black tracking-tight text-[#F1F5F9]">{{ $stats['cantidad_stock_bajo'] ?? 0 }}</div>
            <div class="text-xs text-[#94A3B8] mt-1">Alertas stock</div>
        </div>

        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-transform hover:shadow-xl">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#7C3AED]"></div>
            <div class="text-3xl mb-2">💳</div>
            <div class="text-3xl font-black tracking-tight text-[#F1F5F9]">{{ $stats['pagos_por_verificar'] ?? 0 }}</div>
            <div class="text-xs text-[#94A3B8] mt-1">Pagos por verificar</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-[#131720] border border-[#2A3047] p-4 rounded shadow">
            <h2 class="font-semibold mb-3 text-[#F1F5F9]">Ventas por categoría</h2>
            Acá irian una vista previa de los reportes hechos mediante un entorno Machine Learning

            <div x-data="{ cats: @json($stats['ventas_por_categoria']) }" class="space-y-3">
                <template x-for="c in cats" :key="c.categoria">
                    <div class="flex items-center space-x-3">
                        <div class="w-1/3 text-sm font-medium text-[#F1F5F9]" x-text="c.categoria"></div>
                        <div class="w-2/3 bg-[#1C2130] rounded h-4 relative">
                            <div class="bg-[#1D4ED8] h-4 rounded" :style="'width: ' + ( (c.total / (Math.max(...cats.map(x=>x.total)) || 1)) * 100 ) + '%'">
                            </div>
                        </div>
                        <div class="w-24 text-right text-sm font-semibold text-[#F1F5F9]" x-text="new Intl.NumberFormat().format(c.total)"></div>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-[#131720] border border-[#2A3047] p-4 rounded shadow">
            <h2 class="font-semibold mb-3 text-[#F1F5F9]">Últimos pedidos</h2>
            <table class="w-full text-sm">
                <thead class="bg-[#1C2130] text-xs uppercase tracking-wide text-[#94A3B8]">
                    <tr>
                        <th class="px-2 py-2">#</th>
                        <th class="px-2 py-2">Cliente</th>
                        <th class="px-2 py-2">Total</th>
                        <th class="px-2 py-2">Estado</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lastOrders as $order)
                        @php
                            $st = $order->estado;
                            $badgeClass = 'bg-[#94A3B8]/10 text-[#94A3B8] px-2 py-1 rounded-full text-xs font-semibold';
                            if(in_array($st, ['pendiente','pago_subido'])) $badgeClass = 'bg-[#EA580C]/10 text-[#EA580C] px-2 py-1 rounded-full text-xs font-semibold';
                            elseif(in_array($st, ['pago_verificado','entregado','activo'])) $badgeClass = 'bg-[#059669]/10 text-[#059669] px-2 py-1 rounded-full text-xs font-semibold';
                            elseif(in_array($st, ['cancelado','inactivo'])) $badgeClass = 'bg-red-500/10 text-red-400 px-2 py-1 rounded-full text-xs font-semibold';
                            elseif($st === 'procesando') $badgeClass = 'bg-[#7C3AED]/10 text-[#7C3AED] px-2 py-1 rounded-full text-xs font-semibold';
                            elseif(in_array($st, ['enviado','en_transito','en_transición'])) $badgeClass = 'bg-[#1D4ED8]/10 text-[#1D4ED8] px-2 py-1 rounded-full text-xs font-semibold';
                        @endphp
                        <tr class="border-t cursor-pointer hover:bg-[#1C2130] transition-colors" onclick="window.location='{{ route('admin.pedidos.lista') }}?highlight={{ $order->id }}'">
                            <td class="py-2 text-[#F1F5F9]">{{ $order->numero_pedido }}</td>
                            <td class="py-2 text-[#F1F5F9]">{{ $order->user?->name }}</td>
                            <td class="py-2 text-[#F1F5F9]">{{ number_format($order->total, 2) }}</td>
                            <td class="py-2">
                                <span class="{{ $badgeClass }}">{{ \App\Helpers\LabelHelper::translateStatus($order->estado) }}</span>
                            </td>
                            <td class="py-2 text-right"><a href="{{ route('admin.pedidos.lista') }}?highlight={{ $order->id }}" class="text-[#1D4ED8]">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
