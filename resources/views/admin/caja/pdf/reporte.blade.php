<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja #{{ $caja->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #172033; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #173b57; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { margin: 0 0 4px; color: #173b57; font-size: 20px; }
        .info, .table, .totals { width: 100%; border-collapse: collapse; }
        .info { margin-bottom: 15px; }
        .info td { padding: 4px 6px; }
        .table th { background: #173b57; color: white; border: 1px solid #173b57; padding: 6px; }
        .table td { border: 1px solid #d7dde5; padding: 5px; }
        .totals { margin-top: 15px; }
        .totals td { border: 1px solid #d7dde5; padding: 6px; }
        .label { background: #f3f6f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .income { color: #16803c; font-weight: bold; }
        .expense { color: #c0362c; font-weight: bold; }
        .section-title { margin: 18px 0 6px; font-size: 13px; color: #173b57; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #d7dde5; text-align: center; color: #667085; }
    </style>
</head>
<body>
@php
    $totalIngresos = (float) collect($movimientos)->where('tipo', 'ingreso')->sum('monto');
    $totalEgresos = (float) collect($movimientos)->where('tipo', 'egreso')->sum('monto');
    $ingresosEfectivo = (float) collect($movimientos)->where('tipo', 'ingreso')->where('metodo_pago', 'efectivo')
        ->reject(fn ($movimiento) => $movimiento->concepto === 'Apertura de caja')->sum('monto');
    $egresosEfectivo = (float) collect($movimientos)->where('tipo', 'egreso')->where('metodo_pago', 'efectivo')->sum('monto');
    $resumenMetodos = collect($movimientos)->where('tipo', 'ingreso')->groupBy('metodo_pago')
        ->map(fn ($grupo) => (float) $grupo->sum('monto'));
@endphp

<div class="header">
    <h1>REPORTE DE CIERRE DE CAJA</h1>
    <div>Caja N.º {{ $caja->id }} · {{ $caja->sucursal?->nombre ?? 'Sucursal' }}</div>
</div>

<table class="info">
    <tr>
        <td><strong>Apertura:</strong> {{ optional($caja->fecha_apertura)->format('d/m/Y H:i') }}</td>
        <td><strong>Usuario:</strong> {{ $caja->user?->name ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td><strong>Cierre:</strong> {{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i') : 'Pendiente' }}</td>
        <td><strong>Usuario de cierre:</strong> {{ $caja->userCierre?->name ?? 'N/A' }}</td>
    </tr>
</table>

<h2 class="section-title">Movimientos registrados</h2>
<table class="table">
    <thead>
        <tr>
            <th>Fecha y hora</th>
            <th>Tipo</th>
            <th>Concepto</th>
            <th>Método</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movimientos as $movimiento)
            <tr>
                <td>{{ optional($movimiento->fecha)->format('d/m/Y H:i') }}</td>
                <td class="text-center {{ $movimiento->tipo === 'ingreso' ? 'income' : 'expense' }}">
                    {{ strtoupper($movimiento->tipo) }}
                </td>
                <td>{{ $movimiento->concepto }}</td>
                <td class="text-center">{{ strtoupper($movimiento->metodo_pago) }}</td>
                <td class="text-right {{ $movimiento->tipo === 'ingreso' ? 'income' : 'expense' }}">
                    {{ $movimiento->tipo === 'ingreso' ? '+' : '-' }} Bs {{ number_format((float) $movimiento->monto, 2) }}
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center">No existen movimientos registrados.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="section-title">Conciliación de efectivo</h2>
<table class="totals">
    <tr><td class="label">Fondo inicial</td><td class="text-right">Bs {{ number_format((float) $caja->monto_inicial, 2) }}</td></tr>
    <tr><td class="label">Ingresos en efectivo</td><td class="text-right">+ Bs {{ number_format($ingresosEfectivo, 2) }}</td></tr>
    <tr><td class="label">Egresos en efectivo</td><td class="text-right">- Bs {{ number_format($egresosEfectivo, 2) }}</td></tr>
    <tr><td class="label">Efectivo esperado</td><td class="text-right"><strong>Bs {{ number_format((float) $caja->monto_esperado, 2) }}</strong></td></tr>
    <tr><td class="label">Efectivo contado</td><td class="text-right"><strong>Bs {{ number_format((float) $caja->monto_final, 2) }}</strong></td></tr>
    <tr>
        <td class="label">Diferencia</td>
        <td class="text-right {{ (float) $caja->diferencia >= 0 ? 'income' : 'expense' }}">
            {{ (float) $caja->diferencia >= 0 ? '+' : '' }} Bs {{ number_format((float) $caja->diferencia, 2) }}
        </td>
    </tr>
</table>

<h2 class="section-title">Resumen de ingresos por método</h2>
<table class="totals">
    @foreach(['efectivo' => 'Efectivo', 'qr' => 'QR', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'] as $metodo => $etiqueta)
        <tr><td class="label">{{ $etiqueta }}</td><td class="text-right">Bs {{ number_format((float) ($resumenMetodos[$metodo] ?? 0), 2) }}</td></tr>
    @endforeach
    <tr><td class="label">Todos los ingresos registrados</td><td class="text-right">Bs {{ number_format($totalIngresos, 2) }}</td></tr>
    <tr><td class="label">Todos los egresos registrados</td><td class="text-right">Bs {{ number_format($totalEgresos, 2) }}</td></tr>
</table>

@if($caja->observaciones_apertura || $caja->observaciones_cierre)
    <h2 class="section-title">Observaciones</h2>
    <table class="totals">
        <tr><td class="label">Apertura</td><td>{{ $caja->observaciones_apertura ?: 'Sin observaciones' }}</td></tr>
        <tr><td class="label">Cierre</td><td>{{ $caja->observaciones_cierre ?: 'Sin observaciones' }}</td></tr>
    </table>
@endif

<div class="footer">Generado el {{ now()->format('d/m/Y H:i:s') }} · Sistema de Gestión CONSERDEI</div>
</body>
</html>
