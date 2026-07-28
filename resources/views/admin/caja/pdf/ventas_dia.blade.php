<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas de Caja #{{ $caja->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #172033; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #173b57; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; color: #173b57; }
        .muted { color: #667085; }
        .info, .summary, .table { width: 100%; border-collapse: collapse; }
        .info td { padding: 4px 6px; }
        .table { margin-top: 14px; }
        .table th { background: #173b57; color: #fff; border: 1px solid #173b57; padding: 6px; }
        .table td { border: 1px solid #d7dde5; padding: 5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 14px; }
        .summary td { border: 1px solid #d7dde5; padding: 6px; }
        .summary .label { background: #f3f6f9; font-weight: bold; }
        .section-title { margin: 18px 0 6px; font-size: 13px; color: #173b57; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #d7dde5; text-align: center; color: #667085; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE VENTAS ORIGINADAS EN CAJA</h1>
        <div>Caja N.º {{ $caja->id }} · {{ $sucursal->nombre ?? 'Sucursal' }}</div>
        <div class="muted">Documento de control interno</div>
    </div>

    <table class="info">
        <tr>
            <td><strong>Responsable de apertura:</strong> {{ $caja->user?->name ?? 'N/A' }}</td>
            <td><strong>Apertura:</strong> {{ optional($caja->fecha_apertura)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Responsable de cierre:</strong> {{ $caja->userCierre?->name ?? 'Caja abierta' }}</td>
            <td><strong>Cierre:</strong> {{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i') : 'Pendiente' }}</td>
        </tr>
    </table>

    <h2 class="section-title">Detalle de productos vendidos</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Venta</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>P. venta</th>
                <th>Venta neta</th>
                <th>Método inicial</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalles as $indice => $detalle)
                <tr>
                    <td class="text-center">{{ $indice + 1 }}</td>
                    <td>{{ $detalle['venta_codigo'] }}</td>
                    <td>{{ $detalle['producto_nombre'] }}</td>
                    <td class="text-center">{{ $detalle['cantidad'] }}</td>
                    <td class="text-right">Bs {{ number_format($detalle['precio_venta'], 2) }}</td>
                    <td class="text-right">Bs {{ number_format($detalle['subtotal_venta'], 2) }}</td>
                    <td class="text-center">{{ strtoupper($detalle['venta_data']['metodo_pago'] ?? 'N/A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se originaron ventas en esta caja.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section-title">Resumen comercial</h2>
    <table class="summary">
        <tr>
            <td class="label">Ventas</td>
            <td class="text-right">{{ $resumen['cantidad_ventas'] }}</td>
            <td class="label">Unidades</td>
            <td class="text-right">{{ $resumen['cantidad_productos'] }}</td>
        </tr>
        <tr>
            <td class="label">Costo histórico</td>
            <td class="text-right">Bs {{ number_format($resumen['total_compras'], 2) }}</td>
            <td class="label">Venta neta</td>
            <td class="text-right">Bs {{ number_format($resumen['total_ventas'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Ganancia estimada</td>
            <td class="text-right">Bs {{ number_format($resumen['total_ganancia'], 2) }}</td>
            <td class="label">Diferencia de caja</td>
            <td class="text-right">Bs {{ number_format((float) ($caja->diferencia ?? 0), 2) }}</td>
        </tr>
    </table>

    <h2 class="section-title">Dinero recibido durante esta caja</h2>
    <table class="summary">
        @foreach(['efectivo' => 'Efectivo', 'qr' => 'QR', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'] as $clave => $etiqueta)
            <tr>
                <td class="label">{{ $etiqueta }}</td>
                <td class="text-right">Bs {{ number_format((float) ($cobrosPorMetodo[$clave] ?? 0), 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} · Sistema de Gestión CONSERDEI
    </div>
</body>
</html>
