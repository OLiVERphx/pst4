<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InventoryMovement;
use App\Models\StockAlert;

class SeederDatosDemo extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Admin user
        $admin = User::where('email', 'admin@swworld.com')->first();

        // Clients (seeded by SeederUsuarios)
        $clienteRole = DB::table('roles')->where('nombre', 'cliente')->value('id');
        $clients = $clienteRole ? User::where('rol_id', $clienteRole)->get() : User::where('email', '!=', 'admin@swworld.com')->get();
        if ($clients->isEmpty()) {
            $clients = User::where('email', '!=', 'admin@swworld.com')->get();
        }

        // Products
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->info('No products found, skipping demo data seeding.');
            return;
        }

        // Orders specification
        $ordersSpec = [
            ['estado' => 'entregado', 'tipo' => 'detal', 'items' => 3],
            ['estado' => 'entregado', 'tipo' => 'detal', 'items' => 2],
            ['estado' => 'pago_verificado', 'tipo' => 'mayor', 'items' => 5],
            ['estado' => 'pago_subido', 'tipo' => 'detal', 'items' => 1],
            ['estado' => 'pendiente', 'tipo' => 'detal', 'items' => 2],
            ['estado' => 'cancelado', 'tipo' => 'detal', 'items' => 1],
        ];

        foreach ($ordersSpec as $spec) {
            $client = $clients->random();

            $order = Order::create([
                'numero_pedido' => Order::generarNumeroPedido(),
                'usuario_id' => $client->id,
                'tipo' => $spec['tipo'],
                'estado' => $spec['estado'],
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
                'entrega_nombre' => trim(($client->name ?? '') . ' ' . ($client->apellido ?? '')),
                'entrega_telefono' => $client->telefono ?? '0000',
                'entrega_direccion' => $client->direccion ?? 'Sin dirección',
                'entrega_ciudad' => $client->ciudad ?? 'Ciudad',
                'entrega_notas' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $selected = $products->shuffle()->take(min($spec['items'], $products->count()));
            $subtotal = 0;

            foreach ($selected as $prod) {
                $qty = rand(1, 3);
                $unit = $spec['tipo'] === 'mayor' ? ($prod->precio_mayor ?? $prod->precio_detal) : $prod->precio_detal;
                $unit = $unit ?? 0;
                $line = round($unit * $qty, 2);

                OrderItem::create([
                    'pedido_id' => $order->id,
                    'producto_id' => $prod->id,
                    'cantidad' => $qty,
                    'precio_unitario' => $unit,
                    'subtotal' => $line,
                ]);

                $subtotal += $line;
            }

            $order->subtotal = $subtotal;
            $order->descuento = 0;
            $order->total = $subtotal;
            $order->save();

            // Payments for certain states
            if (in_array($spec['estado'], ['pago_subido', 'pago_verificado'])) {
                $metodos = ['transferencia', 'binance', 'pagomovil'];
                $metodo = $metodos[array_rand($metodos)];

                Payment::create([
                    'pedido_id' => $order->id,
                    'metodo' => $metodo,
                    'monto' => $order->total,
                    'moneda' => 'USD',
                    'numero_referencia' => strtoupper('REF-' . substr(md5($order->numero_pedido), 0, 8)),
                    'ruta_comprobante' => null,
                    'hash_comprobante' => null,
                    'metadata_comprobante' => [],
                    'estado' => $spec['estado'] === 'pago_subido' ? 'pendiente' : 'valido',
                    'verificado_por' => $spec['estado'] === 'pago_verificado' ? ($admin->id ?? null) : null,
                    'verificado_en' => $spec['estado'] === 'pago_verificado' ? $now : null,
                    'motivo_rechazo' => null,
                ]);
            }
        }

        // Inventory movements (10 random)
        $adminId = $admin->id ?? ($clients->first()->id ?? 1);
        for ($i = 0; $i < 10; $i++) {
            $prod = $products->random();
            $tipo = rand(0, 1) ? 'entrada' : 'salida';
            $cantidad = rand(1, 5);
            $cantidad_anterior = (int)$prod->stock;
            $cantidad_nueva = $tipo === 'entrada' ? $cantidad_anterior + $cantidad : max(0, $cantidad_anterior - $cantidad);

            InventoryMovement::create([
                'producto_id' => $prod->id,
                'usuario_id' => $adminId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'cantidad_anterior' => $cantidad_anterior,
                'cantidad_nueva' => $cantidad_nueva,
                'referencia' => $tipo === 'entrada' ? 'FAC-2026-' . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT) : 'PED-' . Order::generarNumeroPedido(),
                'notas' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // update product stock
            $prod->stock = $cantidad_nueva;
            $prod->save();
        }

        // Generate stock alerts for low stock
        $products->fresh();
        foreach (Product::all() as $prod) {
            if ($prod->stock <= $prod->stock_minimo) {
                $tipo = $prod->stock == 0 ? 'sin_stock' : 'low_stock';
                StockAlert::firstOrCreate(
                    ['producto_id' => $prod->id, 'tipo' => $tipo],
                    ['leido' => false, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $this->command->info('SeederDatosDemo completed.');
    }
}
