<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nota de Venta {{ $venta->codigo }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #3f88cc;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #207cd1;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
        }

        .total-box {
            border: 1px solid #3f88cc;
            padding: 10px;
            border-radius: 5px;
            background: #f8fff8;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>NOTA DE VENTA</h1>
        <p>N° {{ $venta->codigo }}</p>
    </div>

    <div class="info-box">
        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</p>
        <p><strong>Cliente:</strong> {{ $venta->cliente ? $venta->cliente->nombre : 'CLIENTE OCASIONAL' }}</p>
        @if ($venta->cliente && $venta->cliente->nit)
            <p><strong>NIT:</strong> {{ $venta->cliente->nit }}</p>
        @endif
        <p><strong>Vendedor:</strong> {{ $venta->user->name }}</p>
        <p><strong>Sucursal:</strong> {{ $venta->sucursal->nombre }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cant.</th>
                <th class="text-center">Unidad</th>
                <th>Producto</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $detalle)
                <tr>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>{{ $detalle->producto->unidad_medida }}</td>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td class="text-right">Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <p class="text-right"><strong>SUBTOTAL:</strong> Bs {{ number_format($venta->subtotal, 2) }}</p>
        @if ((float) $venta->descuento > 0)
            <p class="text-right"><strong>REBAJA:</strong> - Bs {{ number_format($venta->descuento, 2) }}</p>
        @endif
        <p class="text-right"><strong>TOTAL FINAL:</strong> Bs {{ number_format($venta->total, 2) }}</p>
    </div>

    {{-- En la nota de venta, mostrar los datos adicionales --}}
    @php
        $observacionesData = json_decode($venta->observaciones, true);
    @endphp

    @if ($observacionesData)
        <div class="info-box mt-3">
            {{-- <strong>Datos de la Venta:</strong><br> --}}
            <strong>INCLUYE:</strong>
            {{ ($observacionesData['incluye_impuesto'] ?? 'con_impuesto') == 'con_impuesto' ? 'Impuestos de Ley' : 'Sin Impuesto' }}<br>

            <strong>FORMA DE PAGO:</strong> {{ ucfirst($observacionesData['forma_pago'] ?? 'contado') }}<br>

            @if (!empty($observacionesData['lugar_entrega']))
                <strong>LUGAR DE ENTREGA:</strong> {{ $observacionesData['lugar_entrega'] }}<br>
            @endif

            <strong>PLAZO DE ENTREGA:</strong> {{ $observacionesData['plazo_entrega'] ?? 5 }} días confirmado el pedido<br>

            <strong>VALIDEZ ECONOMICA:</strong> {{ ucfirst((string) ($observacionesData['validez_economica'] ?? 48)) }} Horas<br>

            {{-- @if (!empty($observacionesData['validez_economica']))
                <strong>Lugar de Entrega:</strong> {{ $observacionesData['lugar_entrega'] }}<br>
            @endif --}}

            @if (!empty($observacionesData['observaciones_adicionales']))
                <strong>Observaciones:</strong> {{ $observacionesData['observaciones_adicionales'] }}<br>
            @endif
        </div>
    @endif

    <div class="footer">
        <p>¡Gracias por su compra!</p>
    </div>
</body>

</html>
