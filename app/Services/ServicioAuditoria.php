<?php

namespace App\Services;

use App\Models\BitacoraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para el registro y gestión de auditoría transversal en el sistema.
 * Registra acciones sensibles de manera append-only y resiliente ante fallos de BD.
 */
class ServicioAuditoria
{
    /**
     * Registra una acción en la bitácora de auditoría.
     *
     * Captura automáticamente usuario autenticado, dirección IP y User-Agent si están disponibles.
     * Si ocurre algún fallo al guardar en la bitácora, captura el error crítico en logs sin
     * interrumpir el flujo principal de la aplicación.
     *
     * @param string $accion Identificador de la acción (ej: 'pago.aprobado', 'inventario.ajustado')
     * @param Model|null $entidad Modelo afectado (opcional)
     * @param array|null $antes Estado o atributos antes del cambio (opcional)
     * @param array|null $despues Estado o atributos después del cambio (opcional)
     * @param int|null $usuarioId ID explícito del usuario (si no se proporciona, se toma el autenticado)
     * @return BitacoraAuditoria|null
     */
    public static function registrar(
        string $accion,
        ?Model $entidad = null,
        ?array $antes = null,
        ?array $despues = null,
        ?int $usuarioId = null
    ): ?BitacoraAuditoria {
        try {
            // Resolver ID del usuario (explícito, autenticado o nulo para acciones del sistema/invitados)
            $userId = $usuarioId ?? (Auth::check() ? Auth::id() : null);

            // Obtener datos del request HTTP si existen
            $ipOrigen = null;
            $userAgent = null;

            if (function_exists('request') && request()) {
                $ipOrigen = request()->ip();
                $userAgent = request()->userAgent();
            }

            if (empty($userAgent) && app()->runningInConsole()) {
                $userAgent = 'CLI/Console';
            }

            // Datos de la entidad
            $entidadTipo = $entidad ? get_class($entidad) : null;
            $entidadId = $entidad ? $entidad->getKey() : null;

            return BitacoraAuditoria::create([
                'usuario_id' => $userId,
                'accion' => $accion,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'valores_antes' => $antes,
                'valores_despues' => $despues,
                'ip_origen' => $ipOrigen,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Registrar error crítico en los logs del sistema sin interrumpir la operación de negocio
            Log::critical('Fallo crítico al registrar auditoría en bitacora_auditoria: ' . $e->getMessage(), [
                'accion' => $accion,
                'entidad_tipo' => $entidad ? get_class($entidad) : null,
                'entidad_id' => $entidad?->getKey(),
                'usuario_id' => $usuarioId ?? Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
