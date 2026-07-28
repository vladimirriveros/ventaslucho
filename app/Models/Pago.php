<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';  // ← Correcto

    protected $fillable = [
        'venta_id',
        'user_id',
        'caja_id',
        'banca_id',
        'fecha',
        'monto',
        'metodo_pago',
        'referencia',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function banca()
    {
        return $this->belongsTo(Banca::class);
    }
}
