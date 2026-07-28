<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Cotizacion;
use App\Models\InventarioSucuralLote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CotizacionController extends Controller
{
    public function index()
    {
        return view('admin.cotizaciones.index');
    }

    public function create()
    {
        abort_unless(
            Auth::user()->can('operaciones.todas-sucursales') || Auth::user()->sucursal_id,
            403,
            'Su usuario debe tener una sucursal activa asignada para crear cotizaciones.'
        );

        return view('admin.cotizaciones.create');
    }

    public function edit($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        $this->autorizarCotizacion($cotizacion);

        return view('admin.cotizaciones.edit', compact('id'));
    }

    public function imprimir($id)
    {
        $cotizacion = Cotizacion::with(['detalles.producto', 'detalles.lote', 'cliente', 'sucursal', 'user'])
            ->findOrFail($id);
        $this->autorizarCotizacion($cotizacion);

        $pdf = Pdf::loadView('admin.cotizaciones.pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'detalles' => $cotizacion->detalles,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->stream('cotizacion_' . $cotizacion->codigo . '.pdf');
    }

    public function convertirAVenta(Request $request, $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        abort_unless($request->user()->can('cotizaciones.convertir'), 403);
        $this->autorizarCotizacion($cotizacion);

        if ($cotizacion->estado !== 'activa' || ($cotizacion->valida_hasta && $cotizacion->valida_hasta->isPast())) {
            return back()->with('mensaje', 'La cotización está vencida, anulada o ya fue convertida.')
                ->with('icono', 'warning');
        }

        return redirect()->route('ventas.create', ['cotizacion_id' => $id])
            ->with('mensaje', 'Cotización cargada. El inventario se validará al confirmar la venta.')
            ->with('icono', 'info');
    }

    public function verificarStock(Request $request, $id)
    {
        $cotizacion = Cotizacion::with('detalles.producto')->findOrFail($id);
        abort_unless($request->user()->can('cotizaciones.convertir'), 403);
        $this->autorizarCotizacion($cotizacion);

        if ($cotizacion->estado !== 'activa' || ($cotizacion->valida_hasta && $cotizacion->valida_hasta->isPast())) {
            return response()->json(['ok' => false, 'message' => 'La cotización no está activa o ya venció.'], 422);
        }
        if (!Caja::hayCajaAbierta($cotizacion->sucursal_id)) {
            return response()->json([
                'ok' => false,
                'error' => 'caja_cerrada',
                'message' => 'Debe abrir la caja de la sucursal antes de convertir la cotización.',
            ], 422);
        }

        $faltantes = [];
        foreach ($cotizacion->detalles as $detalle) {
            $stockDisponible = InventarioSucuralLote::query()
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->where('inventario_sucural_lotes.sucursal_id', $cotizacion->sucursal_id)
                ->where('lotes.producto_id', $detalle->producto_id)
                ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
                ->where('lotes.estado', true)
                ->where('lotes.cantidad_actual', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('lotes.fecha_vencimiento')
                        ->orWhereDate('lotes.fecha_vencimiento', '>=', today());
                })
                ->sum('inventario_sucural_lotes.cantidad_en_sucursal');

            if ((int) $stockDisponible < (int) $detalle->cantidad) {
                $faltantes[] = [
                    'nombre' => $detalle->producto->nombre,
                    'codigo' => $detalle->producto->codigo,
                    'cantidad_necesaria' => (int) $detalle->cantidad,
                    'stock_disponible' => (int) $stockDisponible,
                ];
            }
        }

        return response()->json([
            'ok' => empty($faltantes),
            'stock_insuficiente' => $faltantes,
            'message' => empty($faltantes)
                ? 'Stock disponible. La asignación de lotes se realizará al registrar la venta.'
                : 'Uno o más productos todavía no tienen stock suficiente.',
        ]);
    }

    private function autorizarCotizacion(Cotizacion $cotizacion): void
    {
        abort_unless(
            Auth::user()->puedeGestionarSucursal((int) $cotizacion->sucursal_id),
            403,
            'No tiene autorización para consultar esta cotización.'
        );
    }
}
