@extends('layouts.web')

@section('title','Pedido confirmado')

@section('content')
<div style="max-width:500px;margin:4rem auto;text-align:center">
  <div style="font-size:5rem;margin-bottom:1.5rem">✅</div>
  <h1 style="font-size:2rem;font-weight:900;margin-bottom:.8rem">¡Pedido enviado!</h1>
  <p style="color:var(--c-muted);margin-bottom:2rem;line-height:1.7">
    Tu pedido fue registrado. El equipo de Smartphone World verificará
    tu comprobante y te contactará para coordinar la entrega.
    Número de pedido: <strong style="color:var(--c-teal)">{{ $order->numero_pedido }}</strong>
  </p>
  <p style="color:var(--c-muted);font-size:.88rem;margin-bottom:2rem">
    1. Enviaste tu pedido con el comprobante<br>
    2. El administrador verifica el pago<br>
    3. Recibes confirmación y tu pedido se prepara<br>
    4. Coordinas la entrega o retiras en tienda
  </p>
  <a href="{{ route('web.home') }}" style="background:var(--c-blue);color:white;padding:.7rem 1.5rem;border-radius:10px;font-weight:700;display:inline-block">
    Volver al inicio
  </a>
</div>

<script>
// Vaciar carrito al confirmar pedido
localStorage.removeItem('sw_carrito');
</script>
@endsection
