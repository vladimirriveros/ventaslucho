<?php

namespace App\Http\Controllers;

use App\Models\InventarioSucuralLote;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertaController extends Controller
{
    private const DIAS_VENCIMIENTO = 7;

    public function resumen(Request $request): JsonResponse
    {
        $user = $request->user();
        $sucursales = $this->sucursalesAutorizadas($user);
        $productos = Producto::query()
            ->where('estado', true)
            ->get(['id', 'stock_minimo']);

        $stockBajo = 0;
        $lotesPorVencer = 0;
        $detalleSucursales = [];

        foreach ($sucursales as $sucursal) {
            $stocks = InventarioSucuralLote::query()
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->where('inventario_sucural_lotes.sucursal_id', $sucursal->id)
                ->where('lotes.estado', true)
                ->where(function ($query) {
                    $query->whereNull('lotes.fecha_vencimiento')
                        ->orWhereDate('lotes.fecha_vencimiento', '>=', today());
                })
                ->groupBy('lotes.producto_id')
                ->select('lotes.producto_id', DB::raw('SUM(inventario_sucural_lotes.cantidad_en_sucursal) as stock'))
                ->pluck('stock', 'producto_id');

            $stockBajoSucursal = $productos->filter(function (Producto $producto) use ($stocks) {
                $stock = (int) ($stocks[$producto->id] ?? 0);
                return $stock <= (int) $producto->stock_minimo;
            })->count();

            $porVencerSucursal = InventarioSucuralLote::query()
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->where('inventario_sucural_lotes.sucursal_id', $sucursal->id)
                ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
                ->where('lotes.estado', true)
                ->whereBetween('lotes.fecha_vencimiento', [today(), today()->copy()->addDays(self::DIAS_VENCIMIENTO)])
                ->distinct('lotes.id')
                ->count('lotes.id');

            $stockBajo += $stockBajoSucursal;
            $lotesPorVencer += $porVencerSucursal;
            $detalleSucursales[] = [
                'id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
                'stock_bajo' => $stockBajoSucursal,
                'lotes_por_vencer' => $porVencerSucursal,
            ];
        }

        return response()->json([
            'alerta' => ($stockBajo + $lotesPorVencer) > 0,
            'stock_bajo' => $stockBajo,
            'lotes_por_vencer' => $lotesPorVencer,
            'dias_vencimiento' => self::DIAS_VENCIMIENTO,
            'alcance' => $user->can('operaciones.todas-sucursales')
                ? 'Todas las sucursales'
                : ($user->sucursal?->nombre ?? 'Sin sucursal asignada'),
            'sucursales' => $detalleSucursales,
        ]);
    }

    public function stock(Request $request): JsonResponse|RedirectResponse
    {
        if (!$request->expectsJson() && !$request->ajax()) {
            return redirect()->route('sucursal_por_lotes.index')
                ->with('mensaje', 'Revise el inventario por sucursal para ver los productos con stock bajo.')
                ->with('icono', 'warning');
        }

        $data = $this->resumen($request)->getData(true);

        return response()->json([
            'alerta' => $data['stock_bajo'] > 0,
            'cantidad' => $data['stock_bajo'],
            'tipo' => 'stock_bajo',
        ]);
    }

    public function lotes(Request $request): JsonResponse|RedirectResponse
    {
        if (!$request->expectsJson() && !$request->ajax()) {
            return redirect()->route('lotes.index')
                ->with('mensaje', 'Revise los lotes próximos a vencer.')
                ->with('icono', 'warning');
        }

        $data = $this->resumen($request)->getData(true);

        return response()->json([
            'alerta' => $data['lotes_por_vencer'] > 0,
            'cantidad' => $data['lotes_por_vencer'],
            'dias' => self::DIAS_VENCIMIENTO,
            'tipo' => 'por_vencer',
        ]);
    }

    private function sucursalesAutorizadas($user): Collection
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
