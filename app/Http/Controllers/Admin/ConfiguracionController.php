<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Configuracion;
use App\Services\ServicioAuditoria;
use App\Services\ServicioPrecios;

/**
 * Controlador para la edición de parámetros globales del sistema.
 * El permiso 'configuracion.editar' se exige en la ruta (routes/web.php).
 */
class ConfiguracionController extends Controller
{
    /**
     * Actualiza la configuración global.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'minimo_compra_mayor_usd' => 'nullable|numeric|min:0',
            'minimo_unidades_mayor' => 'nullable|integer|min:0',
            'tasa_bcv' => 'nullable|numeric|gt:0',
            'fecha_tasa_bcv' => 'nullable|date|before_or_equal:today',
            'tasa_binance' => 'nullable|numeric|min:0',
        ]);

        // La tasa BCV va al historial por fecha (para validar pagos en Bs con la tasa del día del pago)
        $tasaBcv = $data['tasa_bcv'] ?? null;
        $fechaTasa = $data['fecha_tasa_bcv'] ?? now()->toDateString();
        unset($data['tasa_bcv'], $data['fecha_tasa_bcv']);

        $config = Configuracion::first();
        $antes = $config ? $config->toArray() : null;

        if (! $config) {
            $config = Configuracion::create($data + ['updated_by' => Auth::id()]);
        } else {
            $config->fill($data + ['updated_by' => Auth::id()]);
            $config->save();
        }

        ServicioAuditoria::registrar('configuracion.actualizada', $config, $antes, $config->toArray());

        if ($tasaBcv !== null && (float) $tasaBcv !== (float) ServicioPrecios::tasaBcvEn($fechaTasa)) {
            ServicioPrecios::registrarTasaBcv((float) $tasaBcv, $fechaTasa, 'manual', Auth::id());
        }

        return back()->with('success', 'Configuración actualizada correctamente');
    }
}
