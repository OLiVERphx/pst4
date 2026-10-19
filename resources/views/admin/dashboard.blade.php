@extends('layouts.admin')

@section('pageTitle', 'Dashboard')

@section('content')
@php
    $usd = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');

    // Ventas de las últimas 8 semanas: barras SVG dibujadas en el servidor (sin JavaScript)
    $semanas = $stats['ventas_semanales'] ?? [];
    $maxSemana = max(array_column($semanas, 'total') ?: [0]);
    $escalaMax = $maxSemana > 0 ? $maxSemana * 1.15 : 1;
    $chartW = 640; $chartH = 200; $padL = 8; $padR = 64; $padB = 26; $padT = 18;
    $plotH = $chartH - $padB - $padT;
    $slot = count($semanas) ? ($chartW - $padL - $padR) / count($semanas) : 0;
    $barW = min(44, $slot * 0.6);
    $totalSemanas = array_sum(array_column($semanas, 'total'));

    // Ventas por categoría
    $categorias = $stats['ventas_por_categoria'] ?? [];
    $maxCategoria = max(array_column($categorias, 'total') ?: [0]);
    $totalCategorias = array_sum(array_column($categorias, 'total'));

    $tendencia = function ($valor) {
        if ($valor === null) {
            return '<span class="text-xs text-[#A0AEC0]">Sin datos del mes anterior</span>';
        }
        $sube = $valor >= 0;
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold '
            . ($sube ? 'bg-[#059669]/15 text-[#34D399]' : 'bg-[#DC2626]/15 text-[#F87171]') . '">'
            . ($sube ? '▲' : '▼') . ' ' . number_format(abs($valor), 1, ',', '.') . ' %</span>'
            . '<span class="text-xs text-[#A0AEC0] ml-1">vs. mismo tramo del mes anterior</span>';
    };
@endphp

<div class="container mx-auto py-6 space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold text-[#F1F5F9]">Dashboard</h1>
            <p class="text-sm text-[#A0AEC0]">{{ ucfirst(now()->locale('es')->translatedFormat('F \d\e Y')) }} · ventas con pago confirmado</p>
        </div>
    </div>

    {{-- Indicadores principales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5 lg:col-span-2">
            <div class="text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">Ventas del mes</div>
            <div class="mt-1 text-4xl font-black tracking-tight text-[#F1F5F9]">{{ $usd($stats['total_ventas_mes'] ?? 0) }}</div>
            @if(! empty($stats['total_ventas_mes_bs']))
                <div class="text-sm text-[#A0AEC0]">≈ Bs {{ number_format($stats['total_ventas_mes_bs'], 2, ',', '.') }} a tasa BCV vigente</div>
            @endif
            <div class="mt-3">{!! $tendencia($stats['ventas_mes_trend'] ?? null) !!}</div>
        </div>

        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">Pedidos del mes</div>
            <div class="mt-1 text-3xl font-black text-[#F1F5F9]">{{ number_format($stats['pedidos_mes'] ?? 0, 0, ',', '.') }}</div>
            <div class="mt-3">{!! $tendencia($stats['pedidos_mes_trend'] ?? null) !!}</div>
        </div>

        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">Clientes registrados</div>
            <div class="mt-1 text-3xl font-black text-[#F1F5F9]">{{ number_format($stats['total_clientes'] ?? 0, 0, ',', '.') }}</div>
            <a href="{{ route('admin.clients.lista') }}" class="mt-3 inline-block text-xs font-semibold text-[#93C5FD] hover:underline">Ver clientes →</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Ventas semanales --}}
        <div class="lg:col-span-2 bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                <h2 class="font-semibold text-[#F1F5F9]">Ventas de las últimas 8 semanas</h2>
                <span class="text-sm text-[#A0AEC0]">Total: <span class="font-semibold text-[#F1F5F9]">{{ $usd($totalSemanas) }}</span></span>
            </div>

            @if($maxSemana > 0)
                <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-auto" role="img"
                     aria-label="Ventas semanales en dólares de las últimas 8 semanas">
                    {{-- Líneas guía recesivas al 50 % y 100 % del máximo --}}
                    @foreach([0.5, 1] as $f)
                        @php $y = $padT + $plotH - ($maxSemana * $f / $escalaMax) * $plotH; @endphp
                        <line x1="{{ $padL }}" x2="{{ $chartW - $padR }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#475569" stroke-width="1" stroke-dasharray="3 4" />
                        <text x="{{ $chartW - $padR + 6 }}" y="{{ $y + 4 }}" font-size="11" fill="#A0AEC0">{{ $usd($maxSemana * $f) }}</text>
                    @endforeach
                    <line x1="{{ $padL }}" x2="{{ $chartW - $padR }}" y1="{{ $padT + $plotH }}" y2="{{ $padT + $plotH }}" stroke="#64748B" stroke-width="1" />

                    @foreach($semanas as $i => $s)
                        @php
                            $h = $s['total'] > 0 ? max(2, ($s['total'] / $escalaMax) * $plotH) : 0;
                            $x = $padL + $slot * $i + ($slot - $barW) / 2;
                            $y = $padT + $plotH - $h;
                            $esUltima = $i === count($semanas) - 1;
                        @endphp
                        <g>
                            <title>Semana del {{ $s['label'] }}: {{ $usd($s['total']) }}</title>
                            {{-- Zona de hover más grande que la barra --}}
                            <rect x="{{ $padL + $slot * $i }}" y="{{ $padT }}" width="{{ $slot }}" height="{{ $plotH }}" fill="transparent" />
                            @if($h > 0)
                                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ $h }}" rx="{{ min(4, $h / 2) }}"
                                      fill="{{ $esUltima ? '#93C5FD' : '#60A5FA' }}" />
                                @if($h > 4)
                                    <rect x="{{ $x }}" y="{{ $padT + $plotH - min(4, $h / 2) }}" width="{{ $barW }}" height="{{ min(4, $h / 2) }}" fill="{{ $esUltima ? '#93C5FD' : '#60A5FA' }}" />
                                @endif
                            @endif
                            @if($s['total'] == $maxSemana || $esUltima)
                                <text x="{{ $x + $barW / 2 }}" y="{{ $y - 5 }}" text-anchor="middle" font-size="11" font-weight="600" fill="#F1F5F9">{{ $usd($s['total']) }}</text>
                            @endif
                            <text x="{{ $x + $barW / 2 }}" y="{{ $chartH - 8 }}" text-anchor="middle" font-size="11" fill="#A0AEC0">{{ $s['label'] }}</text>
                        </g>
                    @endforeach
                </svg>
                <p class="mt-2 text-xs text-[#A0AEC0]">Cada barra es una semana (fecha del lunes). La más clara es la semana en curso. Pasa el cursor sobre una barra para ver su monto.</p>
            @else
                <div class="py-12 text-center text-sm text-[#A0AEC0]">Aún no hay ventas confirmadas en las últimas 8 semanas.</div>
            @endif
        </div>

        {{-- Requiere atención --}}
        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <h2 class="font-semibold text-[#F1F5F9] mb-3">Requiere atención</h2>
            @php
                $pendientes = [
                    ['n' => $stats['pagos_por_verificar'] ?? 0, 'label' => 'Pagos por verificar', 'url' => route('admin.payments.lista')],
                    ['n' => $stats['pedidos_pendientes'] ?? 0, 'label' => 'Pedidos pendientes', 'url' => route('admin.pedidos.lista')],
                    ['n' => $stats['cantidad_stock_bajo'] ?? 0, 'label' => 'Productos con stock bajo', 'url' => route('admin.inventario.alertas')],
                ];
            @endphp
            <div class="space-y-2">
                @foreach($pendientes as $item)
                    <a href="{{ $item['url'] }}" class="flex items-center justify-between rounded-lg border border-[#475569] px-3 py-2.5 hover:bg-[#475569]/50 transition-colors">
                        <span class="text-sm text-[#F1F5F9]">{{ $item['label'] }}</span>
                        <span class="text-sm font-bold {{ $item['n'] > 0 ? 'text-[#FBBF24]' : 'text-[#34D399]' }}">
                            {{ $item['n'] > 0 ? '⚠ ' . $item['n'] : '✓ 0' }}
                        </span>
                    </a>
                @endforeach
            </div>

            @if(($stats['productos_stock_bajo'] ?? collect())->isNotEmpty())
                <h3 class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">Reponer primero</h3>
                <ul class="space-y-1.5">
                    @foreach($stats['productos_stock_bajo'] as $prod)
                        <li class="flex items-center justify-between text-sm">
                            <span class="truncate text-[#F1F5F9]" title="{{ $prod->nombre }}">{{ $prod->nombre }}</span>
                            <span class="ml-2 shrink-0 text-xs text-[#A0AEC0]">{{ $prod->stock }} / mín. {{ $prod->stock_minimo }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Ventas por categoría --}}
        <div class="lg:col-span-2 bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                <h2 class="font-semibold text-[#F1F5F9]">Ventas por categoría</h2>
                <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-[#93C5FD] hover:underline">Ver reportes →</a>
            </div>
            @if($maxCategoria > 0)
                <div class="space-y-3">
                    @foreach($categorias as $cat)
                        @php $pct = $totalCategorias > 0 ? $cat['total'] / $totalCategorias * 100 : 0; @endphp
                        <div class="grid grid-cols-[minmax(0,8rem)_1fr_auto] items-center gap-3"
                             title="{{ $cat['categoria'] }}: {{ $usd($cat['total']) }} ({{ number_format($pct, 1, ',', '.') }} % del total)">
                            <div class="truncate text-sm text-[#F1F5F9]">{{ $cat['categoria'] }}</div>
                            <div class="h-3 rounded bg-[#475569]/60">
                                <div class="h-3 rounded bg-[#60A5FA]" style="width: {{ max(1, $cat['total'] / $maxCategoria * 100) }}%"></div>
                            </div>
                            <div class="w-32 text-right text-sm">
                                <span class="font-semibold text-[#F1F5F9]">{{ $usd($cat['total']) }}</span>
                                <span class="text-xs text-[#A0AEC0]">· {{ number_format($pct, 0) }} %</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-[#A0AEC0]">Acumulado de pedidos con pago confirmado.</p>
            @else
                <div class="py-10 text-center text-sm text-[#A0AEC0]">Aún no hay ventas confirmadas por categoría.</div>
            @endif
        </div>

        {{-- Últimos pedidos --}}
        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="flex items-baseline justify-between mb-3">
                <h2 class="font-semibold text-[#F1F5F9]">Últimos pedidos</h2>
                <a href="{{ route('admin.pedidos.lista') }}" class="text-xs font-semibold text-[#93C5FD] hover:underline">Ver todos →</a>
            </div>
            @forelse($lastOrders as $order)
                @php
                    $st = $order->estado;
                    $badge = match (true) {
                        in_array($st, ['pendiente', 'pago_subido', 'pago_a_confirmar']) => 'bg-[#D97706]/15 text-[#FBBF24]',
                        in_array($st, \App\Models\Order::ESTADOS_PAGADOS) => 'bg-[#059669]/15 text-[#34D399]',
                        $st === 'listo_para_retirar' => 'bg-[#2563EB]/15 text-[#93C5FD]',
                        $st === 'cancelado' => 'bg-[#DC2626]/15 text-[#F87171]',
                        default => 'bg-[#475569] text-[#CBD5E1]',
                    };
                @endphp
                <a href="{{ route('admin.pedidos.lista') }}?highlight={{ $order->id }}"
                   class="flex items-center justify-between gap-2 border-t border-[#475569] py-2.5 first:border-t-0 hover:bg-[#475569]/40 -mx-2 px-2 rounded transition-colors">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold whitespace-nowrap text-[#F1F5F9]">{{ $order->numero_pedido }}</div>
                        <div class="truncate text-xs text-[#A0AEC0]">{{ $order->user?->name ?? 'Venta en local' }} · {{ $order->created_at->format('d/m H:i') }}</div>
                    </div>
                    <div class="text-right max-w-[10rem]">
                        <div class="text-sm font-semibold text-[#F1F5F9]">{{ $usd($order->total) }}</div>
                        <span class="inline-block mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badge }}">{{ \App\Helpers\LabelHelper::translateStatus($st) }}</span>
                    </div>
                </a>
            @empty
                <div class="py-8 text-center text-sm text-[#A0AEC0]">Todavía no hay pedidos.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
