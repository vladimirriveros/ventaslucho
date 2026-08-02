<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\InventarioSucuralLote;

class CotizacionStockService
{
    public function faltantes(Cotizacion $cotizacion): array
    {
        $cotizacion->loadMissing('detalles.producto');
        $faltantes = [];

        $requerimientos = $cotizacion->detalles
            ->groupBy('producto_id')
            ->map(function ($detalles) {
                $primero = $detalles->first();

                return [
                    'producto_id' => (int) $primero->producto_id,
                    'producto' => $primero->producto,
                    'cantidad' => (int) $detalles->sum('cantidad'),
                ];
            });

        foreach ($requerimientos as $requerimiento) {
            $stockDisponible = (int) InventarioSucuralLote::query()
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->where('inventario_sucural_lotes.sucursal_id', $cotizacion->sucursal_id)
                ->where('lotes.producto_id', $requerimiento['producto_id'])
                ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
                ->where('lotes.estado', true)
                ->where('lotes.cantidad_actual', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('lotes.fecha_vencimiento')
                        ->orWhereDate('lotes.fecha_vencimiento', '>=', today());
                })
                ->sum('inventario_sucural_lotes.cantidad_en_sucursal');

            $cantidadNecesaria = $requerimiento['cantidad'];
            if ($stockDisponible < $cantidadNecesaria) {
                $producto = $requerimiento['producto'];
                $faltantes[] = [
                    'nombre' => $producto?->nombre ?? 'Producto eliminado',
                    'codigo' => $producto?->codigo ?? 'S/C',
                    'cantidad_necesaria' => $cantidadNecesaria,
                    'stock_disponible' => $stockDisponible,
                    'cantidad_faltante' => $cantidadNecesaria - $stockDisponible,
                ];
            }
        }

        return $faltantes;
    }
}
