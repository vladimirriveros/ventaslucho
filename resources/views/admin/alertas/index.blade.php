@extends('layouts.admin')

@section('title', 'Centro de alertas')

@section('content_header')
    <div class="page-heading">
        <div>
            <span class="page-eyebrow">CONTROL OPERATIVO</span>
            <h1>Centro de alertas</h1>
            <p>Existencias críticas y lotes que requieren atención en {{ $alertas['alcance'] }}.</p>
        </div>
        <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
            <i class="fas fa-sync-alt mr-1"></i>Actualizar
        </button>
    </div>
@stop

@section('content')
    <div class="row mb-4">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <a href="#stock-bajo" class="alert-metric danger">
                <span class="alert-metric-icon"><i class="fas fa-box-open"></i></span>
                <span><small>Productos con stock bajo</small><strong>{{ $alertas['stock_bajo'] }}</strong></span>
            </a>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <a href="#por-vencer" class="alert-metric warning">
                <span class="alert-metric-icon"><i class="fas fa-hourglass-half"></i></span>
                <span><small>Lotes por vencer</small><strong>{{ $alertas['lotes_por_vencer'] }}</strong></span>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="#vencidos" class="alert-metric danger">
                <span class="alert-metric-icon"><i class="fas fa-calendar-times"></i></span>
                <span><small>Lotes vencidos con stock</small><strong>{{ $alertas['lotes_vencidos'] }}</strong></span>
            </a>
        </div>
    </div>

    <div class="card" id="stock-bajo">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-open mr-2 text-danger"></i>Stock bajo por sucursal</h3>
            <span class="badge badge-danger">{{ $alertas['stock_bajo'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 alert-table">
                    <thead><tr><th>Sucursal</th><th>Producto</th><th class="text-center">Actual</th><th class="text-center">Mínimo</th><th class="text-right">Estado</th></tr></thead>
                    <tbody>
                        @forelse($alertas['detalle_stock_bajo'] as $item)
                            <tr>
                                <td data-label="Sucursal">{{ $item['sucursal'] }}</td>
                                <td data-label="Producto"><strong>{{ $item['producto'] }}</strong><small class="d-block text-muted">{{ $item['codigo'] }}</small></td>
                                <td data-label="Actual" class="text-center"><strong>{{ $item['stock'] }}</strong></td>
                                <td data-label="Mínimo" class="text-center">{{ $item['stock_minimo'] }}</td>
                                <td data-label="Estado" class="text-right"><span class="badge {{ $item['sin_stock'] ? 'badge-danger' : 'badge-warning' }}">{{ $item['sin_stock'] ? 'Sin stock' : 'Reponer' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success mr-1"></i>No hay productos bajo el mínimo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4" id="por-vencer">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-hourglass-half mr-2 text-warning"></i>Lotes próximos a vencer</h3>
            <span class="badge badge-warning">Próximos {{ $alertas['dias_vencimiento'] }} días</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 alert-table">
                    <thead><tr><th>Sucursal</th><th>Producto / lote</th><th class="text-center">Cantidad</th><th class="text-right">Vencimiento</th></tr></thead>
                    <tbody>
                        @forelse($alertas['detalle_por_vencer'] as $item)
                            <tr>
                                <td data-label="Sucursal">{{ $item->sucursal }}</td>
                                <td data-label="Producto"><strong>{{ $item->producto }}</strong><small class="d-block text-muted">{{ $item->producto_codigo }} · Lote {{ $item->codigo_lote }}</small></td>
                                <td data-label="Cantidad" class="text-center">{{ $item->cantidad }}</td>
                                <td data-label="Vencimiento" class="text-right"><strong>{{ \Carbon\Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') }}</strong><small class="d-block text-warning">{{ $item->dias_restantes }} día(s)</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success mr-1"></i>No hay lotes próximos a vencer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4" id="vencidos">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-times mr-2 text-danger"></i>Lotes vencidos con existencia</h3>
            <span class="badge badge-danger">{{ $alertas['lotes_vencidos'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 alert-table">
                    <thead><tr><th>Sucursal</th><th>Producto / lote</th><th class="text-center">Cantidad</th><th class="text-right">Venció</th></tr></thead>
                    <tbody>
                        @forelse($alertas['detalle_vencidos'] as $item)
                            <tr>
                                <td data-label="Sucursal">{{ $item->sucursal }}</td>
                                <td data-label="Producto"><strong>{{ $item->producto }}</strong><small class="d-block text-muted">{{ $item->producto_codigo }} · Lote {{ $item->codigo_lote }}</small></td>
                                <td data-label="Cantidad" class="text-center">{{ $item->cantidad }}</td>
                                <td data-label="Venció" class="text-right text-danger"><strong>{{ \Carbon\Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success mr-1"></i>No hay lotes vencidos con stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
