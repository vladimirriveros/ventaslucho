@extends('layouts.admin')

@section('title', 'Editar Cuenta Bancaria')

@section('content_header')
    <h1><i class="fas fa-university"></i> Editar Cuenta Bancaria</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editar Cuenta Bancaria #{{ $id }}</h3>
        </div>
        <div class="card-body">
            @livewire('admin.bancas.items-banca', ['banca' => App\Models\Banca::find($id)])
        </div>
    </div>
@stop
