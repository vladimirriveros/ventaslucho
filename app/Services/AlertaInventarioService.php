<?php

namespace App\Services;

use App\Models\InventarioSucuralLote;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertaInventarioService
{
    public const DIAS_VENCIMIENTO = 7;

    public function resumen(User $user): array
    {
        $sucursales = $this->sucursalesAutorizadas($user);
        $stockBajo = collect();

        foreach ($sucursales as $sucursal) {
            // Solo se controlan productos habilitados para esta sucursal.
            // El estado global del catálogo no activa alertas en otras sedes.
            $productosSucursal = DB::table('producto_sucursal')
                ->join('productos', 'producto_sucursal.producto_id', '=', 'productos.id')
                ->where('producto_sucursal.sucursal_id', $sucursal->id)
                ->where('producto_sucursal.activo', true)
                ->where('productos.estado', true)
                ->orderBy('productos.nombre')
                ->get([
                    'productos.id',
                    'productos.codigo',
                    'productos.nombre',
                    DB::raw('COALESCE(producto_sucursal.stock_minimo, productos.stock_minimo, 0) AS stock_minimo'),
                ]);

            $stocks = InventarioSucuralLote::query()
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->where('inventario_sucural_lotes.sucursal_id', $sucursal->id)
                ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
                ->where('lotes.estado', true)
                ->where('lotes.cantidad_actual', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('lotes.fecha_vencimiento')
                        ->orWhereDate('lotes.fecha_vencimiento', '>=', today());
                })
                ->groupBy('lotes.producto_id')
                ->select('lotes.producto_id', DB::raw('SUM(inventario_sucural_lotes.cantidad_en_sucursal) AS stock'))
                ->pluck('stock', 'producto_id');

            foreach ($productosSucursal as $producto) {
                $stock = (int) ($stocks[$producto->id] ?? 0);
                $minimo = max(0, (int) $producto->stock_minimo);

                if ($stock <= $minimo) {
                    $stockBajo->push([
                        'sucursal_id' => $sucursal->id,
                        'sucursal' => $sucursal->nombre,
                        'producto_id' => $producto->id,
                        'codigo' => $producto->codigo,
                        'producto' => $producto->nombre,
                        'stock' => $stock,
                        'stock_minimo' => $minimo,
                        'sin_stock' => $stock === 0,
                    ]);
                }
            }
        }

        $baseLotes = InventarioSucuralLote::query()
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->join('sucursals', 'inventario_sucural_lotes.sucursal_id', '=', 'sucursals.id')
            ->join('producto_sucursal', function ($join) {
                $join->on('producto_sucursal.producto_id', '=', 'productos.id')
                    ->on('producto_sucursal.sucursal_id', '=', 'inventario_sucural_lotes.sucursal_id');
            })
            ->whereIn('inventario_sucural_lotes.sucursal_id', $sucursales->pluck('id'))
            ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
            ->where('lotes.estado', true)
            ->where('productos.estado', true)
            ->where('producto_sucursal.activo', true);

        $porVencer = (clone $baseLotes)
            ->whereNotNull('lotes.fecha_vencimiento')
            ->whereBetween('lotes.fecha_vencimiento', [today(), today()->copy()->addDays(self::DIAS_VENCIMIENTO)])
            ->orderBy('lotes.fecha_vencimiento')
            ->get([
                'lotes.id AS lote_id',
                'lotes.codigo_lote',
                'lotes.fecha_vencimiento',
                'productos.id AS producto_id',
                'productos.codigo AS producto_codigo',
                'productos.nombre AS producto',
                'sucursals.id AS sucursal_id',
                'sucursals.nombre AS sucursal',
                'inventario_sucural_lotes.cantidad_en_sucursal AS cantidad',
            ])
            ->map(function ($item) {
                $item->dias_restantes = today()->diffInDays($item->fecha_vencimiento, false);
                return $item;
            });

        $vencidos = (clone $baseLotes)
            ->whereNotNull('lotes.fecha_vencimiento')
            ->whereDate('lotes.fecha_vencimiento', '<', today())
            ->orderBy('lotes.fecha_vencimiento')
            ->get([
                'lotes.id AS lote_id',
                'lotes.codigo_lote',
                'lotes.fecha_vencimiento',
                'productos.id AS producto_id',
                'productos.codigo AS producto_codigo',
                'productos.nombre AS producto',
                'sucursals.id AS sucursal_id',
                'sucursals.nombre AS sucursal',
                'inventario_sucural_lotes.cantidad_en_sucursal AS cantidad',
            ]);

        $total = $stockBajo->count() + $porVencer->count() + $vencidos->count();

        return [
            'alerta' => $total > 0,
            'total' => $total,
            'stock_bajo' => $stockBajo->count(),
            'lotes_por_vencer' => $porVencer->count(),
            'lotes_vencidos' => $vencidos->count(),
            'dias_vencimiento' => self::DIAS_VENCIMIENTO,
            'alcance' => $user->can('operaciones.todas-sucursales')
                ? 'Todas las sucursales'
                : ($user->sucursal?->nombre ?? 'Sin sucursal asignada'),
            'sucursales' => $sucursales,
            'detalle_stock_bajo' => $stockBajo,
            'detalle_por_vencer' => $porVencer,
            'detalle_vencidos' => $vencidos,
        ];
    }

    private function sucursalesAutorizadas(User $user): Collection
    {
        return Sucursal::query()
            ->where('activa', true)
            ->when(!$user->can('operaciones.todas-sucursales'), function ($query) use ($user) {
                $user->sucursal_id
                    ? $query->whereKey($user->sucursal_id)
                    : $query->whereRaw('1 = 0');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }
}
