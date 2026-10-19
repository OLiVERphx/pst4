@extends('layouts.admin')

@section('pageTitle', 'Reportes')

@php
    $usd = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
    $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $maxTop = max(1, ...array_map(fn ($p) => (float) $p->total, $topProducts ?: [(object) ['total' => 1]]));
    $maxCat = max(1, ...array_map(fn ($c) => (float) $c->total, $ventasPorCategoria ?: [(object) ['total' => 1]]));

    $metodos = [
        'ingenuo' => 'Última semana',
        'promedio_movil' => 'Promedio 4 semanas',
        'ses' => 'Suavizado exponencial',
        'croston_sba' => 'Croston (SBA)',
    ];
    $tipos = ['regular' => 'Regular', 'intermitente' => 'Intermitente', 'sin_ventas' => 'Sin ventas'];
    $confianzas = [
        'alta' => 'bg-emerald-500/15 text-emerald-300',
        'media' => 'bg-amber-500/15 text-amber-300',
        'baja' => 'bg-red-500/15 text-red-300',
    ];
    $card = 'bg-[#334155] border border-[#475569] rounded-xl p-5';
@endphp

@section('content')
<div class="container mx-auto py-6 space-y-6">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">{{ session('error') }}</div>
    @endif

    {{-- Resumen general --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total pedidos', $num($totalOrders)],
            ['Total facturado', $usd($totalFacturado)],
            ['Ticket promedio', $usd($ticketPromedio)],
            ['Clientes registrados', $num($totalClients)],
        ] as [$titulo, $valor])
            <div class="{{ $card }}">
                <div class="text-xs uppercase tracking-wide text-[#A0AEC0]">{{ $titulo }}</div>
                <div class="mt-1 text-2xl font-black text-[#F1F5F9]">{{ $valor }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Top productos --}}
        <div class="{{ $card }}">
            <h3 class="mb-3 font-bold text-[#F1F5F9]">Productos más vendidos (unidades)</h3>
            <div class="space-y-2">
                @forelse ($topProducts as $p)
                    <div class="grid grid-cols-[minmax(0,10rem)_1fr_3rem] items-center gap-3">
                        <div class="truncate text-sm text-[#F1F5F9]" title="{{ $p->producto }}">{{ $p->producto }}</div>
                        <div class="h-3 rounded bg-[#475569]/60">
                            <div class="h-3 rounded bg-[#60A5FA]" style="width: {{ max(1, $p->total / $maxTop * 100) }}%"></div>
                        </div>
                        <div class="text-right text-sm font-semibold text-[#F1F5F9]">{{ $num($p->total) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-[#A0AEC0]">Aún no hay ventas.</p>
                @endforelse
            </div>
        </div>

        {{-- Ventas por categoría --}}
        <div class="{{ $card }}">
            <h3 class="mb-3 font-bold text-[#F1F5F9]">Ventas por categoría (pedidos pagados)</h3>
            <div class="space-y-2">
                @forelse ($ventasPorCategoria as $c)
                    <div class="grid grid-cols-[minmax(0,10rem)_1fr_7rem] items-center gap-3">
                        <div class="truncate text-sm text-[#F1F5F9]" title="{{ $c->categoria }}">{{ $c->categoria }}</div>
                        <div class="h-3 rounded bg-[#475569]/60">
                            <div class="h-3 rounded bg-[#60A5FA]" style="width: {{ max(1, $c->total / $maxCat * 100) }}%"></div>
                        </div>
                        <div class="text-right text-sm font-semibold text-[#F1F5F9]">{{ $usd($c->total) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-[#A0AEC0]">Aún no hay ventas pagadas.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Stock bajo el mínimo --}}
    <div class="{{ $card }}">
        <h3 class="mb-3 font-bold text-[#F1F5F9]">Productos en o por debajo del stock mínimo</h3>
        <div class="flex flex-wrap gap-2">
            @forelse ($lowStock as $p)
                <span class="rounded-full bg-[#EA580C]/15 px-3 py-1 text-xs font-semibold text-[#FB923C]">
                    {{ $p['nombre'] }}: {{ $p['stock'] }} (mín. {{ $p['stock_minimo'] }})
                </span>
            @empty
                <p class="text-sm text-[#A0AEC0]">Ningún producto está por debajo de su mínimo.</p>
            @endforelse
        </div>
    </div>

    {{-- Analítica predictiva (ml-service) --}}
    <section class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#F1F5F9]">Analítica predictiva</h2>
                <p class="text-sm text-[#A0AEC0]">Pronóstico de demanda para las próximas 4 semanas y segmentación de clientes (RFM).</p>
            </div>
            @if ($mlReport['estado'] !== 'sin_configurar')
                <form method="POST" action="{{ route('admin.reports.ml.recalcular') }}">
                    @csrf
                    <button class="rounded-lg border border-[#475569] px-3 py-2 text-sm font-semibold text-[#F1F5F9] hover:bg-[#475569]">Recalcular</button>
                </form>
            @endif
        </div>

        @if ($hayVentasDemo)
            <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-200">
                La base contiene ventas de <strong>demostración</strong> (pedidos DEMO-, clientes @demo.local). Estos resultados ilustran el funcionamiento del módulo; no describen ventas reales.
                Se eliminan con <code>php artisan demo:ventas --limpiar</code>.
            </div>
        @endif

        @switch($mlReport['estado'])
            @case('sin_configurar')
                <div class="{{ $card }} text-sm text-[#A0AEC0]">El servicio de analítica no está configurado (ML_SERVICE_URL en el archivo .env).</div>
                @break
            @case('clave_invalida')
                <div class="{{ $card }} text-sm text-red-200">El servicio de analítica rechazó la clave: ML_API_KEY del .env de Laravel no coincide con la del servicio.</div>
                @break
            @case('caido')
                <div class="{{ $card }} text-sm text-[#A0AEC0]">El servicio de analítica no responde. Se volverá a intentar en unos minutos o al pulsar «Recalcular».</div>
                @break
            @default
                @php
                    $demanda = $mlReport['demanda'] ?? [];
                    $segmentos = $mlReport['segmentos'] ?? [];
                    $porSegmento = collect($segmentos['items'] ?? [])->groupBy('segmento');
                @endphp

                <p class="text-xs text-[#A0AEC0]">
                    Calculado el {{ \Illuminate\Support\Carbon::parse($mlReport['generado_en'])->format('d/m/Y h:i a') }}
                    con {{ $num($mlReport['datos']['lineas_venta'] ?? 0) }} líneas de venta de las últimas {{ $mlReport['datos']['semanas_historial'] ?? 52 }} semanas.
                </p>

                {{-- Reposición --}}
                <div class="{{ $card }}">
                    <h3 class="font-bold text-[#F1F5F9]">Reposición sugerida</h3>
                    <p class="mb-3 text-xs text-[#A0AEC0]">
                        Unidades para cubrir la demanda pronosticada de 4 semanas sin bajar del stock mínimo.
                        Para cada producto se elige el método que menos se equivocó con sus propias ventas recientes.
                    </p>
                    @if (($demanda['status'] ?? null) === 'ok')
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-xs uppercase tracking-wide text-[#A0AEC0]">
                                    <tr class="border-b border-[#475569]">
                                        <th class="py-2 pr-3">Producto</th>
                                        <th class="py-2 pr-3">Demanda</th>
                                        <th class="py-2 pr-3">Método</th>
                                        <th class="py-2 pr-3">Confianza</th>
                                        <th class="py-2 pr-3 text-right">Pronóstico 4 sem.</th>
                                        <th class="py-2 pr-3 text-right">Stock (mín.)</th>
                                        <th class="py-2 text-right">Reponer</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[#F1F5F9]">
                                    @foreach ($demanda['items'] as $item)
                                        <tr class="border-b border-[#475569]/50">
                                            <td class="py-2 pr-3">{{ $item['producto'] }}</td>
                                            <td class="py-2 pr-3 text-[#CBD5E1]">{{ $tipos[$item['tipo']] ?? $item['tipo'] }}</td>
                                            <td class="py-2 pr-3 text-[#CBD5E1]">
                                                @if ($item['status'] === 'ok')
                                                    {{ $metodos[$item['metodo']] ?? $item['metodo'] }}
                                                @elseif ($item['status'] === 'poco_historial')
                                                    Promedio (poco historial)
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="py-2 pr-3">
                                                @if ($item['confianza'])
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $confianzas[$item['confianza']] ?? '' }}"
                                                          @if ($item['error_modelo'] !== null) title="Error típico: {{ $num($item['error_modelo'], 1) }} unid./semana (repetir la última semana: {{ $num($item['error_ingenuo'], 1) }})" @endif>
                                                        {{ ucfirst($item['confianza']) }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="py-2 pr-3 text-right">{{ $num(array_sum($item['pronostico_semanal']), 1) }}</td>
                                            <td class="py-2 pr-3 text-right">{{ $item['stock_actual'] }} ({{ $item['stock_minimo'] }})</td>
                                            <td class="py-2 text-right font-bold {{ $item['reponer_sugerido'] > 0 ? 'text-[#FB923C]' : 'text-[#A0AEC0]' }}">
                                                {{ $item['reponer_sugerido'] > 0 ? $item['reponer_sugerido'] : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-xs text-[#A0AEC0]">
                            Confianza: compara el error típico del método con la venta semanal promedio del producto
                            (alta: menos de la mitad; baja: igual o mayor). Pasa el cursor sobre la etiqueta para ver el error.
                        </p>
                    @else
                        <p class="text-sm text-[#A0AEC0]">{{ $demanda['motivo'] ?? 'No hay datos suficientes para pronosticar.' }}</p>
                    @endif
                </div>

                {{-- Segmentos --}}
                <div class="{{ $card }}">
                    <h3 class="font-bold text-[#F1F5F9]">Segmentos de clientes</h3>
                    <p class="mb-3 text-xs text-[#A0AEC0]">
                        Cada cliente recibe una nota de 1 a 5 en recencia (hace cuánto compró), frecuencia (cuántos pedidos) y monto (cuánto gastó),
                        comparado con los demás clientes.
                    </p>
                    @if (($segmentos['status'] ?? null) === 'ok')
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($segmentos['resumen'] as $s)
                                <div class="rounded-lg border border-[#475569] bg-[#1E293B]/40 p-4">
                                    <div class="flex items-baseline justify-between">
                                        <div class="font-bold text-[#F1F5F9]">{{ $s['segmento'] }}</div>
                                        <div class="text-sm text-[#A0AEC0]">{{ $s['clientes'] }} {{ $s['clientes'] === 1 ? 'cliente' : 'clientes' }}</div>
                                    </div>
                                    <div class="mt-1 text-xs text-[#A0AEC0]">
                                        {{ $usd($s['monto_total']) }} en compras · última compra hace {{ $s['recencia_promedio_dias'] }} días en promedio
                                    </div>
                                    <p class="mt-2 text-sm text-[#CBD5E1]">{{ $s['accion'] }}</p>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-[#60A5FA]">Ver clientes</summary>
                                        <ul class="mt-2 max-h-56 space-y-1 overflow-y-auto text-xs">
                                            @foreach ($porSegmento[$s['segmento']] ?? [] as $c)
                                                <li class="flex justify-between gap-2 border-b border-[#475569]/40 py-1">
                                                    <span class="truncate text-[#F1F5F9]" title="{{ $c['email'] }}">{{ $c['cliente'] }}</span>
                                                    <span class="whitespace-nowrap text-[#A0AEC0]">{{ $c['frecuencia'] }} ped. · {{ $usd($c['monto']) }} · {{ $c['recencia_dias'] }} d</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-[#A0AEC0]">{{ $segmentos['motivo'] ?? 'No hay datos suficientes para segmentar clientes.' }}</p>
                    @endif
                </div>
        @endswitch
    </section>
</div>
@endsection
