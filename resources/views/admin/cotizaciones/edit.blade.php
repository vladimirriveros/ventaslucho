@extends('layouts.admin')

@section('title', 'Editar Cotización')

@section('content_header')
    <h1><i class="fas fa-file-invoice"></i> Editar Cotización</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editar Cotización #{{ $id }}</h3>
            <div class="card-tools">
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.items-cotizacion', ['cotizacion' => App\Models\Cotizacion::find($id)])
        </div>
    </div>
@stop
