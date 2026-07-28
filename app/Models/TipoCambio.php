<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCambio extends Model
{
    use HasFactory;

    protected $table = 'tipo_cambios';

    protected $fillable = [
        'precio_dolar',
        'fecha',
        'estado',
        'is_oficial'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => 'boolean',
        'is_oficial' => 'boolean'
    ];

    // Obtener el tipo de cambio oficial
    public static function getOficial()
    {
        return self::where('is_oficial', true)->first();
    }

    // Obtener el tipo de cambio activo (para ventas)
    public static function getActivo()
    {
        return self::where('estado', true)->first();
    }
}
