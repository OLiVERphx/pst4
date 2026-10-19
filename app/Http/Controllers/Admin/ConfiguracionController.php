<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Configuracion;
use App\Services\ServicioAuditoria;
use App\Services\ServicioPrecios;
use Illuminate\Validation\Rule;

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

    /**
     * Datos de la empresa y de cobro que ve el cliente en el checkout.
     * Un método de pago online solo se ofrece si tiene sus datos cargados.
     */
    public function actualizarDatosTienda(Request $request)
    {
        // Normalizar antes de validar: la gente escribe cuentas y teléfonos con guiones o espacios
        $soloDigitos = fn ($v) => $v === null ? null : preg_replace('/\D/', '', (string) $v);
        $documento = fn ($v) => $v === null || $v === '' ? null : strtoupper(preg_replace('/[\s.]/', '', (string) $v));
        $request->merge([
            'empresa' => array_merge($request->input('empresa', []), [
                'rif' => $documento($request->input('empresa.rif')),
            ]),
            'pago' => [
                'transferencia' => array_merge($request->input('pago.transferencia', []), [
                    'numero_cuenta' => $soloDigitos($request->input('pago.transferencia.numero_cuenta')) ?: null,
                    'documento' => $documento($request->input('pago.transferencia.documento')),
                ]),
                'pagomovil' => array_merge($request->input('pago.pagomovil', []), [
                    'telefono' => $soloDigitos($request->input('pago.pagomovil.telefono')) ?: null,
                    'documento' => $documento($request->input('pago.pagomovil.documento')),
                ]),
                'binance' => array_merge($request->input('pago.binance', []), [
                    'pay_id' => $soloDigitos($request->input('pago.binance.pay_id')) ?: null,
                ]),
            ],
        ]);

        $bancos = array_keys(config('pagos.bancos'));
        $rif = 'regex:/^[VEJGP]-?\d{6,9}(-?\d)?$/';

        $data = $request->validate([
            'empresa.nombre' => 'required|string|max:120',
            'empresa.rif' => ['required', $rif],
            'empresa.telefono' => 'nullable|string|max:30',
            'empresa.direccion' => 'nullable|string|max:255',

            'pago.transferencia.banco' => ['nullable', 'required_with:pago.transferencia.numero_cuenta', Rule::in($bancos)],
            'pago.transferencia.tipo_cuenta' => 'nullable|in:corriente,ahorro',
            'pago.transferencia.numero_cuenta' => ['nullable', 'required_with:pago.transferencia.banco', 'regex:/^\d{20}$/',
                function ($attr, $value, $fail) use ($request) {
                    $banco = $request->input('pago.transferencia.banco');
                    if ($value && $banco && ! str_starts_with($value, $banco)) {
                        $fail("El número de cuenta debe comenzar con el código del banco ({$banco}).");
                    }
                }],
            'pago.transferencia.titular' => 'nullable|required_with:pago.transferencia.numero_cuenta|string|max:120',
            'pago.transferencia.documento' => ['nullable', 'required_with:pago.transferencia.numero_cuenta', $rif],

            'pago.pagomovil.banco' => ['nullable', 'required_with:pago.pagomovil.telefono', Rule::in($bancos)],
            'pago.pagomovil.telefono' => ['nullable', 'required_with:pago.pagomovil.banco', 'regex:/^04(12|14|16|22|24|26)\d{7}$/'],
            'pago.pagomovil.documento' => ['nullable', 'required_with:pago.pagomovil.telefono', $rif],

            'pago.binance.pay_id' => ['nullable', 'regex:/^\d{6,12}$/'],
            'pago.binance.correo' => 'nullable|email|max:150',
        ], [
            'pago.transferencia.numero_cuenta.regex' => 'El número de cuenta debe tener 20 dígitos.',
            'pago.pagomovil.telefono.regex' => 'El teléfono de pago móvil debe ser un celular venezolano (0412, 0414, 0416, 0422, 0424 o 0426).',
            'pago.binance.pay_id.regex' => 'El Binance Pay ID debe tener entre 6 y 12 dígitos.',
            'empresa.rif.regex' => 'El RIF debe tener el formato J-12345678-9.',
            'pago.transferencia.documento.regex' => 'La cédula o RIF del titular no tiene un formato válido.',
            'pago.pagomovil.documento.regex' => 'La cédula o RIF de pago móvil no tiene un formato válido.',
        ], [
            'empresa.nombre' => 'nombre de la empresa',
            'empresa.rif' => 'RIF de la empresa',
            'pago.transferencia.banco' => 'banco de la cuenta',
            'pago.transferencia.numero_cuenta' => 'número de cuenta',
            'pago.transferencia.titular' => 'titular de la cuenta',
            'pago.transferencia.documento' => 'cédula o RIF del titular',
            'pago.pagomovil.banco' => 'banco de pago móvil',
            'pago.pagomovil.telefono' => 'teléfono de pago móvil',
            'pago.pagomovil.documento' => 'cédula o RIF de pago móvil',
        ]);

        $config = Configuracion::first() ?? new Configuracion();
        $antes = ['datos_empresa' => $config->datos_empresa, 'datos_pago' => $config->datos_pago];

        $config->datos_empresa = $data['empresa'];
        $config->datos_pago = [
            'transferencia' => array_filter($data['pago']['transferencia'] ?? [], fn ($v) => $v !== null),
            'pagomovil' => array_filter($data['pago']['pagomovil'] ?? [], fn ($v) => $v !== null),
            'binance' => array_filter($data['pago']['binance'] ?? [], fn ($v) => $v !== null),
        ];
        $config->updated_by = Auth::id();
        $config->save();

        ServicioAuditoria::registrar('configuracion.datos_tienda_actualizados', $config, $antes,
            ['datos_empresa' => $config->datos_empresa, 'datos_pago' => $config->datos_pago]);

        return back()->with('success', 'Datos de la empresa y de pago actualizados.');
    }
}
