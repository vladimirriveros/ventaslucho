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
                @can('ventas.create')
                    <a href="{{ route('ventas.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nueva Venta
                    </a>
                @else
                    <span class="badge badge-info px-3 py-2"><i class="fas fa-eye mr-1"></i>Solo consulta</span>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.lista-ventas')
        </div>
    </div>
@stop
