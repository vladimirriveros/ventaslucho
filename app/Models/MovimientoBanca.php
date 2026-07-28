<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoBanca extends Model
{
    use HasFactory;

    protected $table = 'movimientos_banca';

    protected $fillable = [
        'banca_id',
        'user_id',
        'caja_id',
        'tipo',
        'monto',
        'saldo_anterior',
        'saldo_nuevo',
        'referencia',
        'observaciones',
        'fecha'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function banca()
    {
        return $this->belongsTo(Banca::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }
}
