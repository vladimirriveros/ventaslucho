<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    public function ventas()
    {
        return view('admin.reportes.ventas');
    }

    public function ventasPdf(Request $request)
    {
        $user = $request->user();
        $datos = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'sucursal_id' => [
                'nullable',
                'integer',
                Rule::exists('sucursals', 'id')->where('activa', true),
            ],
        ]);

        $fecha_desde = $datos['fecha_desde'] ?? now()->startOfMonth()->format('Y-m-d');
        $fecha_hasta = $datos['fecha_hasta'] ?? now()->format('Y-m-d');
        $sucursalId = isset($datos['sucursal_id']) ? (int) $datos['sucursal_id'] : null;

        if (!$user->can('operaciones.todas-sucursales')) {
            abort_unless($user->sucursal_id, 403, 'Su usuario no tiene una sucursal asignada.');
            $sucursalId = (int) $user->sucursal_id;
        } elseif ($sucursalId) {
            abort_unless($user->puedeGestionarSucursal($sucursalId), 403);
        }

        $ventas = Venta::with(['cliente', 'user', 'sucursal', 'pagos'])
            ->whereBetween('fecha', [
                Carbon::parse($fecha_desde)->startOfDay(),
                Carbon::parse($fecha_hasta)->endOfDay(),
            ])
            ->where('estado', '!=', 'anulada')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $resumen = [
            'total_ventas' => $ventas->count(),
            'total_ingresos' => $ventas->sum('total'),
            'total_contado' => $ventas->where('tipo', 'contado')->sum('total'),
            'total_credito' => $ventas->where('tipo', 'credito')->sum('total'),
            'total_pendiente' => $ventas->sum('pendiente'),
        ];

        $pdf = Pdf::loadView('admin.reportes.pdf.reporte_ventas', compact(
            'ventas',
            'resumen',
            'fecha_desde',
            'fecha_hasta'
        ));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('reporte_ventas_' . now()->format('Ymd_His') . '.pdf');
    }
}
