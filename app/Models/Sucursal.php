<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    /** @use HasFactory<\Database\Factories\SucursalFactory> */
    use HasFactory;

    protected $table = 'sucursals';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];


    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function salidas()
    {
        return $this->hasMany(Salida::class);
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class);
    }

    public function inventarioSucuralLotes()
    {
        return $this->hasMany(InventarioSucuralLote::class);
    }
    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class);
    }
    public function getTotalInventarioAttribute()
    {
        return $this->inventarioSucuralLotes()->sum('cantidad_en_sucursal');
    }
    public function configuracionesProducto()
    {
        return $this->hasMany(ProductoSucursal::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_sucursal')
            ->withPivot(['activo', 'stock_minimo', 'stock_maximo'])
            ->withTimestamps();
    }

}
