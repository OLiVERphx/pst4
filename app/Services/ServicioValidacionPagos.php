<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\User;
use App\Services\ServicioPedidos;

/**
 * Servicio para validar y almacenar comprobantes de pago.
 * Comentarios en español.
 */
class ServicioValidacionPagos
{
    /**
     * Valida un archivo subido (MIME, tamaño, EXIF, hash) y devuelve detalles.
     * @param UploadedFile $file
     * @return array
     */
    public function validarComprobante(UploadedFile $file): array
    {
        $result = [
            'mime_valido' => false,
            'tamano_valido' => false,
            'exif_limpio' => true,
            'hash_unico' => true,
            'no_duplicado' => true, // requiere contexto de referencia; se verifica en store cuando exista Payment
            'resultado_general' => false,
            'nivel_riesgo' => 'low',
        ];

        // MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = @finfo_file($finfo, $file->getRealPath());
        @finfo_close($finfo);
        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        $result['mime_valido'] = in_array($mime, $allowed, true);

        // Tamaño (<= 5MB)
        $result['tamano_valido'] = ($file->getSize() <= 5 * 1024 * 1024);

        // EXIF (si es imagen)
        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            try {
                $exif = @exif_read_data($file->getRealPath(), null, true);
                if ($exif && is_array($exif)) {
                    // Buscar campos que indiquen edición
                    $software = null;
                    foreach ($exif as $section => $data) {
                        if (is_array($data)) {
                            if (isset($data['Software'])) { $software = $data['Software']; break; }
                            if (isset($data['ProcessingSoftware'])) { $software = $data['ProcessingSoftware']; break; }
                            if (isset($data['ImageDescription'])) { $software = $data['ImageDescription']; }
                        }
                    }
                    if (!empty($software)) {
                        $susp = ['Photoshop', 'GIMP', 'Lightroom', 'IrfanView'];
                        foreach ($susp as $bad) {
                            if (stripos($software, $bad) !== false) {
                                $result['exif_limpio'] = false;
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // No bloquear la validación por errores de EXIF
                Log::debug('EXIF read error: ' . $e->getMessage());
            }
        }

        // Hash SHA-256
        $hash = @hash_file('sha256', $file->getRealPath());
        if ($hash) {
            $exists = Payment::where('hash_comprobante', $hash)->exists();
            $result['hash_unico'] = !$exists;
        } else {
            $result['hash_unico'] = false;
        }

        // no_duplicado: sin contexto de numero_referencia no se puede asegurar aquí
        $result['no_duplicado'] = true;

        // Nivel de riesgo
        if (!($result['mime_valido'] && $result['tamano_valido'])) {
            $result['nivel_riesgo'] = 'high';
        } elseif (!($result['exif_limpio'] && $result['hash_unico'] && $result['no_duplicado'])) {
            $result['nivel_riesgo'] = 'medium';
        } else {
            $result['nivel_riesgo'] = 'low';
        }

        $result['resultado_general'] = ($result['mime_valido'] && $result['tamano_valido'] && $result['exif_limpio'] && $result['hash_unico'] && $result['no_duplicado']);

        // Metadatos adicionales
        $result['hash'] = $hash;
        $result['mime'] = $mime;
        $result['size'] = $file->getSize();

        return $result;
    }

    /**
     * Compara lo declarado por el cliente y lo leído del comprobante (OCR) contra
     * el pedido: monto (en Bs con la tasa BCV del día del pago para Pago Móvil y
     * transferencias), fecha, referencia única y coincidencia OCR/declarado.
     */
    public function validarCoherencia(Payment $payment): array
    {
        $result = [
            'monto_coincide' => null,
            'fecha_valida' => null,
            'referencia_unica' => null,
            'ocr_coincide' => null,
            'ocr_diferencias' => [],
            'destino_coincide' => null,
            'moneda_comparada' => null,
            'tasa_bcv' => null,
            'monto_bs_esperado' => null,
            'monto_comprobante' => null,
            'diferencia_pct' => null,
            'nivel_riesgo_coherencia' => 'low',
        ];

        $ocr = $payment->metadata_comprobante['ocr'] ?? null;
        $ocr = ($ocr['disponible'] ?? false) ? $ocr : null;
        $autocompletados = $payment->metadata_comprobante['autocompletado'] ?? [];

        // Fecha del pago: la del comprobante manda; si no se pudo leer, la declarada
        $fechaPago = $ocr['fecha'] ?? $payment->fecha_pago_declarada?->toDateString();

        // Fecha: solo el día (los comprobantes no traen hora). No puede ser futura
        // ni anterior a la creación del pedido (con margen configurable).
        if ($fechaPago) {
            $hoy = now()->toDateString();
            $minimo = $payment->order?->created_at
                ? $payment->order->created_at->copy()->subDays((int) config('pagos.dias_pago_antes_pedido', 1))->toDateString()
                : null;
            $result['fecha_valida'] = $fechaPago <= $hoy && (! $minimo || $fechaPago >= $minimo);
        }

        // Monto. Pago Móvil y transferencias se pagan en bolívares: el monto del
        // comprobante debe ser total USD × tasa BCV vigente el día del pago.
        $esBs = in_array($payment->metodo, ['transferencia', 'pagomovil'], true)
            || in_array(strtoupper((string) $payment->moneda_declarada), ['VES', 'BS'], true);
        $tasa = $esBs ? ServicioPrecios::tasaBcvEn($fechaPago ?? $payment->created_at ?? now()) : null;

        if ($esBs && $tasa) {
            $esperado = (float) bcmul((string) $payment->monto, (string) $tasa, 2);
            $comprobante = $ocr['monto_bs'] ?? ($payment->monto_declarado !== null ? (float) $payment->monto_declarado : null);

            $result['moneda_comparada'] = 'VES';
            $result['tasa_bcv'] = $tasa;
            $result['monto_bs_esperado'] = $esperado;
            $result['monto_comprobante'] = $comprobante;

            if ($comprobante !== null && $esperado > 0) {
                $result['diferencia_pct'] = round(abs($comprobante - $esperado) / $esperado * 100, 2);
                $result['monto_coincide'] = $result['diferencia_pct'] <= (float) config('pagos.tolerancia_bs_porcentaje', 1.0);
            }
        } elseif ($payment->monto_declarado !== null) {
            // USD / USDT (Binance) o sin tasa BCV cargada: comparación directa en dólares
            $result['moneda_comparada'] = 'USD';
            $result['monto_comprobante'] = (float) $payment->monto_declarado;
            $tolerance = (float) config('pagos.tolerancia_monto', 0.50);
            $result['monto_coincide'] = abs((float) $payment->monto_declarado - (float) $payment->monto) <= $tolerance;
        }

        // Referencia: no repetida entre pedidos distintos
        $referencia = $payment->numero_referencia ?: ($ocr['operacion'] ?? null);
        if ($referencia) {
            $dupe = Payment::where('numero_referencia', $referencia)
                ->where('pedido_id', '!=', $payment->pedido_id)
                ->exists();
            $result['referencia_unica'] = !$dupe;
        }

        // Lo declarado por el cliente contra lo leído en el comprobante
        // (los campos autocompletados desde el OCR no se comparan consigo mismos)
        if ($ocr) {
            $comparados = 0;

            if ($esBs && $ocr['monto_bs'] !== null && $payment->monto_declarado !== null && ! in_array('monto_declarado', $autocompletados, true)) {
                $comparados++;
                if (abs((float) $payment->monto_declarado - $ocr['monto_bs']) > max(0.01, $ocr['monto_bs'] * 0.005)) {
                    $result['ocr_diferencias'][] = 'monto';
                }
            }
            if ($ocr['fecha'] && $payment->fecha_pago_declarada && ! in_array('fecha_pago_declarada', $autocompletados, true)) {
                $comparados++;
                if ($ocr['fecha'] !== $payment->fecha_pago_declarada->toDateString()) {
                    $result['ocr_diferencias'][] = 'fecha';
                }
            }
            if ($ocr['operacion'] && $payment->numero_referencia && ! in_array('numero_referencia', $autocompletados, true)) {
                $comparados++;
                // Se acepta que el cliente escriba solo los últimos dígitos
                $decl = preg_replace('/\D/', '', $payment->numero_referencia);
                if ($decl === '' || ! str_ends_with($ocr['operacion'], $decl)) {
                    $result['ocr_diferencias'][] = 'referencia';
                }
            }
            if ($comparados > 0) {
                $result['ocr_coincide'] = empty($result['ocr_diferencias']);
            }
        }

        // ¿El dinero fue a la tienda? Solo si el OCR leyó un destino con forma válida
        // (puede leer mal; por eso un desacuerdo es alerta y no "sospechoso")
        if ($ocr && ! empty($ocr['destino'])) {
            $result['destino_coincide'] = $this->destinoEsDeLaTienda($payment->metodo, $ocr['destino']);
        }

        if ($result['monto_coincide'] === false) {
            $result['nivel_riesgo_coherencia'] = 'high';
        } elseif ($result['fecha_valida'] === false || $result['referencia_unica'] === false || $result['ocr_coincide'] === false
            || $result['destino_coincide'] === false) {
            $result['nivel_riesgo_coherencia'] = 'medium';
        }

        return $result;
    }

    /**
     * Almacena el comprobante para un pago y guarda metadata en el modelo.
     * @param Payment $payment
     * @param UploadedFile $file
     * @return string path almacenado (relative to storage/app)
     */
    public function store(Payment $payment, UploadedFile $file): string
    {
        $check = $this->validarComprobante($file);
        $uuid = Str::uuid()->toString();
        $ext = $file->getClientOriginalExtension() ?: ($check['mime'] === 'application/pdf' ? 'pdf' : 'jpg');
        $filename = $uuid . '.' . $ext;
        // El disco 'local' ya apunta a storage/app/private
        $dir = 'receipts/' . $payment->pedido_id;
        Storage::disk('local')->putFileAs($dir, $file, $filename);
        $path = $dir . '/' . $filename;

        // Guardar metadata en Payment
        $payment->ruta_comprobante = $path;
        $payment->hash_comprobante = $check['hash'] ?? hash_file('sha256', $file->getRealPath());

        // Leer el comprobante (OCR) y completar lo que el cliente no declaró
        $check['ocr'] = app(ServicioOcrComprobantes::class)->leer(Storage::disk('local')->path($path), $check['mime'] ?? null);
        $check['autocompletado'] = $this->autocompletarDesdeOcr($payment, $check['ocr']);
        $payment->metadata_comprobante = $check;

        // Verificar numero_referencia si existe en el modelo
        if (!empty($payment->numero_referencia)) {
            $dupe = Payment::where('numero_referencia', $payment->numero_referencia)
                ->where('id', '!=', $payment->id)
                ->exists();
            $payment->metadata_comprobante = array_merge($payment->metadata_comprobante ?? [], ['no_duplicado' => !$dupe]);
        }

        // Validaciones de coherencia adicionales (monto, fecha, referencia)
        $coh = $this->validarCoherencia($payment);
        $payment->metadata_comprobante = array_merge($payment->metadata_comprobante ?? [], ['coherencia' => $coh]);
        $payment->tasa_bcv_aplicada = $coh['tasa_bcv'];
        $payment->monto_bs_esperado = $coh['monto_bs_esperado'];

        // Si la coherencia indica riesgo alto, marcar nivel_riesgo en metadata y el estado como sospechoso
        $existingRisk = $payment->metadata_comprobante['nivel_riesgo'] ?? ($check['nivel_riesgo'] ?? 'low');
        $finalRisk = $existingRisk;
        if (($coh['nivel_riesgo_coherencia'] ?? 'low') === 'high') {
            $finalRisk = 'high';
            // No rechazar automáticamente: solo marcar sospechoso para alertar al admin
            $payment->estado = 'sospechoso';
        } elseif (($coh['nivel_riesgo_coherencia'] ?? 'low') === 'medium' && $finalRisk !== 'high') {
            $finalRisk = 'medium';
        }
        $payment->metadata_comprobante = array_merge($payment->metadata_comprobante ?? [], ['nivel_riesgo' => $finalRisk]);

        $payment->save();

        return $path;
    }

    /**
     * Compara el destino leído del comprobante con los datos de cobro de la tienda.
     * null = no se puede decidir (lectura con forma inválida o tienda sin datos).
     */
    private function destinoEsDeLaTienda(string $metodo, string $destino): ?bool
    {
        $datos = \App\Models\Configuracion::datosPago();

        if ($metodo === 'pagomovil') {
            $leido = preg_replace('/\D/', '', $destino);
            $tienda = $datos['pagomovil']['telefono'];
            if (strlen($leido) === 12 && str_starts_with($leido, '58')) {
                $leido = '0' . substr($leido, 2);
            }
            if (! preg_match('/^04\d{9}$/', $leido) || $tienda === '') {
                return null;
            }
            return $leido === $tienda;
        }

        if ($metodo === 'transferencia') {
            // Formato enmascarado del banco: primeros y últimos dígitos visibles
            if (! preg_match('/^(\d{4})\*+(\d{4})$/', $destino, $m) || $datos['transferencia']['numero_cuenta'] === '') {
                return null;
            }
            $cuenta = $datos['transferencia']['numero_cuenta'];
            return str_starts_with($cuenta, $m[1]) && str_ends_with($cuenta, $m[2]);
        }

        return null;
    }

    /**
     * Rellena con lo leído del comprobante los campos que el cliente dejó vacíos.
     * Devuelve la lista de campos completados para mostrarlos como "leído del comprobante".
     */
    private function autocompletarDesdeOcr(Payment $payment, array $ocr): array
    {
        if (! ($ocr['disponible'] ?? false)) {
            return [];
        }

        $completados = [];
        $mapa = [
            'numero_referencia' => $ocr['operacion'] ?? null,
            'monto_declarado' => $ocr['monto_bs'] ?? null,
            'fecha_pago_declarada' => $ocr['fecha'] ?? null,
            'banco_origen' => $ocr['banco'] ?? null,
            'cedula_pagador' => $ocr['identificacion'] ?? null,
        ];
        foreach ($mapa as $campo => $valor) {
            if ($valor !== null && ($payment->{$campo} === null || $payment->{$campo} === '')) {
                $payment->{$campo} = $valor;
                $completados[] = $campo;
            }
        }
        if (in_array('monto_declarado', $completados, true)) {
            $payment->moneda_declarada = 'VES';
        }

        return $completados;
    }

    /**
     * Aprueba un pago: marca como valid y avanza el pedido.
     */
    public function approve(Payment $payment, User $admin): void
    {
        $antes = [
            'estado' => $payment->estado,
            'verificado_por' => $payment->verificado_por,
            'verificado_en' => $payment->verificado_en ? (string) $payment->verificado_en : null,
        ];

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $admin) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first() ?? $payment;
            $lockedPayment->estado = 'valido';
            $lockedPayment->verificado_por = $admin->id;
            $lockedPayment->verificado_en = now();
            $lockedPayment->save();

            $payment->estado = $lockedPayment->estado;
            $payment->verificado_por = $lockedPayment->verificado_por;
            $payment->verificado_en = $lockedPayment->verificado_en;
        });

        $despues = [
            'estado' => $payment->estado,
            'verificado_por' => $payment->verificado_por,
            'verificado_en' => $payment->verificado_en ? (string) $payment->verificado_en : null,
        ];

        // Verificación explícita de auto-revisión (Vendedor aprobando su propia venta local)
        if ($payment->order && $payment->order->usuario_id === $admin->id) {
            $despues['auto_revision'] = true;
        }

        ServicioAuditoria::registrar('pago.aprobado', $payment, $antes, $despues, $admin->id);

        // Avanzar estado del pedido asociado
        try {
            $pedidoService = app(ServicioPedidos::class);
            if ($payment->order) {
                $pedidoService->confirmarPago($payment->order, $admin);
            }
        } catch (\Throwable $e) {
            Log::error('Error advancing order after payment approval: ' . $e->getMessage());
        }

        Log::info('Payment approved', ['payment_id' => $payment->id, 'order_id' => $payment->pedido_id, 'by' => $admin->id]);
    }

    /**
     * Rechaza un pago y registra motivo.
     */
    public function reject(Payment $payment, User $admin, string $reason): void
    {
        $antes = [
            'estado' => $payment->estado,
            'verificado_por' => $payment->verificado_por,
            'verificado_en' => $payment->verificado_en ? (string) $payment->verificado_en : null,
            'motivo_rechazo' => $payment->motivo_rechazo,
        ];

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $admin, $reason) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first() ?? $payment;
            $lockedPayment->estado = 'invalido';
            $lockedPayment->verificado_por = $admin->id;
            $lockedPayment->verificado_en = now();
            $lockedPayment->motivo_rechazo = $reason;
            $lockedPayment->save();

            $payment->estado = $lockedPayment->estado;
            $payment->verificado_por = $lockedPayment->verificado_por;
            $payment->verificado_en = $lockedPayment->verificado_en;
            $payment->motivo_rechazo = $lockedPayment->motivo_rechazo;
        });

        $despues = [
            'estado' => $payment->estado,
            'verificado_por' => $payment->verificado_por,
            'verificado_en' => $payment->verificado_en ? (string) $payment->verificado_en : null,
            'motivo_rechazo' => $payment->motivo_rechazo,
        ];

        ServicioAuditoria::registrar('pago.rechazado', $payment, $antes, $despues, $admin->id);

        // Log / placeholder para notificación al cliente
        Log::info('Payment rejected', ['payment_id' => $payment->id, 'order_id' => $payment->pedido_id, 'by' => $admin->id, 'reason' => $reason]);
    }
}
