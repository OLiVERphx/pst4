<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo BitacoraAuditoria.
 * Registro inmutable (append-only) de auditoría del sistema.
 * No permite actualizaciones ni eliminaciones directas desde la aplicación.
 */
class BitacoraAuditoria extends Model
{
    /**
     * Nombre de la tabla asociada en la base de datos.
     */
    protected $table = 'bitacora_auditoria';

    /**
     * Deshabilitar timestamps automáticos de Laravel ya que la tabla solo maneja created_at.
     */
    public $timestamps = false;

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad_tipo',
        'entidad_id',
        'valores_antes',
        'valores_despues',
        'ip_origen',
        'user_agent',
        'created_at',
    ];

    /**
     * Conversiones de tipos nativos.
     */
    protected $casts = [
        'valores_antes' => 'array',
        'valores_despues' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Protección de inmutabilidad: Evitar actualizaciones y eliminaciones accidentales.
     */
    protected static function booted(): void
    {
        static::updating(function ($model) {
            throw new \RuntimeException('Los registros de la bitácora de auditoría son de solo lectura y no pueden modificarse.');
        });

        static::deleting(function ($model) {
            throw new \RuntimeException('Los registros de la bitácora de auditoría son inmutables y no pueden eliminarse.');
        });
    }

    /**
     * Relación con el usuario que ejecutó la acción (si aplica).
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
