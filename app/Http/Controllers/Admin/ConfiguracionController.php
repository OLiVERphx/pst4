<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Configuracion;
use App\Services\ServicioAuditoria;

/**
 * Controlador para la edición de parámetros globales del sistema.
 * Sólo usuarios con permiso 'configuracion.editar' pueden actualizar.
 */
class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:configuracion.editar')->only(['update']);
    }

    /**
     * Actualiza la configuración global.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'minimo_compra_mayor_usd' => 'nullable|numeric|min:0',
            'minimo_unidades_mayor' => 'nullable|integer|min:0',
            'tasa_bcv' => 'nullable|numeric|min:0',
            'tasa_binance' => 'nullable|numeric|min:0',
        ]);

        $config = Configuracion::first();
        $antes = $config ? $config->toArray() : null;

        if (! $config) {
            $config = Configuracion::create($data + ['updated_by' => Auth::id()]);
        } else {
            $config->fill($data + ['updated_by' => Auth::id()]);
            $config->save();
        }

        // Registrar auditoría si hubo cambios
        ServicioAuditoria::registrar('configuracion.actualizada', $config, $antes, $config->toArray());

        return response()->json(['message' => 'Configuración actualizada correctamente', 'config' => $config]);
    }
}
