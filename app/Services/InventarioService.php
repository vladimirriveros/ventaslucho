<?php

namespace App\Services;

use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\ProductoSucursal;
use InvalidArgumentException;
use RuntimeException;

class InventarioService
{
    /**
     * Descuenta inventario con bloqueo pesimista. Debe llamarse dentro de una transacción.
     */
    public function disminuir(
        int $loteId,
        int $sucursalId,
        int $cantidad,
        ?int $userId = null,
        ?string $origenTipo = null,
        ?int $origenId = null,
        ?string $observaciones = null
    ): array {
        $this->validarCantidad($cantidad);

        $lote = Lote::query()->lockForUpdate()->findOrFail($loteId);
        $inventario = InventarioSucuralLote::query()
            ->where('lote_id', $loteId)
            ->where('sucursal_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            throw new RuntimeException('El lote no pertenece a la sucursal seleccionada.');
        }

        if ((int) $inventario->cantidad_en_sucursal < $cantidad || (int) $lote->cantidad_actual < $cantidad) {
            throw new RuntimeException(sprintf(
                'Stock insuficiente para el lote %s. Disponible: %d; solicitado: %d.',
                $lote->codigo_lote,
                (int) $inventario->cantidad_en_sucursal,
                $cantidad
            ));
        }

        $stockAnterior = (int) $inventario->cantidad_en_sucursal;
        $stockNuevo = $stockAnterior - $cantidad;

        $inventario->cantidad_en_sucursal = $stockNuevo;
        $inventario->save();

        $lote->cantidad_actual = (int) $lote->cantidad_actual - $cantidad;
        $lote->save();

        $movimiento = $this->registrarMovimiento(
            $lote,
            $sucursalId,
            'Salida',
            $cantidad,
            $stockAnterior,
            $stockNuevo,
            $userId,
            $origenTipo,
            $origenId,
            $observaciones
        );

        return compact('lote', 'inventario', 'movimiento');
    }

    /**
     * Incrementa inventario con bloqueo pesimista. Debe llamarse dentro de una transacción.
     */
    public function aumentar(
        int $loteId,
        int $sucursalId,
        int $cantidad,
        ?int $userId = null,
        ?string $origenTipo = null,
        ?int $origenId = null,
        ?string $observaciones = null
    ): array {
        $this->validarCantidad($cantidad);

        $lote = Lote::query()->lockForUpdate()->findOrFail($loteId);

        // Toda entrada habilita el producto únicamente en la sucursal que
        // recibe existencias. Esto evita alertas falsas en otras sucursales.
        ProductoSucursal::query()->updateOrCreate(
            [
                'producto_id' => $lote->producto_id,
                'sucursal_id' => $sucursalId,
            ],
            [
                'activo' => true,
            ]
        );

        $inventario = InventarioSucuralLote::query()
            ->where('lote_id', $loteId)
            ->where('sucursal_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            $inventario = InventarioSucuralLote::create([
                'lote_id' => $loteId,
                'sucursal_id' => $sucursalId,
                'cantidad_en_sucursal' => 0,
            ]);
        }

        $stockAnterior = (int) $inventario->cantidad_en_sucursal;
        $stockNuevo = $stockAnterior + $cantidad;

        $inventario->cantidad_en_sucursal = $stockNuevo;
        $inventario->save();

        $lote->cantidad_actual = (int) $lote->cantidad_actual + $cantidad;
        $lote->save();

        $movimiento = $this->registrarMovimiento(
            $lote,
            $sucursalId,
            'Entrada',
            $cantidad,
            $stockAnterior,
            $stockNuevo,
            $userId,
            $origenTipo,
            $origenId,
            $observaciones
        );

        return compact('lote', 'inventario', 'movimiento');
    }

    private function registrarMovimiento(
        Lote $lote,
        int $sucursalId,
        string $tipo,
        int $cantidad,
        int $stockAnterior,
        int $stockNuevo,
        ?int $userId,
        ?string $origenTipo,
        ?int $origenId,
        ?string $observaciones
    ): MovimientoInventario {
        return MovimientoInventario::create([
            'producto_id' => $lote->producto_id,
            'lote_id' => $lote->id,
            'sucursal_id' => $sucursalId,
            'user_id' => $userId,
            'tipo_movimiento' => $tipo,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'fecha' => now(),
            'observaciones' => $observaciones,
        ]);
    }

    private function validarCantidad(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser un entero mayor a cero.');
        }
    }
}
