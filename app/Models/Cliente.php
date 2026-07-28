<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'nit',
        'email',
        'telefono',
        'direccion',
        'tipo',
        'limite_credito',
        'saldo_pendiente',
        'activo',
        'observaciones'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'limite_credito' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    // Scope para clientes activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para clientes de crédito
    public function scopeCredito($query)
    {
        return $query->where('tipo', 'credito');
    }

    // Scope para clientes regulares
    public function scopeRegular($query)
    {
        return $query->where('tipo', 'regular');
    }

    // Relación con ventas
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
