<?php

namespace App\Models;

use App\Services\CodigoNegocioService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use SoftDeletes;

    protected $table = 'ventas';  // ← Correcto

    protected $fillable = [
        'codigo',
        'sucursal_id',
        'user_id',
        'caja_id',
        'cliente_id',
        'cotizacion_id',
        'fecha',
        'tipo',
        'subtotal',
        'descuento',
        'total',
        'pagado',
        'pendiente',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'pendiente' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    // Generar código secuencial protegido contra operaciones simultáneas.
    public static function generarCodigo(): string
    {
        return app(CodigoNegocioService::class)->siguiente('ventas', 'VEN');
    }

    // Actualizar saldo pendiente
    public function actualizarSaldo()
    {
        $pagado = $this->pagos()->sum('monto');
        $this->pagado = $pagado;
        $this->pendiente = max(0, round((float) $this->total - (float) $pagado, 2));

        if ($this->estado !== 'anulada') {
            $this->estado = $this->pendiente <= 0 ? 'pagada' : 'pendiente';
        }

        $this->save();

        // Actualizar saldo del cliente si es crédito
        if ($this->cliente && $this->tipo === 'credito') {
            $this->cliente->saldo_pendiente = $this->cliente->ventas()
                ->where('estado', '!=', 'anulada')
                ->sum('pendiente');
            $this->cliente->save();
        }
    }
}
