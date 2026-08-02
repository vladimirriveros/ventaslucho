<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoSucursal extends Model
{
    protected $table = 'producto_sucursal';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'activo',
        'stock_minimo',
        'stock_maximo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
