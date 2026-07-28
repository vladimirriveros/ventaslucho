<?php

namespace App\Models;

use App\Services\CodigoNegocioService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    // Especificar el nombre correcto de la tabla
    protected $table = 'cotizaciones';  // ← Importante: con 'e' al final

    protected $fillable = [
        'codigo',
        'sucursal_id',
        'user_id',
        'cliente_id',
        'fecha',
        'valida_hasta',
        'subtotal',
        'descuento',
        'total',
        'observaciones',
        'estado'
    ];

    protected $casts = [
        'fecha' => 'date',
        'valida_hasta' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCotizacion::class);
    }

    public function venta()
    {
        return $this->hasOne(Venta::class);
    }

    // Generar código secuencial protegido contra operaciones simultáneas.
    public static function generarCodigo(): string
    {
        return app(CodigoNegocioService::class)->siguiente('cotizaciones', 'COT');
    }
}
