@extends('layouts.admin')

@section('title', 'Nueva Venta')

@section('content_header')
    <h1><i class="fas fa-cash-register"></i> Nueva Venta</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registrar Venta</h3>
            <div class="card-tools">
                <a href="{{ route('ventas.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.items-venta')
        </div>
    </div>
@stop
