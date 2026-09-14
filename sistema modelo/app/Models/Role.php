<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'permisos',
    ];

    protected $casts = [
        'permisos' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
