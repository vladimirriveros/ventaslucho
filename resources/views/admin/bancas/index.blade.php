@extends('layouts.admin')

@section('title', 'Cuentas Bancarias')

@section('content_header')
    <h1><i class="fas fa-university"></i> Cuentas Bancarias</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Cuentas Bancarias</h3>
        </div>
        <div class="card-body">
            @livewire('admin.bancas.lista-bancas')
        </div>
    </div>
@stop
