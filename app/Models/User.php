<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Support\IndiceCiego;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Campos asignables (coinciden con columnas de la BD)
    // Campos asignables (coinciden con columnas de la BD)
    // Se agrega 'bloqueado' para controles administrativos que impidan checkout
    protected $fillable = [
        'rol_id',
        'name',
        'apellido',
        'email',
        'cedula',
        'telefono',
        'direccion',
        'ciudad',
        'estado',
        'activo',
        'bloqueado',
        'ultimo_acceso',
        'password',
    ];

    // Campos ocultos en arrays/JSON
    protected $hidden = [
        'password',
        'remember_token',
        'cedula_hash',
        'telefono_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_ultimo_paso',
    ];

    // Casts para tipos nativos
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        'preferencias_notificacion' => 'array',
        // 'bloqueado' indica que el cliente puede ver catálogo pero no completar checkout
        'bloqueado' => 'boolean',
        'password' => 'hashed',
        // Datos personales cifrados en la base (AES-256 con APP_KEY)
        'cedula' => 'encrypted',
        'telefono' => 'encrypted',
        'direccion' => 'encrypted',
        // Verificación en dos pasos (ver App\Services\ServicioDosFactores)
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    // Al asignar cédula o teléfono se cifran y se calcula su índice ciego en el mismo
    // paso (no con eventos: los seeders corren sin eventos de modelo). La lectura la
    // descifra el cast 'encrypted'. Ver App\Support\IndiceCiego.
    protected function cedula(): Attribute
    {
        return Attribute::make(set: fn ($valor) => [
            'cedula' => self::cifrar($valor),
            'cedula_hash' => IndiceCiego::cedula($valor),
        ]);
    }

    protected function telefono(): Attribute
    {
        return Attribute::make(set: fn ($valor) => [
            'telefono' => self::cifrar($valor),
            'telefono_hash' => IndiceCiego::telefono($valor),
        ]);
    }

    private static function cifrar($valor): ?string
    {
        return $valor === null || $valor === '' ? null : Crypt::encryptString((string) $valor);
    }

    public function scopePorCedula($query, ?string $cedula)
    {
        return $query->where('cedula_hash', IndiceCiego::cedula($cedula) ?? '-');
    }

    public function scopePorTelefono($query, ?string $telefono)
    {
        return $query->where('telefono_hash', IndiceCiego::telefono($telefono) ?? '-');
    }

    // Relaciones
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeClientes($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('nombre', 'cliente');
        });
    }

    /**
     * Avisos del panel: tipo => [etiqueta, permiso necesario, descripción].
     * Cada usuario elige cuáles recibir en Configuración o en Mi cuenta.
     */
    public const NOTIFICACIONES = [
        'pedido_nuevo' => ['Nuevo pedido', 'pedidos.ver', 'Pedidos pendientes de atender, en la campana.'],
        'pago_recibido' => ['Pago recibido', 'pagos.ver', 'Comprobantes de pago por verificar, en la campana.'],
        'stock_bajo' => ['Stock bajo', 'inventario.ver', 'Productos en o por debajo de su stock mínimo, en la campana.'],
        'reporte_semanal' => ['Reporte semanal', 'reportes.ver', 'Resumen de ventas por correo cada lunes a las 7:45 a. m.'],
    ];

    /** Tipos de aviso que el usuario puede recibir según sus permisos. */
    public function notificacionesDisponibles(): array
    {
        return array_filter(self::NOTIFICACIONES, fn ($n) => $this->can($n[1]));
    }

    public function quiereNotificacion(string $tipo): bool
    {
        return isset($this->notificacionesDisponibles()[$tipo])
            && ($this->preferencias_notificacion[$tipo] ?? true);
    }

    /** Cierra las sesiones abiertas en otros equipos (tras cambiar la contraseña). */
    /** Roles que no pueden usar el panel sin verificación en dos pasos. */
    public const ROLES_CON_DOS_FACTORES = ['superadmin', 'admin'];

    public function tieneDosFactores(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    public function requiereDosFactores(): bool
    {
        return config('auth.dos_factores.obligatorio') && $this->hasAnyRole(self::ROLES_CON_DOS_FACTORES);
    }

    public function cerrarOtrasSesiones(string $sesionActual): void
    {
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->where('id', '!=', $sesionActual)
                ->delete();
        }
    }
}
