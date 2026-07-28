@extends('layouts.admin')

@section('title', 'Inventario por sucursal')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-warehouse mr-2"></i>Inventario por sucursal</h1>
            <p class="text-muted mb-0">Existencias disponibles y alertas de reposición por ubicación.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        @forelse ($sucursales as $sucursal)
            <div class="col-12 col-sm-6 col-xl-4 mb-3">
                <a class="inventory-branch-card {{ $sucursal->tiene_stock_bajo ? 'has-alert' : '' }}"
                   href="{{ route('mostrar_inventario_por_sucursal.show', $sucursal->id) }}">
                    <span class="inventory-branch-icon"><i class="fas fa-store"></i></span>
                    <span class="inventory-branch-body">
                        <strong>{{ $sucursal->nombre }}</strong>
                        <small>{{ number_format((int) $sucursal->total_inventario) }} unidades disponibles</small>
                        @if ($sucursal->stock_bajo_count > 0)
                            <span class="inventory-branch-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ $sucursal->stock_bajo_count }} producto(s) con stock bajo
                            </span>
                        @else
                            <span class="inventory-branch-ok"><i class="fas fa-check-circle"></i> Inventario estable</span>
                        @endif
                    </span>
                    <i class="fas fa-chevron-right inventory-branch-arrow"></i>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No existen sucursales activas disponibles para su usuario.</div>
            </div>
        @endforelse
    </div>
@endsection
