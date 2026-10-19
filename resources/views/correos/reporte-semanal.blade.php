@php $usd = fn ($n) => '$' . number_format((float) $n, 2, ',', '.'); @endphp
<x-mail::message>
# Reporte semanal

Semana del **{{ $resumen['desde']->format('d/m/Y') }}** al **{{ $resumen['hasta']->format('d/m/Y') }}**.

<x-mail::table>
| Indicador | Valor |
|:--|--:|
| Ventas (pedidos pagados) | {{ $usd($resumen['ventas']) }} |
| Frente a la semana anterior | {{ $resumen['variacion'] === null ? 'sin datos' : ($resumen['variacion'] > 0 ? '+' : '') . number_format($resumen['variacion'], 1, ',', '.') . ' %' }} |
| Pedidos pagados | {{ $resumen['pedidos'] }} |
| Ticket promedio | {{ $usd($resumen['ticket']) }} |
| Pedidos pendientes hoy | {{ $resumen['pedidosPendientes'] }} |
| Pagos por verificar hoy | {{ $resumen['pagosPorVerificar'] }} |
</x-mail::table>

@if ($resumen['top']->isNotEmpty())
## Más vendidos

<x-mail::table>
| Producto | Unidades |
|:--|--:|
@foreach ($resumen['top'] as $p)
| {{ $p->nombre }} | {{ (int) $p->unidades }} |
@endforeach
</x-mail::table>
@endif

@if ($resumen['stockBajo']->isNotEmpty())
## Stock en o por debajo del mínimo

<x-mail::table>
| Producto | Stock | Mínimo |
|:--|--:|--:|
@foreach ($resumen['stockBajo']->take(15) as $p)
| {{ $p->nombre }} | {{ $p->stock }} | {{ $p->stock_minimo }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('admin.reports.index')">
Ver reportes completos
</x-mail::button>

Recibes este correo porque tienes activo el aviso «Reporte semanal». Puedes desactivarlo en Mi cuenta del panel.
</x-mail::message>
