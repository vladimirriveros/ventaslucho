@extends('layouts.admin')

@section('title', 'Panel principal')

@php
    $pct = static fn ($value, $total) => $total > 0 ? min(100, round(($value / $total) * 100)) : 0;

    $comprasPendientesPct = $pct($compras_pendientes, $compras_count);
    $comprasEnviadasPct = $pct($compras_enviadas, $compras_count);
    $comprasRecibidasPct = $pct($compras_recibidas, $compras_count);

    $salidasPendientesPct = $pct($salidas_pendientes, $salidas_count);
    $salidasProcesoPct = $pct($salidas_proceso, $salidas_count);
    $salidasEntregadasPct = $pct($salidas_entregadas, $salidas_count);
@endphp

@section('content_header')
    <div class="dashboard-hero">
        <div class="position-relative" style="z-index: 1;">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <div>
                    <h1>Hola, {{ auth()->user()->name }}</h1>
                    <p>Resumen general de operaciones, ventas e inventario.</p>
                </div>
                <div class="mt-3 mt-md-0 text-md-right">
                    <span class="badge badge-light px-3 py-2">
                        <i class="far fa-calendar-alt mr-1"></i>
                        {{ now()->translatedFormat('d \d\e F \d\e Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="metric-card">
                <span class="metric-icon success"><i class="fas fa-chart-line"></i></span>
                <div class="metric-label">Ingresos por ventas</div>
                <div class="metric-value">Bs {{ number_format($total_ingresos_ventas, 2) }}</div>
                <div class="metric-note">{{ number_format($total_ventas) }} ventas válidas registradas</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="metric-card">
                <span class="metric-icon"><i class="fas fa-coins"></i></span>
                <div class="metric-label">Ganancia estimada</div>
                <div class="metric-value">Bs {{ number_format($total_ganancia_ventas, 2) }}</div>
                <div class="metric-note">Ingresos menos costo de los lotes vendidos</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3 mb-md-0">
            <div class="metric-card">
                <span class="metric-icon warning"><i class="fas fa-boxes"></i></span>
                <div class="metric-label">Capital recibido en compras</div>
                <div class="metric-value">Bs {{ number_format($total_compras_lotes, 2) }}</div>
                <div class="metric-note">{{ number_format($total_productos_inventario) }} productos con existencia</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <span class="metric-icon danger"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="metric-label">Lotes vencidos con stock</div>
                <div class="metric-value">{{ number_format($total_lotes_vencidos) }}</div>
                <div class="metric-note">Requieren revisión y salida de inventario</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-cart mr-2 text-primary"></i>Estado de compras</h3>
                            <div class="card-tools">
                                <span class="badge badge-light">{{ $compras_count }} total</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Pendientes</span>
                                    <strong>{{ $compras_pendientes }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-warning" style="width: {{ $comprasPendientesPct }}%"></div></div>
                            </div>
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Enviadas al proveedor</span>
                                    <strong>{{ $compras_enviadas }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-info" style="width: {{ $comprasEnviadasPct }}%"></div></div>
                            </div>
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Recibidas</span>
                                    <strong>{{ $compras_recibidas }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-success" style="width: {{ $comprasRecibidasPct }}%"></div></div>
                            </div>
                        </div>
                        @can('compras.index')
                            <div class="card-footer bg-white text-right">
                                <a href="{{ route('compras.index') }}" class="btn btn-sm btn-outline-primary">
                                    Ver compras <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="col-md-6 mt-3 mt-md-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sign-out-alt mr-2 text-danger"></i>Estado de salidas</h3>
                            <div class="card-tools">
                                <span class="badge badge-light">{{ $salidas_count }} total</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Pendientes</span>
                                    <strong>{{ $salidas_pendientes }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-warning" style="width: {{ $salidasPendientesPct }}%"></div></div>
                            </div>
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>En proceso</span>
                                    <strong>{{ $salidas_proceso }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-info" style="width: {{ $salidasProcesoPct }}%"></div></div>
                            </div>
                            <div class="status-row">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Entregadas</span>
                                    <strong>{{ $salidas_entregadas }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar bg-success" style="width: {{ $salidasEntregadasPct }}%"></div></div>
                            </div>
                        </div>
                        @can('salidas.index')
                            <div class="card-footer bg-white text-right">
                                <a href="{{ route('salidas.index') }}" class="btn btn-sm btn-outline-primary">
                                    Ver salidas <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-warehouse mr-2 text-primary"></i>Inventario por sucursal</h3>
                    <div class="card-tools">
                        <span class="badge badge-light">{{ $total_sucursales }} sucursales</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sucursal</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventario_por_sucursal as $sucursalId => $inventario)
                                    <tr>
                                        <td>
                                            <strong>{{ $inventario['nombre'] }}</strong>
                                            <div class="small text-muted">Existencias disponibles</div>
                                        </td>
                                        <td class="text-center"><span class="badge badge-primary">{{ number_format($inventario['total_productos']) }}</span></td>
                                        <td class="text-center">{{ number_format($inventario['total_unidades']) }}</td>
                                        <td class="text-right">
                                            @can('mostrar_inventario_por_sucursal.show')
                                                <a href="{{ route('mostrar_inventario_por_sucursal.show', $sucursalId) }}" class="btn btn-sm btn-outline-primary" title="Ver inventario">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No hay sucursales registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-star mr-2 text-warning"></i>Productos con más salidas por venta</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productos_mas_salidas as $producto)
                                    <tr>
                                        <td><strong>{{ $producto->producto }}</strong></td>
                                        <td><code>{{ $producto->codigo }}</code></td>
                                        <td class="text-center">{{ number_format($producto->total_vendido) }}</td>
                                        <td class="text-right">Bs {{ number_format($producto->total_monto, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Aún no existen salidas por venta finalizadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2 text-warning"></i>Accesos rápidos</h3>
                </div>
                <div class="card-body">
                    @can('ventas.create')
                        <a href="{{ route('ventas.create') }}" class="quick-action">
                            <i class="fas fa-cash-register"></i>
                            <span><strong>Nueva venta</strong><small class="d-block text-muted">Registrar una operación</small></span>
                        </a>
                    @endcan
                    @can('cotizaciones.create')
                        <a href="{{ route('cotizaciones.create') }}" class="quick-action">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span><strong>Nueva cotización</strong><small class="d-block text-muted">Preparar propuesta comercial</small></span>
                        </a>
                    @endcan
                    @can('compras.create')
                        <a href="{{ route('compras.create') }}" class="quick-action">
                            <i class="fas fa-shopping-cart"></i>
                            <span><strong>Nueva compra</strong><small class="d-block text-muted">Registrar pedido a proveedor</small></span>
                        </a>
                    @endcan
                    @can('productos.create')
                        <a href="{{ route('productos.create') }}" class="quick-action">
                            <i class="fas fa-box"></i>
                            <span><strong>Nuevo producto</strong><small class="d-block text-muted">Añadir al catálogo</small></span>
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2 text-primary"></i>Resumen operativo</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Productos vendidos</span>
                        <strong>{{ number_format($total_productos_vendidos) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Costo de ventas</span>
                        <strong>Bs {{ number_format($total_costo_compras, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Cajas abiertas</span>
                        <strong>{{ number_format($total_cajas_abiertas) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Saldo en bancas</span>
                        <strong>Bs {{ number_format($total_saldo_bancas, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Cotizaciones activas</span>
                        <strong>{{ number_format($total_cotizaciones_activas) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-2">
                        <span class="text-muted">Proveedores</span>
                        <strong>{{ number_format($total_proveedores) }}</strong>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <span class="metric-icon warning mr-3"><i class="fas fa-info-circle"></i></span>
                        <div>
                            <strong>Control de inventario</strong>
                            <p class="text-muted small mb-2">Revisa periódicamente lotes vencidos, stock bajo y movimientos por sucursal.</p>
                            @can('lotes.index')
                                <a href="{{ route('lotes.index') }}" class="btn btn-sm btn-outline-primary">Revisar lotes</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
