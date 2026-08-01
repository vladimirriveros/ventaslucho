<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $cotizacion->codigo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Tamaño para 1/4 de hoja (105mm x 148mm) */
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            width: 105mm;
            min-height: 148mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
        }

        /* Contenedor principal */
        .ticket {
            width: 100%;
            max-width: 95mm;
            margin: 0 auto;
            background: white;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ffc107;
        }

        .header h1 {
            font-size: 14px;
            margin: 0;
            color: #ffc107;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 8px;
            margin: 2px 0;
            color: #666;
        }

        .codigo {
            font-size: 10px;
            font-weight: bold;
            background: #f5f5f5;
            padding: 2px;
            margin-top: 3px;
        }

        /* Información del cliente */
        .info-cliente {
            margin-bottom: 8px;
            padding: 5px;
            background: #f9f9f9;
            border-radius: 3px;
            font-size: 8px;
        }

        .info-cliente p {
            margin: 2px 0;
            line-height: 1.3;
        }

        .info-cliente strong {
            font-size: 8px;
        }

        /* Validez */
        .validez {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 4px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        /* Tabla de productos */
        .productos {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }

        .productos th {
            background-color: #ffc107;
            color: #000;
            font-size: 7px;
            padding: 3px 2px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .productos td {
            border: 1px solid #ddd;
            padding: 3px 2px;
            font-size: 7px;
            vertical-align: top;
        }

        .productos .text-right {
            text-align: right;
        }

        .productos .text-center {
            text-align: center;
        }

        /* Totales */
        .totales {
            margin-top: 5px;
            margin-bottom: 8px;
            border-top: 1px dashed #ddd;
            padding-top: 5px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            padding: 2px 0;
        }

        .total-grande {
            font-size: 10px;
            font-weight: bold;
            border-top: 1px solid #ffc107;
            margin-top: 3px;
            padding-top: 3px;
        }

        /* Datos adicionales de la cotización */
        .datos-adicionales {
            margin-top: 8px;
            padding: 4px;
            background: #e8f0fe;
            border-radius: 3px;
            font-size: 7px;
            border-left: 3px solid #ffc107;
        }

        .datos-adicionales p {
            margin: 2px 0;
        }

        .datos-adicionales strong {
            font-size: 7px;
        }

        /* Observaciones */
        .observaciones {
            margin-top: 8px;
            padding: 4px;
            background: #f9f9f9;
            border-radius: 3px;
            font-size: 7px;
        }

        .observaciones p {
            margin: 2px 0;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px dashed #ddd;
            padding-top: 5px;
        }

        .footer p {
            margin: 2px 0;
        }

        /* Línea de corte */
        .cut-line {
            text-align: center;
            font-size: 8px;
            color: #ccc;
            margin-top: 8px;
            padding-top: 3px;
            border-top: 1px dashed #ccc;
        }

        /* Para impresión */
        @media print {
            body {
                width: 105mm;
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none;
            }

            .cut-line {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="ticket">
        {{-- Encabezado --}}
        <div class="header">
            <h1>COTIZACIÓN</h1>
            <p class="codigo">N° {{ $cotizacion->codigo }}</p>
            <p>Fecha: {{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}</p>
        </div>

        {{-- Información del Cliente --}}
        <div class="info-cliente">
            <p><strong>Cliente:</strong> {{ $cotizacion->cliente ? $cotizacion->cliente->nombre : 'CLIENTE OCASIONAL' }}
            </p>
            @if ($cotizacion->cliente && $cotizacion->cliente->nit)
                <p><strong>NIT:</strong> {{ $cotizacion->cliente->nit }}</p>
            @endif
            @if ($cotizacion->cliente && $cotizacion->cliente->telefono)
                <p><strong>Tel:</strong> {{ $cotizacion->cliente->telefono }}</p>
            @endif
            <p><strong>Vendedor:</strong> {{ $cotizacion->user->name }}</p>
        </div>

        {{-- Validez --}}
        @if ($cotizacion->valida_hasta)
            <div class="validez">
                VÁLIDA HASTA: {{ \Carbon\Carbon::parse($cotizacion->valida_hasta)->format('d/m/Y') }}
            </div>
        @endif

        {{-- Tabla de Productos --}}
        <table class="productos">
            <thead>
                <tr>
                    <th width="12%">Cant</th>
                    <th width="48%">Producto</th>
                    <th width="20%">Precio</th>
                    <th width="20%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detalles as $detalle)
                    <tr>
                        <td class="text-center">{{ $detalle->cantidad }}</td>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="text-right">Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="text-right">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totales --}}
        <div class="totales">
            <div class="total-line">
                <span>SUBTOTAL:</span>
                <span>Bs {{ number_format($cotizacion->subtotal, 2) }}</span>
            </div>
            @if ((float) $cotizacion->descuento > 0)
                <div class="total-line">
                    <span>REBAJA:</span>
                    <span>- Bs {{ number_format($cotizacion->descuento, 2) }}</span>
                </div>
            @endif
            <div class="total-line total-grande">
                <strong>TOTAL:</strong>
                <strong>Bs {{ number_format($cotizacion->total, 2) }}</strong>
            </div>
        </div>

        {{-- Datos adicionales de la cotización --}}
        @php
            $observacionesData = json_decode($cotizacion->observaciones, true);
        @endphp

        @if ($observacionesData)
            <div class="datos-adicionales">
                <strong>📋 DATOS DE LA COTIZACIÓN:</strong><br>
                <strong>💰 Impuesto:</strong>
                {{ ($observacionesData['incluye_impuesto'] ?? 'con_impuesto') == 'con_impuesto' ? 'Con Impuesto de Ley' : 'Sin Impuesto' }}<br>
                <strong>💳 Forma de Pago:</strong> {{ ucfirst($observacionesData['forma_pago'] ?? 'contado') }}<br>
                @if (!empty($observacionesData['lugar_entrega']))
                    <strong>📍 Lugar de Entrega:</strong> {{ $observacionesData['lugar_entrega'] }}<br>
                @endif
                <strong>📦 Plazo de Entrega:</strong> {{ $observacionesData['plazo_entrega'] ?? 5 }} días hábiles<br>
                <strong>⏳ Validez Económica:</strong> {{ $observacionesData['validez_economica'] ?? 48 }} horas<br>
                @if (!empty($observacionesData['observaciones_adicionales']))
                    <strong>📝 Observaciones Adicionales:</strong>
                    {{ $observacionesData['observaciones_adicionales'] }}<br>
                @endif
                @if (!empty($observacionesData['notas']))
                    <strong>📌 Notas:</strong> {{ $observacionesData['notas'] }}<br>
                @endif
            </div>
        @elseif($cotizacion->observaciones)
            <div class="observaciones">
                <strong>Observaciones:</strong>
                <p>{{ $cotizacion->observaciones }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>Cotización válida hasta fecha indicada</p>
            <p>¡Gracias por su preferencia!</p>
        </div>

        {{-- Línea de corte (útil si se imprime varios en una hoja) --}}
        <div class="cut-line">
            - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        </div>
    </div>
</body>

</html>
