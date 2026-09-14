<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'ultimo_acceso',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $guard_name = 'web';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    /* public function getNameAttribute(): string
    {
        //return $this->name;
        return '';
    } */

    public function role()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'usuario_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeClientes($query)
    {
        return $query->whereHas('role', function ($query) {
            $query->where('nombre', 'cliente');
        });
    }

    public function adminlte_desc()
    {
        //return $this->name;
        return 'Zambrano Cell';
    }
}
