<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servicio para validar y almacenar comprobantes de pago.
 * Comentarios en español, código en inglés.
 */
class ServicioValidacionPagos
{
    /**
     * Valida el comprobante subido y devuelve un array con checks.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function validarComprobante(UploadedFile $file): array
    {
        $result = [
            'mime_valido' => false,
            'tamano_valido' => false,
            'exif_limpio' => true,
            'hash_unico' => false,
            'no_duplicado' => true,
            'resultado_general' => false,
            'nivel_riesgo' => 'high',
        ];

        // MIME real
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file->getPathname());
        } catch (\Throwable $e) {
            $mime = $file->getClientMimeType() ?? '';
        }

        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        $result['mime_valido'] = in_array($mime, $allowed, true);

        // Tamaño
        $size = $file->getSize();
        $result['tamano_valido'] = ($size !== false) && ($size <= 5 * 1024 * 1024);

        // EXIF limpio (solo para imágenes)
        $result['exif_limpio'] = true;
        if (strpos($mime, 'image/') === 0 && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($file->getPathname(), 'ANY_TAG', true);
                if (is_array($exif)) {
                    // Buscar clave Software en los bloques EXIF
                    foreach ($exif as $block) {
                        if (is_array($block) && isset($block['Software'])) {
                            $software = strtolower((string) $block['Software']);
                            if (Str::contains($software, ['photoshop', 'gimp', 'lightroom', 'irfanview'])) {
                                $result['exif_limpio'] = false;
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // No bloquear si falla la lectura
                $result['exif_limpio'] = true;
            }
        }

        // Hash y unicidad
        $hash = @hash_file('sha256', $file->getPathname());
        $result['hash_unico'] = $hash ? !Payment::where('hash_comprobante', $hash)->exists() : false;

        // No duplicado (heurística: buscar por nombre original en ruta_comprobante)
        $originalName = $file->getClientOriginalName();
        $result['no_duplicado'] = !Payment::where('ruta_comprobante', 'like', '%' . $originalName . '%')->exists();

        // Resultado general
        $result['resultado_general'] = $result['mime_valido'] && $result['tamano_valido'] && $result['exif_limpio'] && $result['hash_unico'] && $result['no_duplicado'];

        if (!$result['mime_valido'] || !$result['tamano_valido']) {
            $result['nivel_riesgo'] = 'high';
        } elseif (!$result['exif_limpio'] || !$result['hash_unico'] || !$result['no_duplicado']) {
            $result['nivel_riesgo'] = 'medium';
        } else {
            $result['nivel_riesgo'] = 'low';
        }

        // Añadir metadatos útiles
        $result['hash'] = $hash;
        $result['mime'] = $mime;
        $result['size'] = $size;

        return $result;
    }

    /**
     * Almacena el comprobante en storage y actualiza el modelo Payment.
     *
     * @param Payment $payment
     * @param UploadedFile $file
     * @return string (ruta relativa dentro de storage/app)
     */
    public function store(Payment $payment, UploadedFile $file): string
    {
        $dir = 'private/receipts/' . $payment->pedido_id;
        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Guardar archivo
        $path = $file->storeAs($dir, $filename);

        // Calcular hash y guardar metadata
        $fullPath = storage_path('app/' . $path);
        $hash = @hash_file('sha256', $fullPath);

        $payment->ruta_comprobante = $path;
        $payment->hash_comprobante = $hash;
        $payment->metadata_comprobante = [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
        $payment->save();

        return $path;
    }

    /**
     * Aprueba el pago: marca validado y avanza el pedido.
     *
     * @param Payment $payment
     * @param \App\Models\User $admin
     * @return void
     */
    public function approve(Payment $payment, $admin): void
    {
        $payment->estado = 'valid';
        $payment->verificado_por = $admin->id ?? null;
        $payment->verificado_en = now();
        $payment->save();

        // Avanzar el pedido
        try {
            $pedido = $payment->order;
            if ($pedido) {
                $pedidosService = new ServicioPedidos();
                $pedidosService->avanzarEstado($pedido, $admin);
            }
        } catch (\Throwable $e) {
            Log::error('Error advancing order after payment approval: ' . $e->getMessage());
        }
    }

    /**
     * Rechaza el pago: marca invalid y guarda motivo. Notifica (log).
     *
     * @param Payment $payment
     * @param \App\Models\User $admin
     * @param string $reason
     * @return void
     */
    public function reject(Payment $payment, $admin, string $reason): void
    {
        $payment->estado = 'invalid';
        $payment->motivo_rechazo = $reason;
        $payment->verificado_por = $admin->id ?? null;
        $payment->verificado_en = now();
        $payment->save();

        Log::info("Payment {$payment->id} rejected by user {$admin->id}: {$reason}");
    }
}
