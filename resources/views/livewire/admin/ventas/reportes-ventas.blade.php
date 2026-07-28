<div>
    {{-- Filtros --}}
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas fa-filter"></i> Filtros de Reporte
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo de Reporte</label>
                        <select wire:model.live="tipo_reporte" class="form-control">
                            <option value="diario">Reporte Diario</option>
                            <option value="mensual">Reporte Mensual</option>
                            <option value="vendedores">Reporte por Vendedores</option>
                        </select>
                    </div>
                </div>

                @if($tipo_reporte == 'diario')
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha Desde</label>
                        <input type="date" wire:model.live="fecha_desde" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha Hasta</label>
                        <input type="date" wire:model.live="fecha_hasta" class="form-control">
                    </div>
                </div>
                @endif

                @if($tipo_reporte == 'mensual')
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Mes</label>
                        <select wire:model.live="mes_seleccionado" class="form-control">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ Carbon\Carbon::create()->month($i)->locale('es')->monthName }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Año</label>
                        <select wire:model.live="anio_seleccionado" class="form-control">
                            @for($i = Carbon\Carbon::now()->year - 2; $i <= Carbon\Carbon::now()->year; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                @endif

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Sucursal</label>
                        <select wire:model.live="sucursal_id" class="form-control">
                            <option value="">Todas las sucursales</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vendedor</label>
                        <select wire:model.live="vendedor_id" class="form-control">
                            <option value="">Todos los vendedores</option>
                            @foreach($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12 text-right">
                    <button class="btn btn-success" wire:click="exportarPDF" wire:loading.attr="disabled">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                    <button class="btn btn-info" wire:click="exportarExcel" wire:loading.attr="disabled">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reporte Diario / Mensual --}}
    @if($tipo_reporte == 'diario' || $tipo_reporte == 'mensual')
        {{-- Tarjetas de Resumen --}}
        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($resumen['total_ventas'] ?? 0) }}</h3>
                        <p>Total Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</h3>
                        <p>Total Ingresos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Bs {{ number_format($resumen['promedio_venta'] ?? 0, 2) }}</h3>
                        <p>Promedio por Venta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>Bs {{ number_format($resumen['total_pendiente'] ?? 0, 2) }}</h3>
                        <p>Pendiente de Cobro</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico de Ventas por Día --}}
        @if($ventas_por_dia && $ventas_por_dia->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Ventas por {{ $tipo_reporte == 'diario' ? 'Día' : 'Día del Mes' }}
                </h3>
            </div>
            <div class="card-body">
                <canvas id="ventasChart" style="height: 300px;"></canvas>
            </div>
        </div>
        @endif

        {{-- Tabla de Ventas --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Detalle de Ventas
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ count($ventas) }} ventas</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if(count($ventas) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="bg-light">
                            <tr class="text-center">
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
                                <td class="text-center">{{ Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $venta->codigo }}</td>
                                <td>{{ $venta->cliente ? $venta->cliente->nombre : 'CLIENTE OCASIONAL' }}</td>
                                <td>{{ $venta->user->name }}</td>
                                <td>{{ $venta->sucursal->nombre }}</td>
                                <td class="text-center">
                                    @if($venta->tipo == 'contado')
                                        <span class="badge badge-success">Contado</span>
                                    @else
                                        <span class="badge badge-warning">Crédito</span>
                                    @endif
                                </td>
                                <td class="text-right">Bs {{ number_format($venta->total, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pagado, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pendiente, 2) }}</td>
                                <td class="text-center">
                                    @if($venta->estado == 'pagada')
                                        <span class="badge badge-success">Pagada</span>
                                    @elseif($venta->estado == 'pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">Anulada</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="7" class="text-right">TOTALES:</th>
                                <th class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle"></i> No hay ventas en el período seleccionado.
                </div>
                @endif
            </div>
        </div>

        {{-- Métodos de Pago y Productos más Vendidos --}}
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-credit-card"></i> Métodos de Pago
                        </h3>
                    </div>
                    <div class="card-body">
                        @if(count($metodos_pago) > 0)
                        <table class="table table-sm">
                            @foreach($metodos_pago as $metodo => $total)
                            <tr>
                                <td><strong>{{ $metodo }}</strong></td>
                                <td class="text-right">Bs {{ number_format($total, 2) }}</td>
                                <td class="text-right">
                                    @php
                                        $porcentaje = ($resumen['total_ingresos'] ?? 1) > 0 ? ($total / ($resumen['total_ingresos'] ?? 1)) * 100 : 0;
                                    @endphp
                                    <div class="progress">
                                        <div class="progress-bar" style="width: {{ $porcentaje }}%">{{ number_format($porcentaje, 1) }}%</div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </table>
                        @else
                        <div class="alert alert-info">No hay datos de pagos registrados.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Top 10 Productos Más Vendidos
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        @if(count($productos_mas_vendidos) > 0)
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
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
                        @else
                        <div class="alert alert-info m-3">No hay productos vendidos en el período.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reporte por Vendedores --}}
    @if($tipo_reporte == 'vendedores')
        {{-- Tarjetas de Resumen --}}
        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($resumen['total_vendedores'] ?? 0) }}</h3>
                        <p>Vendedores Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($resumen['total_ventas'] ?? 0) }}</h3>
                        <p>Total Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</h3>
                        <p>Total Ingresos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>Bs {{ number_format($resumen['promedio_ingresos'] ?? 0, 2) }}</h3>
                        <p>Promedio por Vendedor</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de Vendedores --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trophy"></i> Ranking de Vendedores
                </h3>
            </div>
            <div class="card-body p-0">
                @if(count($top_vendedores) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th>#</th>
                                <th>Vendedor</th>
                                <th class="text-right">Ventas</th>
                                <th class="text-right">Total Ingresos</th>
                                <th class="text-right">Promedio por Venta</th>
                                <th class="text-right">Contado</th>
                                <th class="text-right">Crédito</th>
                                <th>Mejor Producto</th>
                              </tr>
                        </thead>
                        <tbody>
                            @foreach($top_vendedores as $index => $vendedor)
                             <tr class="{{ $index == 0 ? 'bg-success text-white' : '' }}">
                                <td class="text-center">
                                    @if($index == 0)
                                        <i class="fas fa-crown fa-lg"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                 </td>
                                 <td>
                                    <strong>{{ $vendedor->name }}</strong>
                                    @if($index == 0)
                                        <span class="badge badge-warning ml-2">TOP VENDEDOR</span>
                                    @endif
                                 </td>
                                 <td class="text-right">{{ number_format($vendedor->total_ventas) }}</td>
                                 <td class="text-right">Bs {{ number_format($vendedor->total_ingresos, 2) }}</td>
                                 <td class="text-right">Bs {{ number_format($vendedor->promedio_venta, 2) }}</td>
                                 <td class="text-right">Bs {{ number_format($vendedor->contado, 2) }}</td>
                                 <td class="text-right">Bs {{ number_format($vendedor->credito, 2) }}</td>
                                 <td>
                                    @php
                                        $mejorProducto = \App\Models\DetalleVenta::select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total'))
                                            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                                            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                                            ->where('ventas.user_id', $vendedor->id)
                                            ->whereBetween('ventas.fecha', [
                                                $fecha_desde ? Carbon\Carbon::parse($fecha_desde)->startOfDay() : Carbon\Carbon::now()->startOfMonth(),
                                                $fecha_hasta ? Carbon\Carbon::parse($fecha_hasta)->endOfDay() : Carbon\Carbon::now()->endOfDay()
                                            ])
                                            ->groupBy('productos.id', 'productos.nombre')
                                            ->orderBy('total', 'desc')
                                            ->first();
                                    @endphp
                                    {{ $mejorProducto ? $mejorProducto->nombre . ' (' . $mejorProducto->total . ' unid.)' : 'N/A' }}
                                 </td>
                              </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                             <tr>
                                <th colspan="2" class="text-right">TOTALES:</th>
                                <th class="text-right">{{ number_format($resumen['total_ventas'] ?? 0) }}</th>
                                <th class="text-right">Bs {{ number_format($resumen['total_ingresos'] ?? 0, 2) }}</th>
                                <th colspan="4"></th>
                             </tr>
                        </tfoot>
                     </table>
                </div>

                {{-- Mejor y Peor Vendedor --}}
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <i class="fas fa-chart-line"></i>
                            <strong>🏆 Mejor Vendedor:</strong> {{ $resumen['vendedor_top']->name ?? 'N/A' }}<br>
                            <small>Ventas: {{ number_format($resumen['vendedor_top']->total_ventas ?? 0) }} |
                                   Ingresos: Bs {{ number_format($resumen['vendedor_top']->total_ingresos ?? 0, 2) }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-danger">
                            <i class="fas fa-chart-line"></i>
                            <strong>📉 Peor Vendedor:</strong> {{ $resumen['vendedor_bottom']->name ?? 'N/A' }}<br>
                            <small>Ventas: {{ number_format($resumen['vendedor_bottom']->total_ventas ?? 0) }} |
                                   Ingresos: Bs {{ number_format($resumen['vendedor_bottom']->total_ingresos ?? 0, 2) }}</small>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle"></i> No hay ventas registradas en el período seleccionado.
                </div>
                @endif
            </div>
        </div>

        {{-- Productos más vendidos por vendedor (si se selecciona uno) --}}
        @if($vendedor_id && count($productos_mas_vendidos) > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i> Top Productos Vendidos por {{ \App\Models\User::find($vendedor_id)->name ?? 'Vendedor' }}
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
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
            </div>
        </div>
        @endif
    @endif
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('renderizar-grafico', () => {
            @if($ventas_por_dia && $ventas_por_dia->count() > 0)
            const ctx = document.getElementById('ventasChart').getContext('2d');

            // Destruir gráfico existente si lo hay
            if (window.ventasChart) {
                window.ventasChart.destroy();
            }

            const dias = @json($ventas_por_dia->pluck('dia')->map(function($dia) {
                return is_numeric($dia) ? $dia : \Carbon\Carbon::parse($dia)->format('d/m');
            }));
            const ingresos = @json($ventas_por_dia->pluck('total_ingresos'));
            const cantidades = @json($ventas_por_dia->pluck('total_ventas'));

            window.ventasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dias,
                    datasets: [
                        {
                            label: 'Ingresos (Bs)',
                            data: ingresos,
                            backgroundColor: 'rgba(40, 167, 69, 0.5)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Cantidad de Ventas',
                            data: cantidades,
                            backgroundColor: 'rgba(23, 162, 184, 0.5)',
                            borderColor: 'rgba(23, 162, 184, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw;
                                    if (context.dataset.label === 'Ingresos (Bs)') {
                                        return `${label}: Bs ${value.toFixed(2)}`;
                                    }
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Ingresos (Bs)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Bs ' + value.toFixed(2);
                                }
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Ventas'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            @endif
        });

        // Disparar renderizado del gráfico después de cargar
        setTimeout(() => {
            Livewire.dispatch('renderizar-grafico');
        }, 500);
    });

    // Escuchar cambios en los filtros para actualizar gráfico
    window.addEventListener('livewire:updated', () => {
        setTimeout(() => {
            Livewire.dispatch('renderizar-grafico');
        }, 300);
    });
</script>
@endpush
