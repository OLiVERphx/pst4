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
    ];

    // Casts para tipos nativos
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        // 'bloqueado' indica que el cliente puede ver catálogo pero no completar checkout
        'bloqueado' => 'boolean',
        'password' => 'hashed',
        // Datos personales cifrados en la base (AES-256 con APP_KEY)
        'cedula' => 'encrypted',
        'telefono' => 'encrypted',
        'direccion' => 'encrypted',
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
}
