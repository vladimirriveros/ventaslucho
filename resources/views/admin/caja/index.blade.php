@extends('layouts.admin')

@section('title', 'Caja')

@section('content_header')
    <h1><i class="fas fa-cash-register"></i> Gestión de Caja</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Control de Caja por Sucursal</h3>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.items-caja')
        </div>
    </div>
@stop
