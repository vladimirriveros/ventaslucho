<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function index()
    {
        return view('admin.ventas.index');
    }

    public function create()
    {
        abort_unless(
            Auth::user()->can('operaciones.todas-sucursales') || Auth::user()->sucursal_id,
            403,
            'Su usuario debe tener una sucursal activa asignada para registrar ventas.'
        );

        return view('admin.ventas.create');
    }

    public function edit($id)
    {
        $venta = Venta::findOrFail($id);
        $this->autorizarVenta($venta);

        return view('admin.ventas.edit', compact('id'));
    }

    public function notaVentaPdf($id)
    {
        $venta = $this->ventaAutorizada($id);
        $this->cargarObservaciones($venta);

        $pdf = Pdf::loadView('admin.ventas.pdf.nota_venta', [
            'venta' => $venta,
            'detalles' => $venta->detalles,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->stream('nota_venta_' . $venta->codigo . '.pdf');
    }

    public function descargarNotaVenta($id)
    {
        $venta = $this->ventaAutorizada($id);
        $this->cargarObservaciones($venta);

        $pdf = Pdf::loadView('admin.ventas.pdf.nota_venta', [
            'venta' => $venta,
            'detalles' => $venta->detalles,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->download('nota_venta_' . $venta->codigo . '.pdf');
    }

    private function ventaAutorizada(int|string $id): Venta
    {
        $venta = Venta::with([
            'detalles.producto',
            'detalles.lote',
            'cliente',
            'sucursal',
            'user',
            'pagos.banca',
        ])->findOrFail($id);

        $this->autorizarVenta($venta);

        return $venta;
    }

    private function autorizarVenta(Venta $venta): void
    {
        abort_unless(
            Auth::user()->puedeGestionarSucursal((int) $venta->sucursal_id),
            403,
            'No tiene autorización para consultar esta venta.'
        );
    }

    private function cargarObservaciones(Venta $venta): void
    {
        $observaciones = json_decode((string) $venta->observaciones, true);
        if (!is_array($observaciones)) {
            $observaciones = [];
        }

        $venta->incluye_impuesto = $observaciones['incluye_impuesto'] ?? 'con_impuesto';
        $venta->forma_pago = $observaciones['forma_pago'] ?? 'contado';
        $venta->lugar_entrega = $observaciones['lugar_entrega'] ?? '';
        $venta->plazo_entrega = $observaciones['plazo_entrega'] ?? 5;
        $venta->validez_economica = $observaciones['validez_economica'] ?? 48;
        $venta->observaciones_adicionales = $observaciones['observaciones_adicionales'] ?? '';
    }
}
