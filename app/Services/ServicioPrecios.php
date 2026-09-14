<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ServicioAuditoria;
use GuzzleHttp\Client;
use Throwable;

/**
 * ServicioPrecios: centraliza todos los cálculos relacionados con precios,
 * conversiones y validaciones de mínimo para ventas al mayor.
 */
class ServicioPrecios
{
    /**
     * Obtener el precio unitario y total según tipo y cantidad.
     * @param Product $product
     * @param int $cantidad
     * @param string $tipo 'detal' o 'mayor'
     * @return array ['unit_price' => decimal, 'total' => decimal, 'currency' => 'USD' (por convención)]
     */
    public static function obtenerPrecio(Product $product, int $cantidad, string $tipo = 'detal'): array
    {
        $unit = $tipo === 'mayor' ? $product->precio_mayor : $product->precio_detal;
        $total = bcmul((string)$unit, (string)$cantidad, 2);

        return [
            'unit_price' => (float) $unit,
            'total' => (float) $total,
            'currency' => 'USD',
        ];
    }

    /**
     * Valida en servidor que un carrito marcado como "al mayor" cumple el mínimo
     * ya sea por monto en USD o por unidades totales.
     *
     * $cart debe tener la forma:
     * [
     *   'items' => [ ['product_id'=>1,'cantidad'=>2,'unit_price'=>12.00], ... ],
     *   'is_wholesale' => true
     * ]
     *
     * Nunca confiar en unit_price que venga del cliente para cálculos definitivos;
     * en su lugar se recalculan los precios unitarios desde DB.
     *
     * Retorna null si válido, o array con 'message' y 'missing' datos si inválido.
     */
    public static function validarMinimoMayor(array $cart): ?array
    {
        if (empty($cart['is_wholesale'])) {
            return null; // no aplica
        }

        $config = Configuracion::instance();
        $minUsd = $config?->minimo_compra_mayor_usd ?? 50.00;
        $minUnits = $config?->minimo_unidades_mayor ?? 10;

        $totalUsd = 0.0;
        $totalUnits = 0;

        foreach ($cart['items'] ?? [] as $item) {
            $prod = Product::lockForUpdate()->find($item['product_id']);
            if (! $prod) {
                return ['message' => "Producto con id {$item['product_id']} no existe", 'missing' => null];
            }

            $totalUnits += (int) $item['cantidad'];
            // Recalcular precio unitario desde DB: si el ítem viene marcado 'mayor' usar precio_mayor sin volver a comparar contra min_cantidad_mayor
            if (isset($item['tipo']) && $item['tipo'] === 'mayor') {
                $precioUnit = $prod->precio_mayor;
            } else {
                $precioUnit = $item['cantidad'] >= ($prod->min_cantidad_mayor ?? $minUnits) ? $prod->precio_mayor : $prod->precio_detal;
            }
            $totalUsd += (float) bcmul((string)$precioUnit, (string)$item['cantidad'], 2);
        }

        // Comprobar si cumple por unidades o por monto
        if ($totalUnits >= $minUnits || $totalUsd >= $minUsd) {
            return null; // válido
        }

        // Calcular faltante en USD y en unidades
        $faltanteUsd = max(0.0, round($minUsd - $totalUsd, 2));
        $faltanteUnidades = max(0, $minUnits - $totalUnits);

        $message = 'El pedido marcado como venta al mayor no cumple el mínimo. ';
        $message .= "Faltan ";
        if ($faltanteUnidades > 0) {
            $message .= "{$faltanteUnidades} unidad(es) ";
            if ($faltanteUsd > 0) $message .= "y ";
        }
        if ($faltanteUsd > 0) {
            $message .= "US$ {$faltanteUsd} ";
        }
        $message .= "para alcanzar el mínimo (mínimo: US$ {$minUsd} o {$minUnits} unidades).";

        return [
            'message' => $message,
            'missing' => [
                'usd' => $faltanteUsd,
                'units' => $faltanteUnidades,
                'min_usd' => (float) $minUsd,
                'min_units' => (int) $minUnits,
            ],
        ];
    }

    /**
     * Convierte un monto en USD a Bolívares (Bs) usando la tasa BCV vigente
     * o a USDT usando tasa_binance.
     *
     * @param float $amount
     * @param string $to 'bs' o 'usdt'
     * @return float|null
     */
    public static function convertirFromUsd(float $amount, string $to = 'bs'): ?float
    {
        $config = Configuracion::instance();
        if ($to === 'bs') {
            $tasa = $config?->tasa_bcv;
        } else {
            $tasa = $config?->tasa_binance;
        }

        if (! $tasa || $tasa <= 0) {
            return null; // tasa no disponible
        }

        return (float) bcmul((string)$amount, (string)$tasa, 2);
    }

    /**
     * Actualiza la tasa BCV consultando la API pública de DolarApi (región Venezuela).
     * Usa el endpoint https://ve.dolarapi.com/v1/dolares/oficial y extrae el campo `promedio`
     * (o `venta`/`compra` si `promedio` no está disponible).
     *
     * Guarda la tasa en la configuración dentro de una transacción y registra auditoría
     * con los valores antes y después.
     *
     * En caso de fallo NO actualiza la configuración y retorna null (manejo de errores).
     */
    public static function actualizarTasaBcvDesdeBcv(string $endpoint = 'https://ve.dolarapi.com/v1/dolares/oficial'): ?float
    {
        try {
            $client = new Client(['timeout' => 10]);
            $res = $client->get($endpoint, ['headers' => ['Accept' => 'application/json']]);
            $body = (string) $res->getBody();
            $data = json_decode($body, true);

            if (! is_array($data)) {
                Log::warning('ServicioPrecios: respuesta inválida al consultar DolarApi VE.');
                return null;
            }

            // Estructura esperada: { "compra": number, "venta": number, "promedio": number, ... }
            $rate = null;
            if (isset($data['promedio']) && $data['promedio'] > 0) {
                $rate = (float) $data['promedio'];
            } elseif (isset($data['venta']) && $data['venta'] > 0) {
                $rate = (float) $data['venta'];
            } elseif (isset($data['compra']) && $data['compra'] > 0) {
                $rate = (float) $data['compra'];
            }

            if (! $rate || $rate <= 0) {
                Log::warning('ServicioPrecios: tasa obtenida inválida desde DolarApi VE.');
                return null;
            }

            // Guardar en configuración y auditar dentro de una transacción
            return DB::transaction(function () use ($rate) {
                $config = Configuracion::instance();
                $antes = $config ? $config->toArray() : null;

                if (! $config) {
                    $config = Configuracion::create([
                        'minimo_compra_mayor_usd' => 50.00,
                        'minimo_unidades_mayor' => 10,
                        'tasa_bcv' => $rate,
                    ]);

                    ServicioAuditoria::registrar('configuracion.tasa_bcv_creada', $config, $antes, $config->toArray());
                } else {
                    $config->tasa_bcv = $rate;
                    $config->save();

                    ServicioAuditoria::registrar('configuracion.tasa_bcv_actualizada', $config, $antes, $config->toArray());
                }

                return (float) $rate;
            });
        } catch (Throwable $e) {
            Log::error('Error actualizando tasa BCV desde DolarApi VE: ' . $e->getMessage());
            return null;
        }
    }
}
