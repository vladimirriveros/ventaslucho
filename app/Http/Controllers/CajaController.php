<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class CajaController extends Controller
{
    public function index()
    {
        return view('admin.caja.index');
    }

    public function reportePdf(int $cajaId)
    {
        $caja = $this->cajaAutorizada($cajaId, [
            'sucursal',
            'user',
            'userCierre',
            'movimientos' => fn ($query) => $query->orderBy('fecha')->orderBy('id'),
        ]);

        $pdf = Pdf::loadView('admin.caja.pdf.reporte', [
            'caja' => $caja,
            'movimientos' => $caja->movimientos,
        ]);

        return $pdf->setPaper('A4', 'portrait')
            ->stream('reporte_caja_' . $caja->id . '.pdf');
    }

    public function ventasPdf(int $cajaId)
    {
        $caja = $this->cajaAutorizada($cajaId, [
            'sucursal',
            'user',
            'userCierre',
            'movimientos',
        ]);

        // Una venta pertenece a la caja donde se originó. Los cobros posteriores
        // permanecen asociados a la caja que realmente recibió cada pago.
        $ventas = Venta::query()
            ->where('caja_id', $caja->id)
            ->where('estado', '!=', 'anulada')
            ->with([
                'cliente',
                'user',
                'detalles.producto',
                'detalles.lote',
                'pagos' => fn ($query) => $query->orderBy('fecha')->orderBy('id'),
            ])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $detalles = [];
        $totalProductos = 0;
        $totalCosto = 0.0;
        $totalGanancia = 0.0;

        foreach ($ventas as $venta) {
            $pagoEnCaja = $venta->pagos->firstWhere('caja_id', $caja->id);
            $metodoPago = $pagoEnCaja?->metodo_pago
                ?? $venta->pagos->first()?->metodo_pago
                ?? ($venta->tipo === 'credito' ? 'credito' : 'sin pago');

            $subtotalVenta = (float) $venta->subtotal;
            $factorNeto = $subtotalVenta > 0
                ? min(1, max(0, (float) $venta->total / $subtotalVenta))
                : 1;

            foreach ($venta->detalles as $detalle) {
                $precioCompra = (float) ($detalle->costo_unitario ?? $detalle->lote?->precio_compra ?? 0);
                $subtotalCompra = round($precioCompra * (int) $detalle->cantidad, 2);
                $subtotalNeto = round((float) $detalle->subtotal * $factorNeto, 2);
                $ganancia = round($subtotalNeto - $subtotalCompra, 2);

                $totalCosto += $subtotalCompra;
                $totalGanancia += $ganancia;
                $totalProductos += (int) $detalle->cantidad;

                $detalles[] = [
                    'venta_id' => $venta->id,
                    'venta_codigo' => $venta->codigo,
                    'fecha' => $venta->fecha,
                    'cliente' => $venta->cliente?->nombre ?? 'CLIENTE OCASIONAL',
                    'vendedor' => $venta->user?->name ?? 'N/A',
                    'producto_nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                    'producto_codigo' => $detalle->producto?->codigo ?? 'N/A',
                    'cantidad' => (int) $detalle->cantidad,
                    'precio_compra' => $precioCompra,
                    'precio_venta' => (float) $detalle->precio_unitario,
                    'subtotal_compra' => $subtotalCompra,
                    'subtotal_venta' => $subtotalNeto,
                    'ganancia' => $ganancia,
                    'venta_data' => [
                        'total' => (float) $venta->total,
                        'pagado' => (float) $venta->pagado,
                        'pendiente' => (float) $venta->pendiente,
                        'metodo_pago' => $metodoPago,
                    ],
                ];
            }
        }

        $cobrosPorMetodo = $caja->movimientos
            ->where('tipo', 'ingreso')
            ->groupBy('metodo_pago')
            ->map(fn ($movimientos) => round((float) $movimientos->sum('monto'), 2));

        $resumen = [
            'total_compras' => round($totalCosto, 2),
            'total_ventas' => round((float) $ventas->sum('total'), 2),
            'total_ganancia' => round($totalGanancia, 2),
            'cantidad_ventas' => $ventas->count(),
            'cantidad_productos' => $totalProductos,
        ];

        $pdf = Pdf::loadView('admin.caja.pdf.ventas_dia', [
            'caja' => $caja,
            'sucursal' => $caja->sucursal,
            'ventas' => $ventas,
            'detalles' => $detalles,
            'resumen' => $resumen,
            'cobrosPorMetodo' => $cobrosPorMetodo,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'courier',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('ventas_caja_' . $caja->id . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function cajaAutorizada(int $cajaId, array $with = []): Caja
    {
        $caja = Caja::with($with)->findOrFail($cajaId);
        $user = Auth::user();

        abort_unless(
            $user && $user->can('caja.reportes') && $user->puedeGestionarSucursal((int) $caja->sucursal_id),
            403,
            'No tiene autorización para consultar esta caja.'
        );

        return $caja;
    }
}
