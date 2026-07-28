@extends('layouts.admin')

@section('title', 'Nueva Cotización')

@section('content_header')
    <h1><i class="fas fa-file-invoice"></i> Nueva Cotización</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crear Cotización</h3>
            {{-- <div class="card-tools">
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div> --}}
            <div class="mb-3 text-right">
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-info btn-sm" title="Ver lista de cotizaciones">
                    <i class="fas fa-list"></i> Lista Cotizaciones
                </a>
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.items-cotizacion')
        </div>
    </div>
@stop
