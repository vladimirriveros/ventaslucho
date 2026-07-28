@extends('layouts.admin')

@section('title', 'Reportes de Ventas')

@section('content_header')
    <h1><i class="fas fa-chart-line"></i> Reportes de Ventas</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Reportes Estadísticos</h3>
        </div>
        <div class="card-body">
            @livewire('admin.ventas.reportes-ventas')
        </div>
    </div>
@stop
