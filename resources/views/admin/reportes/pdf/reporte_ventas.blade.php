<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 18px;
        }
        .info-box {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .info-box table {
            width: 100%;
            font-size: 9px;
        }
        .info-box td {
            padding: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-box {
            margin-top: 15px;
            border: 1px solid #28a745;
            padding: 10px;
            border-radius: 5px;
            background: #f8fff8;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .badge-success {
            color: #28a745;
            font-weight: bold;
        }
        .badge-warning {
            color: #ffc107;
            font-weight: bold;
        }
        .badge-danger {
            color: #dc3545;
            font-weight: bold;
        }
        h3 {
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE VENTAS</h1>
        <p>Sistema de Gestión - Conserdei</p>
    </div>

    <div class="info-box">
        <table>
             <tr>
                <td width="50%"><strong>Tipo de Reporte:</strong>
                    @if($tipo_reporte == 'diario') Reporte Diario
                    @elseif($tipo_reporte == 'mensual') Reporte Mensual
                    @else Reporte por Vendedores
                    @endif
                </td>
                <td width="50%"><strong>Fecha Generación:</strong> {{ $fecha_generacion->format('d/m/Y H:i:s') }}</td>
             </tr>
             @if($tipo_reporte == 'diario')
             <tr>
                <td><strong>Período:</strong> {{ \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha_hasta)->format('d/m/Y') }}</td>
                <td><strong>Sucursal:</strong> {{ $sucursal ? $sucursal->nombre : 'Todas' }}</td>
             </tr>
             <tr>
                <td><strong>Vendedor:</strong> {{ $vendedor ? $vendedor->name : 'Todos' }}</td>
                <td></td>
             </tr>
             @endif
             @if($tipo_reporte == 'mensual')
             <tr>
                <td><strong>Mes:</strong> {{ \Carbon\Carbon::create()->month($mes_seleccionado)->locale('es')->monthName }} {{ $anio_seleccionado }}</td>
                <td><strong>Sucursal:</strong> {{ $sucursal ? $sucursal->nombre : 'Todas' }}</td>
             </tr>
             <tr>
                <td><strong>Vendedor:</strong> {{ $vendedor ? $vendedor->name : 'Todos' }}</td>
                <td></td>
             </tr>
             @endif
             @if($tipo_reporte == 'vendedores')
             <tr>
                <td><strong>Período:</strong> {{ \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha_hasta)->format('d/m/Y') }}</td>
                <td><strong>Sucursal:</strong> {{ $sucursal ? $sucursal->nombre : 'Todas' }}</td>
             </tr>
             @endif
         </table>
    </div>

    @if($tipo_reporte == 'diario' || $tipo_reporte == 'mensual')
        {{-- Resumen --}}
        <div class="total-box">
            <table style="border: none;">
                <tr>
                    <td style="border: none;"><strong>Total Ventas:</strong></td>
                    <td style="border: none;" class="text-right">{{ number_format($resumen['total_ventas'] ?? 0) }}</td>
                    <td style="border: none;" width="20%"></td>
                    <td style="border: none;"><strong>Total Ingresos:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Ventas Contado:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['total_contado'] ?? 0, 2) }}</td>
                    <td style="border: none;"></td>
                    <td style="border: none;"><strong>Ventas Crédito:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['total_credito'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Pendiente de Cobro:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['total_pendiente'] ?? 0, 2) }}</td>
                    <td style="border: none;"></td>
                    <td style="border: none;"><strong>Promedio por Venta:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['promedio_venta'] ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Tabla de Ventas --}}
        <h3>Detalle de Ventas</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Sucursal</th>
                    <th>Tipo</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Pagado</th>
                    <th class="text-right">Pendiente</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $venta)
                <tr>
                    <td class="text-center">{{ $venta->id }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $venta->codigo }}</td>
                    <td>{{ $venta->cliente ? $venta->cliente->nombre : 'CLIENTE OCASIONAL' }}</td>
                    <td>{{ $venta->user->name }}</td>
                    <td>{{ $venta->sucursal->nombre }}</td>
                    <td class="text-center">{{ $venta->tipo == 'contado' ? 'Contado' : 'Crédito' }}</td>
                    <td class="text-right">Bs {{ number_format($venta->total, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($venta->pagado, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($venta->pendiente, 2) }}</td>
                    <td class="text-center">{{ $venta->estado == 'pagada' ? 'Pagada' : ($venta->estado == 'pendiente' ? 'Pendiente' : 'Anulada') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7" class="text-right">TOTALES:</th>
                    <th class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>

        {{-- Productos más vendidos --}}
        @if(count($productos_mas_vendidos) > 0)
        <h3>Top 10 Productos Más Vendidos</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos_mas_vendidos as $index => $producto)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $producto->nombre }} ({{ $producto->codigo }})</td>
                    <td class="text-center">{{ $producto->total_cantidad }}</td>
                    <td class="text-right">Bs {{ number_format($producto->total_venta, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Métodos de Pago --}}
        @if(count($metodos_pago) > 0)
        <h3>Métodos de Pago</h3>
        <table>
            <thead>
                <tr>
                    <th>Método</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metodos_pago as $metodo => $total)
                <tr>
                    <td>{{ $metodo }}</td>
                    <td class="text-right">Bs {{ number_format($total, 2) }}</td>
                    <td class="text-right">{{ number_format(($total / ($resumen['total_ingresos'] ?? 1)) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @endif

    @if($tipo_reporte == 'vendedores')
        {{-- Resumen Vendedores --}}
        <div class="total-box">
            <table style="border: none;">
                <tr>
                    <td style="border: none;"><strong>Vendedores Activos:</strong></td>
                    <td style="border: none;" class="text-right">{{ number_format($resumen['total_vendedores'] ?? 0) }}</td>
                    <td style="border: none;" width="20%"></td>
                    <td style="border: none;"><strong>Total Ventas:</strong></td>
                    <td style="border: none;" class="text-right">{{ number_format($resumen['total_ventas'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Total Ingresos:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</td>
                    <td style="border: none;"></td>
                    <td style="border: none;"><strong>Promedio por Vendedor:</strong></td>
                    <td style="border: none;" class="text-right">Bs {{ number_format($resumen['promedio_ingresos'] ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Ranking de Vendedores --}}
        <h3>Ranking de Vendedores</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendedor</th>
                    <th class="text-right">Ventas</th>
                    <th class="text-right">Total Ingresos</th>
                    <th class="text-right">Promedio</th>
                    <th class="text-right">Contado</th>
                    <th class="text-right">Crédito</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_vendedores as $index => $vendedor)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $vendedor->name }}</strong></td>
                    <td class="text-right">{{ number_format($vendedor->total_ventas) }}</td>
                    <td class="text-right">Bs {{ number_format($vendedor->total_ingresos, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($vendedor->promedio_venta, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($vendedor->contado, 2) }}</td>
                    <td class="text-right">Bs {{ number_format($vendedor->credito, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">TOTALES:</th>
                    <th class="text-right">{{ number_format($resumen['total_ventas'] ?? 0) }}</th>
                    <th class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>

        {{-- Mejor y Peor Vendedor --}}
        <div class="info-box">
            <table style="border: none;">
                <tr>
                    <td style="border: none;"><strong>🏆 Mejor Vendedor:</strong> {{ $resumen['vendedor_top']->name ?? 'N/A' }}</td>
                    <td style="border: none;" class="text-right">Ventas: {{ number_format($resumen['vendedor_top']->total_ventas ?? 0) }} | Ingresos: Bs {{ number_format($resumen['vendedor_top']->total_ingresos ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>📉 Peor Vendedor:</strong> {{ $resumen['vendedor_bottom']->name ?? 'N/A' }}</td>
                    <td style="border: none;" class="text-right">Ventas: {{ number_format($resumen['vendedor_bottom']->total_ventas ?? 0) }} | Ingresos: Bs {{ number_format($resumen['vendedor_bottom']->total_ingresos ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Gestión - Conserdei</p>
        <p>{{ $fecha_generacion->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
