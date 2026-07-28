@extends('layouts.admin')

@section('title', 'Listado Cotizaciones')

@section('content_header')
    <h1><i class="fas fa-file-invoice"></i> Cotizaciones</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Cotizaciones</h3>
            <div class="card-tools">
                <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva Cotización
                </a>
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.lista-cotizaciones')
        </div>
    </div>
@stop
