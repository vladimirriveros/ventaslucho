@extends('layouts.admin')

@section('title', 'Listado Ventas')

@section('content_header')
    <h1><i class="fas fa-cash-register"></i> Ventas</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Ventas</h3>
            <div class="card-tools">
                <a href="{{ route('ventas.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nueva Venta
                </a>
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.lista-ventas')
        </div>
    </div>
@stop
