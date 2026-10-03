<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use App\Services\ServicioRespaldos;
use App\Services\ServicioAuditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Livewire component para la gestión de respaldos de base de datos.
 * Exclusivo para superadmin (permiso 'respaldos.gestionar').
 */
class RespaldosTable extends Component
{
    public function mount()
    {
        Gate::authorize('respaldos.gestionar');
    }

    public function generar(ServicioRespaldos $servicio)
    {
        Gate::authorize('respaldos.gestionar');

        try {
            $info = $servicio->crearRespaldo();

            ServicioAuditoria::registrar(
                'respaldo.generado',
                null,
                null,
                [
                    'archivo' => $info['archivo'],
                    'tamano' => $info['tamano_humano'],
                ],
                Auth::id()
            );

            session()->flash('success', "Respaldo generado exitosamente: {$info['archivo']} ({$info['tamano_humano']})");
        } catch (\Throwable $e) {
            session()->flash('error', "Error al generar respaldo: {$e->getMessage()}");
        }
    }

    public function descargar(string $archivo, ServicioRespaldos $servicio)
    {
        Gate::authorize('respaldos.gestionar');

        ServicioAuditoria::registrar(
            'respaldo.descargado',
            null,
            ['archivo' => $archivo],
            null,
            Auth::id()
        );

        return redirect()->route('admin.respaldos.descargar', ['archivo' => $archivo]);
    }

    public function eliminar(string $archivo, ServicioRespaldos $servicio)
    {
        Gate::authorize('respaldos.gestionar');

        $exito = $servicio->eliminarRespaldo($archivo);

        if ($exito) {
            ServicioAuditoria::registrar(
                'respaldo.eliminado',
                null,
                ['archivo' => $archivo],
                null,
                Auth::id()
            );
            session()->flash('success', "Respaldo {$archivo} eliminado correctamente.");
        } else {
            session()->flash('error', "No se pudo eliminar el archivo {$archivo}.");
        }
    }

    public function render(ServicioRespaldos $servicio)
    {
        Gate::authorize('respaldos.gestionar');

        $respaldos = $servicio->listarRespaldos();

        return view('livewire.datatables.respaldos-table', [
            'respaldos' => $respaldos,
        ]);
    }
}
