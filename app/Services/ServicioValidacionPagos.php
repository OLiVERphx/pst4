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
     * Almacena el comprobante para un pago y guarda metadata en el modelo.
     * @param Payment $payment
     * @param UploadedFile $file
     * @return string path almacenado (relative to storage/app)
     */
    public function validarCoherencia(Payment $payment): array
    {
        $result = [
            'monto_coincide' => null,
            'fecha_valida' => null,
            'referencia_unica' => null,
            'nivel_riesgo_coherencia' => 'low',
        ];

        // Tolerancia configurable
        $tolerance = (float) config('pagos.tolerancia_monto', 0.50);

        // Monto
        if ($payment->monto_declarado === null) {
            $result['monto_coincide'] = null; // sin dato declarado
        } else {
            $decl = (float) $payment->monto_declarado;
            $expected = (float) $payment->monto;
            $result['monto_coincide'] = (abs($decl - $expected) <= $tolerance);
        }

        // Fecha: no puede ser futura ni anterior a la creación del pedido
        if ($payment->fecha_pago_declarada === null) {
            $result['fecha_valida'] = null;
        } else {
            try {
                $fecha = $payment->fecha_pago_declarada;
                $now = now();
                $orderCreated = $payment->order?->created_at;
                $valid = true;
                if ($fecha > $now) {
                    $valid = false;
                }
                if ($orderCreated && $fecha < $orderCreated) {
                    $valid = false;
                }
                $result['fecha_valida'] = $valid;
            } catch (\Throwable $e) {
                $result['fecha_valida'] = false;
            }
        }

        // Referencia: no repetida entre pedidos distintos
        if (empty($payment->numero_referencia)) {
            $result['referencia_unica'] = null;
        } else {
            $dupe = Payment::where('numero_referencia', $payment->numero_referencia)
                ->where('pedido_id', '!=', $payment->pedido_id)
                ->exists();
            $result['referencia_unica'] = !$dupe;
        }

        // Nivel de riesgo derivado solo de coherencia
        if ($result['monto_coincide'] === false) {
            $result['nivel_riesgo_coherencia'] = 'high';
        } elseif ($result['fecha_valida'] === false || $result['referencia_unica'] === false) {
            $result['nivel_riesgo_coherencia'] = 'medium';
        } else {
            $result['nivel_riesgo_coherencia'] = 'low';
        }

        return $result;
    }

    public function store(Payment $payment, UploadedFile $file): string
    {
        $check = $this->validarComprobante($file);
        $uuid = Str::uuid()->toString();
        $ext = $file->getClientOriginalExtension() ?: ($check['mime'] === 'application/pdf' ? 'pdf' : 'jpg');
        $filename = $uuid . '.' . $ext;
        $dir = 'private/receipts/' . $payment->pedido_id;
        Storage::disk('local')->putFileAs($dir, $file, $filename);
        $path = $dir . '/' . $filename;

        // Guardar metadata en Payment
        $payment->ruta_comprobante = $path;
        $payment->hash_comprobante = $check['hash'] ?? hash_file('sha256', $file->getRealPath());
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
        $payment->metadata_comprobante['nivel_riesgo'] = $finalRisk;

        $payment->save();

        return $path;
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
                $pedidoService->avanzarEstado($payment->order, $admin);
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
