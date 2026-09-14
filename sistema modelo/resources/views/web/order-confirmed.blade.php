@extends('layouts.web')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold">Order received</h2>
    <p class="mt-4">Thank you. Your order <strong>{{ $order->numero_pedido }}</strong> was created.</p>
    <div class="mt-4">Total: ${{ number_format($order->total, 2) }}</div>
</div>
@endsection