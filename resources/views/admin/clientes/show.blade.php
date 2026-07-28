@extends('layouts.admin')

@section('title', 'Detalle Cliente')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detalle</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> Información del Cliente
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>ID</th>
                            <td>{{ $cliente->id }}</td>
                        </tr>
                        <tr>
                            <th>Nombre</th>
                            <td>{{ $cliente->nombre }}</td>
                        </tr>
                        <tr>
                            <th>NIT</th>
                            <td>{{ $cliente->nit ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Teléfono</th>
                            <td>{{ $cliente->telefono ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $cliente->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Dirección</th>
                            <td>{{ $cliente->direccion ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tipo</th>
                            <td>
                                @if($cliente->tipo == 'credito')
                                    <span class="badge badge-warning">Crédito</span>
                                @else
                                    <span class="badge badge-info">Regular</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Límite de Crédito</th>
                            <td>Bs {{ number_format($cliente->limite_credito, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Saldo Pendiente</th>
                            <td>Bs {{ number_format($cliente->saldo_pendiente, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                @if($cliente->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Observaciones</th>
                            <td>{{ $cliente->observaciones ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Registrado</th>
                            <td>{{ $cliente->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-cart"></i> Historial de Ventas
                    </h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Código</th>
                                <th>Total</th>
                                <th>Pagado</th>
                                <th>Pendiente</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cliente->ventas as $venta)
                            <tr>
                                <td>{{ $venta->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $venta->codigo }}</td>
                                <td class="text-right">Bs {{ number_format($venta->total, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pagado, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pendiente, 2) }}</td>
                                <td>
                                    @if($venta->estado == 'pagada')
                                        <span class="badge badge-success">Pagada</span>
                                    @elseif($venta->estado == 'pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">Anulada</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('ventas.nota-pdf', $venta->id) }}" target="_blank" rel="noopener" class="btn btn-info btn-sm" title="Ver nota de venta">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No hay ventas registradas</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
