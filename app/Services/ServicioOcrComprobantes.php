<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Lectura OCR de comprobantes de pago (Pago Móvil y transferencias).
 *
 * Usa el binario Tesseract instalado en el servidor (config/pagos.php → ocr).
 * El formato de referencia es el comprobante del Banco de Venezuela (BDV),
 * pero los patrones son genéricos: etiqueta + valor ("Fecha: 16/10/2026",
 * "Operación: 007693346569", "12.000,00 Bs").
 *
 * El OCR nunca bloquea el flujo: si Tesseract no está instalado o falla, se
 * devuelve 'disponible' => false y el admin verifica a ojo como antes.
 */
class ServicioOcrComprobantes
{
    public function leer(string $rutaAbsoluta, ?string $mime): array
    {
        if (! config('pagos.ocr.habilitado', true)) {
            return ['disponible' => false, 'motivo' => 'OCR deshabilitado'];
        }
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return ['disponible' => false, 'motivo' => 'El OCR solo lee imágenes JPG o PNG'];
        }

        $tmp = null;
        try {
            $tmp = $this->preprocesar($rutaAbsoluta);
            $result = Process::timeout(30)->run([
                config('pagos.ocr.tesseract', 'tesseract'),
                $tmp ?? $rutaAbsoluta,
                'stdout',
                '-l', config('pagos.ocr.idioma', 'spa'),
                '--psm', '6',
            ]);

            if (! $result->successful()) {
                Log::warning('OCR comprobante falló: ' . trim($result->errorOutput()));
                return ['disponible' => false, 'motivo' => 'Tesseract no está instalado o falló'];
            }

            $texto = trim($result->output());

            return ['disponible' => true, 'texto' => $texto] + self::extraerCampos($texto);
        } catch (Throwable $e) {
            Log::warning('OCR comprobante no disponible: ' . $e->getMessage());
            return ['disponible' => false, 'motivo' => 'Tesseract no está instalado o falló'];
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Extrae los campos del texto OCR. Público y estático para probarlo sin Tesseract.
     */
    public static function extraerCampos(string $texto): array
    {
        $t = preg_replace('/[ \t]+/u', ' ', $texto);

        $campos = [
            'tipo' => null,
            'monto_bs' => null,
            'fecha' => null,
            'operacion' => null,
            'identificacion' => null,
            'banco' => null,
            'nombre' => null,
        ];

        if (preg_match('/pago\s*m[oó0]vil/iu', $t)) {
            $campos['tipo'] = 'pagomovil';
        } elseif (preg_match('/transferencia/iu', $t)) {
            $campos['tipo'] = 'transferencia';
        }

        // Monto: número con decimales junto a "Bs" (antes o después). El OCR a veces
        // confunde la coma decimal con un punto, por eso se aceptan ambos.
        $num = '(\d{1,3}(?:[.,\s]\d{3})*[.,]\d{2})';
        if (preg_match("/{$num}\s*Bs\b/iu", $t, $m) || preg_match("/Bs\.?\s*{$num}/iu", $t, $m)
            || preg_match("/Monto\s*:?\s*{$num}/iu", $t, $m)) {
            $campos['monto_bs'] = self::normalizarMonto($m[1]);
        }

        if (preg_match('/(\d{2})[\/\-.](\d{2})[\/\-.](\d{4})/', $t, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            $campos['fecha'] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/(?:Operaci[oó0]n|Referencia|Ref\.?|N[°º]\s*de\s*operaci[oó]n)\s*:?\s*(\d{6,20})/iu', $t, $m)) {
            $campos['operacion'] = $m[1];
        }

        if (preg_match('/Identificaci[oó0]n\s*:?\s*([VEJGP]?[-\s]?\d{6,9})\b/iu', $t, $m)) {
            $campos['identificacion'] = strtoupper(preg_replace('/[\s-]/', '', $m[1]));
        }

        if (preg_match('/\b(01\d{2}|0[2-9]\d{2})\s*[-–—]\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ .]{2,})/u', $t, $m)) {
            // Con el código basta: el nombre se toma de la lista oficial (el OCR lo deforma más)
            $campos['banco'] = $m[1] . ' - ' . (config("pagos.bancos.{$m[1]}") ?? trim($m[2]));
        }

        if (preg_match('/Nombre\s*:?\s*([^\n\r]{3,80})/iu', $t, $m)) {
            $campos['nombre'] = trim($m[1]);
        }

        return $campos;
    }

    /** "146.800,00" | "146.800.00" | "146,800.00" → 146800.00 */
    public static function normalizarMonto(string $valor): float
    {
        $v = preg_replace('/\s/', '', $valor);
        $entero = preg_replace('/[.,]/', '', substr($v, 0, -3));

        return (float) ($entero . '.' . substr($v, -2));
    }

    /**
     * Prepara la imagen para Tesseract: escala de grises, invierte si el fondo es
     * oscuro (los comprobantes BDV son texto blanco sobre fondo oscuro) y amplía
     * las capturas pequeñas. Devuelve la ruta temporal o null si GD no la abre.
     */
    private function preprocesar(string $ruta): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $img = @imagecreatefromstring((string) file_get_contents($ruta));
        if (! $img) {
            return null;
        }

        imagefilter($img, IMG_FILTER_GRAYSCALE);

        $w = imagesx($img);
        $h = imagesy($img);
        $suma = 0;
        $muestras = 0;
        for ($x = 0; $x < $w; $x += max(1, intdiv($w, 40))) {
            for ($y = 0; $y < $h; $y += max(1, intdiv($h, 40))) {
                $suma += imagecolorat($img, $x, $y) & 0xFF;
                $muestras++;
            }
        }
        if ($muestras && ($suma / $muestras) < 128) {
            // Tema oscuro: el texto es lo más claro de la imagen (también el monto,
            // que va en blanco sobre un recuadro gris). Se binariza dejando solo
            // los píxeles claros como texto negro sobre fondo blanco.
            $negro = imagecolorallocate($img, 0, 0, 0);
            $blanco = imagecolorallocate($img, 255, 255, 255);
            for ($x = 0; $x < $w; $x++) {
                for ($y = 0; $y < $h; $y++) {
                    imagesetpixel($img, $x, $y, (imagecolorat($img, $x, $y) & 0xFF) > 170 ? $negro : $blanco);
                }
            }
        }

        if ($w < 1200) {
            $escala = 1200 / $w;
            $img = imagescale($img, 1200, (int) round($h * $escala), IMG_BICUBIC) ?: $img;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
        imagepng($img, $tmp);
        imagedestroy($img);

        return $tmp;
    }
}
