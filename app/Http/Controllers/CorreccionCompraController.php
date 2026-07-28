<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CorreccionCompraController extends Controller
{
    public function edit($compraId)
    {
        $compra = Compra::with(['detalles.producto', 'detalles.lote', 'proveedor', 'sucursal'])->findOrFail($compraId);

        if ($compra->estado !== 'Recibido') {
            return redirect()->route('compras.show', $compraId)
                ->with('mensaje', 'Solo se pueden corregir compras recibidas.')
                ->with('icono', 'warning');
        }

        if ($compra->sucursal_id && !Auth::user()->puedeGestionarSucursal((int) $compra->sucursal_id)) {
            abort(403, 'La compra pertenece a otra sucursal.');
        }

        return view('admin.compras.correccion', compact('compra'));
    }

    public function update(Request $request, $compraId)
    {
        $validated = $request->validate([
            'correcciones' => 'required|array|min:1',
            'correcciones.*.detalle_id' => 'required|integer|exists:detalle_compras,id',
            'correcciones.*.cantidad_correcta' => 'required|integer|min:0',
            'correcciones.*.motivo' => 'required|string|min:3|max:255',
        ]);

        try {
            DB::transaction(function () use ($compraId, $validated) {
                $compra = Compra::query()->lockForUpdate()->findOrFail($compraId);
                if ($compra->estado !== 'Recibido') {
                    throw new \RuntimeException('La compra ya no está disponible para corrección.');
                }

                $sucursalId = $compra->sucursal_id;
                if (!$sucursalId) {
                    $sucursalId = MovimientoInventario::where('origen_tipo', Compra::class)
                        ->where('origen_id', $compra->id)
                        ->value('sucursal_id');
                }
                if (!$sucursalId) {
                    $sucursalId = MovimientoInventario::where('tipo_movimiento', 'Entrada')
                        ->where('observaciones', 'like', '%COMPRA_ID:' . $compra->id . '%')
                        ->value('sucursal_id');
                }
                if (!$sucursalId) {
                    throw new \RuntimeException('No fue posible determinar la sucursal donde se recibió la compra.');
                }
                if (!Auth::user()->puedeGestionarSucursal((int) $sucursalId)) {
                    throw new \RuntimeException('No tiene autorización para corregir inventario en esa sucursal.');
                }

                $servicio = app(InventarioService::class);
                foreach ($validated['correcciones'] as $correccion) {
                    $detalle = DetalleCompra::with(['lote', 'producto'])
                        ->where('compra_id', $compra->id)
                        ->lockForUpdate()
                        ->findOrFail($correccion['detalle_id']);
                    $lote = Lote::query()->lockForUpdate()->findOrFail($detalle->lote_id);
                    $cantidadAnterior = (int) $detalle->cantidad;
                    $cantidadCorrecta = (int) $correccion['cantidad_correcta'];
                    $diferencia = $cantidadCorrecta - $cantidadAnterior;

                    if ($diferencia === 0) {
                        continue;
                    }

                    $texto = "Corrección de compra #{$compra->id}: {$cantidadAnterior} → {$cantidadCorrecta}. Motivo: {$correccion['motivo']}";
                    if ($diferencia < 0) {
                        $servicio->disminuir($lote->id, (int) $sucursalId, abs($diferencia), Auth::id(), Compra::class, $compra->id, $texto);
                    } else {
                        $servicio->aumentar($lote->id, (int) $sucursalId, $diferencia, Auth::id(), Compra::class, $compra->id, $texto);
                    }

                    // La cantidad inicial representa lo realmente recibido tras la corrección.
                    $lote->refresh();
                    $lote->cantidad_inicial = $cantidadCorrecta;
                    $lote->save();

                    $detalle->cantidad = $cantidadCorrecta;
                    $detalle->subtotal = round($cantidadCorrecta * (float) $detalle->precio_unitario, 2);
                    $detalle->save();
                }

                $compra->sucursal_id = $sucursalId;
                $compra->total = $compra->detalles()->sum('subtotal');
                $compra->observaciones = trim(($compra->observaciones ? $compra->observaciones . ' | ' : '')
                    . 'Corrección aplicada por ' . Auth::user()->name . ' el ' . now()->format('d/m/Y H:i'));
                $compra->save();
            }, 3);

            return redirect()->route('compras.show', $compraId)
                ->with('mensaje', 'Compra e inventario corregidos con trazabilidad completa.')
                ->with('icono', 'success');
        } catch (\Throwable $e) {
            return back()->withInput()->with('mensaje', 'No se aplicó la corrección: ' . $e->getMessage())->with('icono', 'error');
        }
    }

    public function getStockLote($loteId)
    {
        $lote = Lote::findOrFail($loteId);
        $usuario = Auth::user();
        $inventarios = InventarioSucuralLote::with('sucursal')
            ->where('lote_id', $loteId)
            ->when(!$usuario->can('operaciones.todas-sucursales'), function ($query) use ($usuario) {
                $usuario->sucursal_id
                    ? $query->where('sucursal_id', $usuario->sucursal_id)
                    : $query->whereRaw('1 = 0');
            })
            ->get();
        abort_if($inventarios->isEmpty() && !$usuario->can('operaciones.todas-sucursales'), 403);

        return response()->json([
            'lote' => $lote->codigo_lote,
            'stock_total' => $inventarios->sum('cantidad_en_sucursal'),
            'stock_por_sucursal' => $inventarios->map(fn ($item) => [
                'sucursal' => $item->sucursal?->nombre ?? 'Sin sucursal',
                'cantidad' => $item->cantidad_en_sucursal,
            ]),
        ]);
    }
}
