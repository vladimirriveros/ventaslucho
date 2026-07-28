<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';  // ← Correcto

    protected $fillable = [
        'sucursal_id',
        'user_id',
        'user_cierre_id',
        'fecha_apertura',
        'monto_inicial',
        'fecha_cierre',
        'monto_final',
        'monto_esperado',
        'diferencia',
        'estado',
        'observaciones_apertura',
        'observaciones_cierre'
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userCierre()
    {
        return $this->belongsTo(User::class, 'user_cierre_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    // Verificar si hay caja abierta en la sucursal
    public static function hayCajaAbierta($sucursalId)
    {
        return self::where('sucursal_id', $sucursalId)
            ->where('estado', 'abierta')
            ->exists();
    }

    // Obtener caja abierta actual
    public static function getCajaAbierta($sucursalId)
    {
        return self::where('sucursal_id', $sucursalId)
            ->where('estado', 'abierta')
            ->latest('fecha_apertura')
            ->first();
    }
    public function calcularEfectivoEsperado(): float
    {
        $ingresos = (float) $this->movimientos()
            ->where('tipo', 'ingreso')
            ->where('metodo_pago', 'efectivo')
            ->where('concepto', '!=', 'Apertura de caja')
            ->sum('monto');

        $egresos = (float) $this->movimientos()
            ->where('tipo', 'egreso')
            ->where('metodo_pago', 'efectivo')
            ->sum('monto');

        return round((float) $this->monto_inicial + $ingresos - $egresos, 2);
    }

}
