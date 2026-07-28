<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - Caja #{{ $caja->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Monaco', monospace;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            width: 100%;
            padding: 5px;
        }

        .ticket {
            max-width: 300px;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header p {
            font-size: 8px;
            margin: 2px 0;
        }

        .info-section {
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .info-label {
            font-weight: bold;
        }

        .table-header {
            font-weight: bold;
            border-bottom: 1px dotted #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 8px;
        }

        .producto-nombre {
            flex: 2;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .producto-cantidad {
            width: 30px;
            text-align: center;
        }

        .producto-precio {
            width: 50px;
            text-align: right;
        }

        .producto-subtotal {
            width: 55px;
            text-align: right;
        }

        .separator {
            border-top: 1px dotted #000;
            margin: 5px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-top: 5px;
            padding-top: 3px;
            border-top: 1px solid #000;
        }

        .metodos-pago {
            border-top: 1px dashed #000;
            margin-top: 8px;
            padding-top: 5px;
        }

        .metodo-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            margin-top: 8px;
            padding-top: 5px;
            font-size: 7px;
        }

        .venta-separator {
            border-top: 1px dotted #ccc;
            margin: 8px 0;
        }

        .venta-header {
            background: #f5f5f5;
            padding: 3px;
            margin: 5px 0;
            font-weight: bold;
            font-size: 8px;
        }

        .resumen-venta {
            font-size: 7px;
            margin-top: 3px;
            padding-left: 5px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- HEADER --}}
        <div class="header">
            <h1>CONSERDEI</h1>
            <p>RUC: 123456789</p>
            <p>{{ $sucursal->nombre ?? 'Sucursal Principal' }}</p>
            <p>Tel: {{ $sucursal->telefono ?? 'XXX-XXXXXXX' }}</p>
            <p>{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        {{-- INFORMACIÓN DE LA CAJA --}}
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">CAJA #:</span>
                <span>{{ $caja->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">APERTURA:</span>
                <span>{{ \Carbon\Carbon::parse($caja->fecha_apertura)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">CIERRE:</span>
                <span>{{ \Carbon\Carbon::parse($caja->fecha_cierre)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">VENDEDOR:</span>
                <span>{{ $caja->user->name ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- COLUMNAS DE LA TABLA --}}
        <div class="table-header">
            <span class="producto-nombre">PRODUCTO</span>
            <span class="producto-cantidad">CANT</span>
            <span class="producto-precio">P.UNIT</span>
            <span class="producto-subtotal">SUBTOTAL</span>
        </div>

        {{-- DETALLE DE VENTAS --}}
        @php
            $ventaActual = null;
            $totalGeneral = 0;
            $totalEfectivo = 0;
            $totalQR = 0;
            $totalTransferencia = 0;
            $totalTarjeta = 0;
        @endphp

        @foreach($detalles as $index => $detalle)
            @if($ventaActual != $detalle['venta_id'])
                @if($ventaActual !== null)
                    {{-- Resumen de la venta anterior --}}
                    <div class="resumen-venta">
                        <div class="info-row">
                            <span>💵 Total Venta:</span>
                            <span>Bs {{ number_format($ventaData['total'], 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span>💰 Pagado:</span>
                            <span>Bs {{ number_format($ventaData['pagado'], 2) }}</span>
                        </div>
                        @if($ventaData['pendiente'] > 0)
                            <div class="info-row">
                                <span>⏳ Pendiente:</span>
                                <span>Bs {{ number_format($ventaData['pendiente'], 2) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="venta-separator"></div>
                @endif

                {{-- Nueva venta --}}
                @php
                    $ventaActual = $detalle['venta_id'];
                    $ventaData = $detalle['venta_data'];

                    // Sumar a totales por método de pago
                    $metodo = $ventaData['metodo_pago'] ?? 'efectivo';
                    if ($metodo == 'efectivo') {
                        $totalEfectivo += $ventaData['pagado'];
                    } elseif ($metodo == 'qr') {
                        $totalQR += $ventaData['pagado'];
                    } elseif ($metodo == 'transferencia') {
                        $totalTransferencia += $ventaData['pagado'];
                    } elseif ($metodo == 'tarjeta') {
                        $totalTarjeta += $ventaData['pagado'];
                    }
                    $totalGeneral += $ventaData['total'];
                @endphp

                <div class="venta-header">
                    <div class="info-row">
                        <span>📄 VENTA #{{ $detalle['venta_codigo'] }}</span>
                        <span>{{ \Carbon\Carbon::parse($detalle['fecha'])->format('H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span>👤 {{ $detalle['cliente'] }}</span>
                        <span>{{ $detalle['vendedor'] }}</span>
                    </div>
                </div>
            @endif

            {{-- Detalle del producto --}}
            <div class="producto-item">
                <span class="producto-nombre">{{ $detalle['producto_nombre'] }}</span>
                <span class="producto-cantidad">{{ $detalle['cantidad'] }}</span>
                <span class="producto-precio">Bs {{ number_format($detalle['precio_venta'], 2) }}</span>
                <span class="producto-subtotal">Bs {{ number_format($detalle['subtotal_venta'], 2) }}</span>
            </div>

            @if($detalle['precio_compra'] > 0)
                <div class="producto-item" style="font-size: 7px; color: #666; padding-left: 5px;">
                    <span class="producto-nombre">└ Costo: Bs {{ number_format($detalle['precio_compra'], 2) }}</span>
                    <span class="producto-subtotal">Gan: Bs {{ number_format($detalle['ganancia'], 2) }}</span>
                </div>
            @endif
        @endforeach

        {{-- Resumen de la última venta --}}
        @if($ventaActual !== null)
            <div class="resumen-venta">
                <div class="info-row">
                    <span>💵 Total Venta:</span>
                    <span>Bs {{ number_format($ventaData['total'], 2) }}</span>
                </div>
                <div class="info-row">
                    <span>💰 Pagado:</span>
                    <span>Bs {{ number_format($ventaData['pagado'], 2) }}</span>
                </div>
                @if($ventaData['pendiente'] > 0)
                    <div class="info-row">
                        <span>⏳ Pendiente:</span>
                        <span>Bs {{ number_format($ventaData['pendiente'], 2) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="separator"></div>

        {{-- TOTAL GENERAL --}}
        <div class="total-row">
            <span>TOTAL GENERAL:</span>
            <span>Bs {{ number_format($totalGeneral, 2) }}</span>
        </div>

        {{-- DESGLOSE POR MÉTODO DE PAGO --}}
        <div class="metodos-pago">
            <div class="metodo-item">
                <span>💰 EFECTIVO:</span>
                <span>Bs {{ number_format($totalEfectivo, 2) }}</span>
            </div>
            <div class="metodo-item">
                <span>📱 QR:</span>
                <span>Bs {{ number_format($totalQR, 2) }}</span>
            </div>
            <div class="metodo-item">
                <span>🏦 TRANSFERENCIA:</span>
                <span>Bs {{ number_format($totalTransferencia, 2) }}</span>
            </div>
            <div class="metodo-item">
                <span>💳 TARJETA:</span>
                <span>Bs {{ number_format($totalTarjeta, 2) }}</span>
            </div>
        </div>

        {{-- RESUMEN DE CAJA --}}
        <div class="metodos-pago">
            <div class="metodo-item">
                <span>💰 MONTO INICIAL:</span>
                <span>Bs {{ number_format($caja->monto_inicial, 2) }}</span>
            </div>
            <div class="metodo-item">
                <span>💵 MONTO ESPERADO:</span>
                <span>Bs {{ number_format($caja->monto_esperado, 2) }}</span>
            </div>
            <div class="metodo-item">
                <span>💵 MONTO FINAL:</span>
                <span>Bs {{ number_format($caja->monto_final, 2) }}</span>
            </div>
            <div class="metodo-item {{ $caja->diferencia >= 0 ? 'text-success' : 'text-danger' }}">
                <span>📊 DIFERENCIA:</span>
                <span>{{ $caja->diferencia >= 0 ? '+' : '' }}Bs {{ number_format($caja->diferencia, 2) }}</span>
            </div>
        </div>

        {{-- ESTADÍSTICAS --}}
        <div class="metodos-pago">
            <div class="metodo-item">
                <span>📦 TOTAL VENTAS:</span>
                <span>{{ $ventas->count() }}</span>
            </div>
            <div class="metodo-item">
                <span>📦 PRODUCTOS VENDIDOS:</span>
                <span>{{ $resumen['cantidad_productos'] }}</span>
            </div>
            <div class="metodo-item">
                <span>📊 GANANCIA ESTIMADA:</span>
                <span>Bs {{ number_format($resumen['total_ganancia'], 2) }}</span>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <p>¡Gracias por su compra!</p>
            <p>*** Este documento es un comprobante de ventas del día ***</p>
            <p>CONSERDEI - Calidad y Confianza</p>
        </div>
    </div>
</body>
</html>
