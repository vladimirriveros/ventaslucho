@extends('layouts.admin')

@section('title', 'Nueva Cuenta Bancaria')

@section('content_header')
    <h1><i class="fas fa-university"></i> Nueva Cuenta Bancaria</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crear Cuenta Bancaria</h3>
        </div>
        <div class="card-body">
            @livewire('admin.bancas.items-banca')
        </div>
    </div>
@stop
