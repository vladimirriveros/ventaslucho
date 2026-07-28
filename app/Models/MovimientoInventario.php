<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventarios';

    protected $fillable = [
        'producto_id',
        'lote_id',
        'sucursal_id',
        'user_id',
        'tipo_movimiento',
        'origen_tipo',
        'origen_id',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'fecha',
        'observaciones',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function origen()
    {
        return $this->morphTo(__FUNCTION__, 'origen_tipo', 'origen_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
