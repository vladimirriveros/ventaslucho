<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    /** @use HasFactory<\Database\Factories\ProductoFactory> */
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'marca_id',
        'descripcion',
        'imagen',
        'precio_compra',
        'precio_venta',
        'porcentaje_ganancia',
        'stock_minimo',
        'stock_maximo',
        'unidad_medida',
        'norma',        // <-- Agregar
        'presion',      // <-- Agregar si decides incluirlo
        'diametro',     // <-- Agregar si decides incluirlo
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'porcentaje_ganancia' => 'decimal:2',
    ];

    // Relación con historial de precios
    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }
     // 👇 RELACIÓN CORRECTA CON MARCA
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }
    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class);
    }
    public function detalleCompras()
    {
        return $this->hasMany(DetalleCompra::class);
    }
    public function detalleSalidas()
    {
        return $this->hasMany(DetalleSalida::class);
    }

    public function configuracionesSucursal()
    {
        return $this->hasMany(ProductoSucursal::class);
    }

    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'producto_sucursal')
            ->withPivot(['activo', 'stock_minimo', 'stock_maximo'])
            ->withTimestamps();
    }

}
