<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Banca extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'banco',
        'numero_cuenta',
        'nombre',
        'qr_code',
        'activa',
        'saldo_actual',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'saldo_actual' => 'decimal:2',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoBanca::class);
    }

    // Scope para obtener solo bancas activas
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // Scope para ordenar
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('banco')->orderBy('nombre');
    }

    // Registra un movimiento atómico y conserva el saldo anterior/nuevo para auditoría.
    public function registrarMovimiento($tipo, $monto, $userId, $cajaId = null, $referencia = null, $observaciones = null)
    {
        $monto = round((float) $monto, 2);
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        if (!in_array($tipo, ['carga', 'retiro', 'ajuste'], true)) {
            throw new \InvalidArgumentException('Tipo de movimiento bancario no válido.');
        }

        return DB::transaction(function () use ($tipo, $monto, $userId, $cajaId, $referencia, $observaciones) {
            $banca = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $saldoAnterior = round((float) $banca->saldo_actual, 2);

            $saldoNuevo = match ($tipo) {
                'carga' => $saldoAnterior + $monto,
                'retiro' => $saldoAnterior - $monto,
                'ajuste' => $monto,
            };

            if ($saldoNuevo < 0) {
                throw new \RuntimeException('Saldo insuficiente en la cuenta bancaria.');
            }

            $movimiento = $banca->movimientos()->create([
                'user_id' => $userId,
                'caja_id' => $cajaId,
                'tipo' => $tipo,
                'monto' => $monto,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => round($saldoNuevo, 2),
                'referencia' => $referencia,
                'observaciones' => $observaciones,
                'fecha' => now(),
            ]);

            $banca->update(['saldo_actual' => round($saldoNuevo, 2)]);
            $this->setRawAttributes($banca->fresh()->getAttributes(), true);

            return $movimiento;
        }, 3);
    }

}
