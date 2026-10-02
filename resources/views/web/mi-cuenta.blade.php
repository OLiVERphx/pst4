@extends('layouts.web')

@section('title', 'Mis pedidos')

@section('content')
<div class="section active">
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <span>Mis pedidos</span>
  </div>

  <div class="section-title">Mis pedidos</div>

  @if($pedidos->isEmpty())
    <div class="panel">
      <p style="color:var(--c-muted)">Todavía no tenés pedidos. <a href="{{ route('web.catalogo') }}">Explorar catálogo</a></p>
    </div>
  @else
    @foreach($pedidos as $pedido)
      <div class="panel" style="margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
          <div>
            <strong>{{ $pedido->numero_pedido }}</strong>
            <span style="color:var(--c-muted);font-size:.85rem"> — {{ $pedido->created_at->format('d/m/Y H:i') }}</span>
          </div>
          <div style="font-weight:600">
            {{ $estados[$pedido->estado] ?? ucfirst(str_replace('_',' ',$pedido->estado)) }}
          </div>
        </div>

        <div style="color:var(--c-muted);font-size:.9rem;margin-top:.3rem">
          {{ $pedido->tipo_entrega === 'delivery' ? '🚚 Delivery' : '🏪 Retiro en tienda' }}
          @if($pedido->payment)
            — Pago: {{ ucfirst($pedido->payment->metodo) }}
          @endif
          @if($pedido->tipo_entrega === 'delivery' && $pedido->fecha_estimada_entrega)
            — Llega: {{ \Carbon\Carbon::parse($pedido->fecha_estimada_entrega)->format('d/m/Y H:i') }}
          @endif
        </div>

        <div style="margin-top:.6rem">
          @foreach($pedido->items as $item)
            <div class="order-summary-item">
              <span>{{ $item->product->nombre ?? 'Producto eliminado' }} × {{ $item->cantidad }}</span>
              <span>${{ number_format($item->subtotal, 2) }}</span>
            </div>
          @endforeach
        </div>

        <div class="order-total-row">
          <span>Total</span>
          <span>${{ number_format($pedido->total, 2) }}</span>
        </div>
      </div>
    @endforeach

    <div style="margin-top:1rem">
      {{ $pedidos->links() }}
    </div>
  @endif
</div>
@endsection
