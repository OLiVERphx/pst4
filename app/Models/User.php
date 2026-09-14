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
    ];

    // Casts para tipos nativos
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        // 'bloqueado' indica que el cliente puede ver catálogo pero no completar checkout
        'bloqueado' => 'boolean',
        'password' => 'hashed',
    ];

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
