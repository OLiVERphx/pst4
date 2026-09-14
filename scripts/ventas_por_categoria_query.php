<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('items_pedido')
    ->join('products', 'items_pedido.producto_id', '=', 'products.id')
    ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
    ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
    ->whereIn('pedidos.estado', ['entregado','pago_verificado','procesando','enviado'])
    ->select('categorias.nombre as categoria', DB::raw('SUM(items_pedido.subtotal) as total'))
    ->groupBy('categorias.id', 'categorias.nombre')
    ->orderByDesc('total')
    ->get();

echo json_encode($rows->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
