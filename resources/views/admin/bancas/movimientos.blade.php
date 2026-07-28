@extends('layouts.admin')

@section('title', 'Movimientos de Cuenta')

@section('content_header')
    <h1><i class="fas fa-history"></i> Movimientos de Cuenta</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Movimientos</h3>
        </div>
        <div class="card-body">
            @livewire('admin.bancas.movimientos-banca', ['bancaId' => $id])
        </div>
    </div>
@stop
