@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-info">💸</span>
            <div class="info-box-content">
                <span class="info-box-text">Ventas (mes)</span>
                <span class="info-box-number">${{ number_format($stats['total_ventas_mes'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-success">🧾</span>
            <div class="info-box-content">
                <span class="info-box-text">Pedidos (mes)</span>
                <span class="info-box-number">{{ $stats['pedidos_mes'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning">👥</span>
            <div class="info-box-content">
                <span class="info-box-text">Clientes</span>
                <span class="info-box-number">{{ $stats['total_clientes'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-danger">⚠️</span>
            <div class="info-box-content">
                <span class="info-box-text">Stock bajo</span>
                <span class="info-box-number">{{ $stats['cantidad_stock_bajo'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Ventas por categoría</h3></div>
    <div class="card-body">
        @php $max = collect($stats['ventas_por_categoria'] ?? [])->max('total') ?? 1; @endphp
        @foreach($stats['ventas_por_categoria'] ?? [] as $cat)
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <div>{{ $cat['category'] }}</div>
                    <div>${{ number_format($cat['total'], 2) }}</div>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $max ? ($cat['total'] / $max * 100) : 0 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h3 class="card-title">Últimos pedidos</h3></div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Pedido</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                @foreach($stats['latest_orders'] ?? [] as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->numero_pedido }}</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td><span class="badge bg-secondary">{{ $order->estado }}</span></td>
                        <td>{{ $order->created_at }}</td>
                        <td><a href="#" class="btn btn-sm btn-primary">Ver</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
