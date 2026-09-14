<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'metodo',
        'monto',
        'moneda',
        'numero_referencia',
        'ruta_comprobante',
        'hash_comprobante',
        'metadata_comprobante',
        'estado',
        'verificado_por',
        'verificado_en',
        'motivo_rechazo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'metadata_comprobante' => 'array',
        'verificado_en' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'pedido_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeSospechoso($query)
    {
        return $query->where('estado', 'suspicious');
    }
}

