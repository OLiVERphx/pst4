<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Historial de ventas de DEMOSTRACIÓN para la analítica predictiva (ml-service).
 *
 * El sistema aún no tiene ventas reales acumuladas. Estos datos son simulados y se
 * identifican sin ambigüedad: pedidos con número DEMO-AAAA-NNNN y clientes con correo
 * @demo.local. No descuentan stock. Se eliminan con --limpiar.
 */
class GenerarVentasDemo extends Command
{
    protected $signature = 'demo:ventas
        {--semanas=40 : Semanas de historial a generar}
        {--clientes=60 : Clientes de demostración}
        {--pedidos-semana=25 : Pedidos promedio por semana}
        {--semilla=42 : Semilla aleatoria (mismo valor = mismos datos)}
        {--limpiar : Elimina los datos de demostración y termina}
        {--force : Permite ejecutarlo en producción}';

    protected $description = 'Genera (o elimina) un historial de ventas de demostración para la analítica predictiva';

    private const PREFIJO = 'DEMO-';
    private const DOMINIO = '@demo.local';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Estás en producción: los datos de demostración se mezclarían con ventas reales. Usa --force si de verdad quieres hacerlo.');
            return self::FAILURE;
        }

        $this->limpiar();
        if ($this->option('limpiar')) {
            $this->info('Datos de demostración eliminados.');
            return self::SUCCESS;
        }

        mt_srand((int) $this->option('semilla'));
        $productos = Product::where('activo', true)->get(['id', 'nombre', 'precio_detal']);
        if ($productos->isEmpty()) {
            $this->error('No hay productos activos. Ejecuta primero los seeders.');
            return self::FAILURE;
        }

        $semanas = max(12, (int) $this->option('semanas'));
        $clientes = $this->crearClientes(max(30, (int) $this->option('clientes')), $semanas);
        $perfiles = $this->perfilesDeProducto($productos);

        $pedidos = 0;
        DB::transaction(function () use ($semanas, $clientes, $perfiles, &$pedidos) {
            $inicio = now()->startOfWeek()->subWeeks($semanas);
            for ($w = 0; $w < $semanas; $w++) {
                $lunes = $inicio->copy()->addWeeks($w);
                $estacional = $this->factorEstacional($lunes);
                $activos = array_values(array_filter($clientes, fn ($c) => $w >= $c['desde'] && $w <= $c['hasta']));
                if (! $activos) {
                    continue;
                }

                $numPedidos = $this->poisson((float) $this->option('pedidos-semana') * $estacional);
                for ($k = 0; $k < $numPedidos; $k++) {
                    $cliente = $this->elegirPonderado($activos, 'peso');
                    $lineas = $this->lineasDePedido($perfiles, $estacional);
                    if ($lineas) {
                        $this->insertarPedido($cliente['id'], $lineas, $lunes->copy()->addDays(mt_rand(0, 6))->setTime(mt_rand(9, 18), mt_rand(0, 59)));
                        $pedidos++;
                    }
                }
            }
        });

        $this->info("Listo: {$pedidos} pedidos de demostración en {$semanas} semanas, " . count($clientes) . ' clientes ' . self::DOMINIO . '.');
        $this->line('Son datos SIMULADOS. Para eliminarlos: php artisan demo:ventas --limpiar');

        return self::SUCCESS;
    }

    /**
     * Clientes con comportamientos distintos para que el RFM tenga algo que encontrar:
     * frecuentes y recientes, que dejaron de comprar, nuevos y de una sola compra antigua.
     */
    private function crearClientes(int $n, int $semanas): array
    {
        $rolCliente = Role::where('nombre', 'cliente')->value('id');
        $nombres = ['José', 'María', 'Luis', 'Ana', 'Carlos', 'Daniela', 'Pedro', 'Gabriela', 'Jesús', 'Andreína', 'Miguel', 'Valentina'];
        $apellidos = ['Pérez', 'González', 'Rodríguez', 'Briceño', 'Uzcátegui', 'Matheus', 'Rivas', 'Paredes', 'Araujo', 'Valera'];
        $tipos = [
            // [proporción, semana desde (fracción), semana hasta (fracción), peso de compra]
            'frecuente' => [0.20, 0.0, 1.0, 6],
            'regular' => [0.30, 0.0, 1.0, 2],
            'en_riesgo' => [0.20, 0.0, 0.55, 5],
            'nuevo' => [0.15, 0.90, 1.0, 1],
            'perdido' => [0.15, 0.0, 0.25, 0.5],
        ];

        $clientes = [];
        $password = Hash::make(Str::random(32));
        $i = 0;
        foreach ($tipos as $tipo => [$proporcion, $desde, $hasta, $peso]) {
            $cantidad = $tipo === 'perdido' ? $n - $i : (int) round($n * $proporcion);
            for ($j = 0; $j < $cantidad && $i < $n; $j++, $i++) {
                $user = User::create([
                    'rol_id' => $rolCliente,
                    'name' => $nombres[$i % count($nombres)],
                    'apellido' => $apellidos[intdiv($i, count($nombres)) % count($apellidos)] . ' (demo)',
                    'email' => 'cliente' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT) . self::DOMINIO,
                    'password' => $password,
                    'activo' => true,
                ]);
                $user->assignRole('cliente');
                $clientes[] = [
                    'id' => $user->id,
                    'desde' => (int) floor($desde * ($semanas - 1)),
                    'hasta' => (int) ceil($hasta * ($semanas - 1)),
                    'peso' => $peso,
                ];
            }
        }

        return $clientes;
    }

    /** Popularidad por producto: algunos se venden todas las semanas y otros rara vez. */
    private function perfilesDeProducto($productos): array
    {
        return $productos->values()->map(function ($p, $i) {
            return [
                'id' => $p->id,
                'precio' => (float) $p->precio_detal,
                // Los primeros tercios: alta, media y baja rotación (demanda intermitente)
                'peso' => match ($i % 3) { 0 => 6, 1 => 2.5, default => 0.6 },
            ];
        })->all();
    }

    /** Diciembre y temporada escolar venden más. */
    private function factorEstacional(Carbon $lunes): float
    {
        return match ((int) $lunes->month) {
            12 => 1.6,
            9 => 1.25,
            1 => 0.75,
            default => 1.0,
        };
    }

    private function lineasDePedido(array $perfiles, float $estacional): array
    {
        $lineas = [];
        $n = mt_rand(1, 100) <= 70 ? 1 : mt_rand(2, 3);
        for ($k = 0; $k < $n; $k++) {
            $p = $this->elegirPonderado($perfiles, 'peso');
            $lineas[$p['id']] = [
                'producto_id' => $p['id'],
                'cantidad' => ($lineas[$p['id']]['cantidad'] ?? 0) + (mt_rand(1, 100) <= 80 ? 1 : mt_rand(2, 4)),
                'precio' => $p['precio'],
            ];
        }

        return array_values($lineas);
    }

    private function insertarPedido(int $clienteId, array $lineas, Carbon $fecha): void
    {
        static $secuencia = 0;
        $secuencia++;
        $total = round(array_sum(array_map(fn ($l) => $l['cantidad'] * $l['precio'], $lineas)), 2);
        $delivery = mt_rand(1, 100) <= 35;

        $pedidoId = DB::table('pedidos')->insertGetId([
            'numero_pedido' => self::PREFIJO . $fecha->year . '-' . str_pad((string) $secuencia, 5, '0', STR_PAD_LEFT),
            'usuario_id' => $clienteId,
            'tipo' => 'detal',
            'estado' => $delivery ? 'entregado' : 'completado',
            'tipo_entrega' => $delivery ? 'delivery' : 'retiro',
            'canal' => 'online',
            'subtotal' => $total,
            'descuento' => 0,
            'total' => $total,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        DB::table('items_pedido')->insert(array_map(fn ($l) => [
            'pedido_id' => $pedidoId,
            'producto_id' => $l['producto_id'],
            'cantidad' => $l['cantidad'],
            'precio_unitario' => $l['precio'],
            'subtotal' => round($l['cantidad'] * $l['precio'], 2),
        ], $lineas));

        DB::table('pagos')->insert([
            'pedido_id' => $pedidoId,
            'metodo' => ['pagomovil', 'transferencia', 'binance'][mt_rand(0, 2)],
            'monto' => $total,
            'moneda' => 'USD',
            'estado' => 'valido',
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }

    private function limpiar(): void
    {
        DB::transaction(function () {
            $pedidos = DB::table('pedidos')->where('numero_pedido', 'like', self::PREFIJO . '%')->pluck('id');
            foreach ($pedidos->chunk(500) as $lote) {
                DB::table('pagos')->whereIn('pedido_id', $lote)->delete();
                DB::table('items_pedido')->whereIn('pedido_id', $lote)->delete();
                DB::table('pedidos')->whereIn('id', $lote)->delete();
            }
            User::where('email', 'like', '%' . self::DOMINIO)->get()->each(function (User $u) {
                $u->roles()->detach();
                DB::table('carts')->where('usuario_id', $u->id)->delete();
                $u->delete();
            });
        });
    }

    private function poisson(float $lambda): int
    {
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);

        return $k - 1;
    }

    private function elegirPonderado(array $opciones, string $campo): array
    {
        $total = array_sum(array_column($opciones, $campo));
        $r = mt_rand() / mt_getrandmax() * $total;
        foreach ($opciones as $o) {
            $r -= $o[$campo];
            if ($r <= 0) {
                return $o;
            }
        }

        return end($opciones);
    }
}
