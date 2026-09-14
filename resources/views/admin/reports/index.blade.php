@extends('layouts.admin')

@section('pageTitle', 'Reportes')

@section('content')
<div class="container mx-auto py-6">
    <p class="text-sm text-[#94A3B8] mb-4">Los datos mostrados se calculan en tiempo real sobre la base de datos actual. El motor de Machine Learning (predicción de demanda, segmentación de clientes).</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Top productos -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-3">Top productos más vendidos</h3>
            <div x-data="{ items: @json($topProducts) }" class="space-y-3">
                <template x-for="i in items" :key="i.producto">
                    <div class="flex items-center gap-3">
                        <div class="w-40 text-sm text-[#F1F5F9]" x-text="i.producto"></div>
                        <div class="flex-1 bg-[#1C2130] rounded h-4 relative">
                            <div class="bg-[#1D4ED8] h-4 rounded" :style="'width: ' + ( (i.total / (Math.max(...items.map(x=>x.total)) || 1)) * 100 ) + '%' "></div>
                        </div>
                        <div class="w-20 text-right text-sm font-semibold text-[#F1F5F9]" x-text="i.total"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Riesgo de stock -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-3">Riesgo de stock</h3>
            <ul class="space-y-2">
                @forelse($lowStock as $p)
                    <li class="flex items-center justify-between">
                        <div class="text-[#F1F5F9]">{{ $p['nombre'] ?? ($p['name'] ?? 'Producto') }}</div>
                        <span class="ml-2 bg-[#EA580C]/15 text-[#FB923C] text-[11px] font-bold rounded-full px-2 py-0.5">Stock: {{ $p['stock'] }}</span>
                    </li>
                @empty
                    <li class="text-[#94A3B8]">No hay productos en riesgo</li>
                @endforelse
            </ul>
        </div>

        <!-- Ventas por categoría -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-3">Ventas por categoría</h3>
            <div x-data="{ cats: @json($ventasPorCategoria) }" class="space-y-3">
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

        <!-- Resumen general -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-3">Resumen general</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[#1C2130] border border-[#2A3047] rounded-lg p-3 text-center">
                    <div class="text-xs text-[#94A3B8]">Total pedidos</div>
                    <div class="text-xl font-black text-[#F1F5F9]">{{ $totalOrders }}</div>
                </div>
                <div class="bg-[#1C2130] border border-[#2A3047] rounded-lg p-3 text-center">
                    <div class="text-xs text-[#94A3B8]">Total facturado</div>
                    <div class="text-xl font-black text-[#F1F5F9]">{{ number_format($totalFacturado, 2) }}</div>
                </div>
                <div class="bg-[#1C2130] border border-[#2A3047] rounded-lg p-3 text-center">
                    <div class="text-xs text-[#94A3B8]">Ticket promedio</div>
                    <div class="text-xl font-black text-[#F1F5F9]">{{ number_format($ticketPromedio, 2) }}</div>
                </div>
                <div class="bg-[#1C2130] border border-[#2A3047] rounded-lg p-3 text-center">
                    <div class="text-xs text-[#94A3B8]">Total clientes</div>
                    <div class="text-xl font-black text-[#F1F5F9]">{{ $totalClients }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
