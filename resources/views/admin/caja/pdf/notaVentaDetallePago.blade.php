<!DOCTYPE html>

<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nota de Venta - Caja #{{ $caja->id }}</title>

<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
        margin: 20px;
    }

    .container {
        width: 100%;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .info {
        width: 100%;
        margin-bottom: 15px;
    }

    .info td {
        padding: 3px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .table th {
        border: 1px solid #000;
        padding: 6px;
        background: #f0f0f0;
        text-align: center;
    }

    .table td {
        border: 1px solid #000;
        padding: 5px;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .total-section {
        margin-top: 20px;
        width: 100%;
    }

    .total-section td {
        padding: 6px;
    }

    .total-final {
        font-weight: bold;
        font-size: 14px;
    }

    .venta-header {
        background: #f9f9f9;
        font-weight: bold;
    }

    .footer {
        margin-top: 30px;
        text-align: center;
        font-size: 11px;
    }
</style>

</head>

<body>

<div class="container">

{{-- HEADER --}}
<div class="header">
    <h1>CONSERDEI</h1>
    <p>RUC: 123456789</p>
    <p>{{ $sucursal->nombre ?? 'Sucursal Principal' }}</p>
    <p>{{ now()->format('d/m/Y H:i') }}</p>
</div>

{{-- INFO --}}
<table class="info">
    <tr>
        <td><strong>Caja:</strong> {{ $caja->id }}</td>
        <td><strong>Vendedor:</strong> {{ $caja->user->name ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td><strong>Apertura:</strong> {{ \Carbon\Carbon::parse($caja->fecha_apertura)->format('d/m/Y H:i') }}</td>
        <td><strong>Cierre:</strong> {{ \Carbon\Carbon::parse($caja->fecha_cierre)->format('d/m/Y H:i') }}</td>
    </tr>
</table>

{{-- INICIALIZACIÓN --}}
@php
    $i = 1;
    $totalGeneral = 0;

    $totalEfectivo = 0;
    $totalQR = 0;
    $totalTransferencia = 0;
    $totalTarjeta = 0;

    $ventaActual = null;
@endphp

{{-- TABLA --}}
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Producto</th>
            <th>Cant.</th>
            <th>P. Unit</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>

    @foreach($detalles as $detalle)

        {{-- DETECTAR CAMBIO DE VENTA --}}
        @if($ventaActual != $detalle['venta_id'])

            {{-- SUMAR SOLO UNA VEZ POR VENTA --}}
            @php
                $ventaActual = $detalle['venta_id'];

                $metodo = $detalle['venta_data']['metodo_pago'] ?? 'efectivo';
                $monto = $detalle['venta_data']['pagado'] ?? 0;

                if ($metodo == 'efectivo') $totalEfectivo += $monto;
                elseif ($metodo == 'qr') $totalQR += $monto;
                elseif ($metodo == 'transferencia') $totalTransferencia += $monto;
                elseif ($metodo == 'tarjeta') $totalTarjeta += $monto;
            @endphp

            {{-- HEADER DE VENTA --}}
            <tr class="venta-header">
                <td colspan="5">
                    Venta #{{ $detalle['venta_codigo'] }} |
                    Cliente: {{ $detalle['cliente'] }} |
                    Método: {{ strtoupper($metodo) }}
                </td>
            </tr>

        @endif

        {{-- PRODUCTO --}}
        <tr>
            <td class="text-center">{{ $i++ }}</td>
            <td>{{ $detalle['producto_nombre'] }}</td>
            <td class="text-center">{{ $detalle['cantidad'] }}</td>
            <td class="text-right">Bs {{ number_format($detalle['precio_venta'], 2) }}</td>
            <td class="text-right">Bs {{ number_format($detalle['subtotal_venta'], 2) }}</td>
        </tr>

        @php
            $totalGeneral += $detalle['subtotal_venta'];
        @endphp

    @endforeach

    </tbody>
</table>

{{-- TOTALES --}}
<table class="total-section">
    <tr>
        <td class="text-right"><strong>Total General:</strong></td>
        <td class="text-right total-final">Bs {{ number_format($totalGeneral, 2) }}</td>
    </tr>
</table>

{{-- DESGLOSE POR MÉTODO --}}
<table class="total-section">
    <tr>
        <td class="text-right"><strong>Efectivo:</strong></td>
        <td class="text-right">Bs {{ number_format($totalEfectivo, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right"><strong>QR:</strong></td>
        <td class="text-right">Bs {{ number_format($totalQR, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right"><strong>Transferencia:</strong></td>
        <td class="text-right">Bs {{ number_format($totalTransferencia, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right"><strong>Tarjeta:</strong></td>
        <td class="text-right">Bs {{ number_format($totalTarjeta, 2) }}</td>
    </tr>
</table>

{{-- RESUMEN CAJA --}}
<table class="total-section">
    <tr>
        <td class="text-right">Monto Inicial:</td>
        <td class="text-right">Bs {{ number_format($caja->monto_inicial, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right">Monto Final:</td>
        <td class="text-right">Bs {{ number_format($caja->monto_final, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right">Diferencia:</td>
        <td class="text-right">
            {{ $caja->diferencia >= 0 ? '+' : '' }}Bs {{ number_format($caja->diferencia, 2) }}
        </td>
    </tr>
</table>

{{-- FOOTER --}}
<div class="footer">
    <p><strong>Gracias por su compra</strong></p>
    <p>Documento de control interno</p>
</div>

</div>

</body>
</html>
