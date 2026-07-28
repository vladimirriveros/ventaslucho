@extends('layouts.admin')

@section('title', isset($cliente) ? 'Editar Cliente' : 'Nuevo Cliente')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ isset($cliente) ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas {{ isset($cliente) ? 'fa-edit' : 'fa-plus' }}"></i>
                {{ isset($cliente) ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h3>
            <div class="card-tools">
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <form action="{{ isset($cliente) ? route('clientes.update', $cliente->id) : route('clientes.store') }}" method="POST">
            @csrf
            @if(isset($cliente)) @method('PUT') @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $cliente->nombre ?? '') }}" required>
                            @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NIT</label>
                            <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror"
                                   value="{{ old('nit', $cliente->nit ?? '') }}">
                            @error('nit') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="{{ old('telefono', $cliente->telefono ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $cliente->email ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo de Cliente <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-control @error('tipo') is-invalid @enderror" id="tipo_cliente" required>
                                <option value="regular" {{ old('tipo', $cliente->tipo ?? 'regular') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="credito" {{ old('tipo', $cliente->tipo ?? '') == 'credito' ? 'selected' : '' }}>Crédito</option>
                            </select>
                            @error('tipo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4" id="limite_credito_div" style="display: {{ old('tipo', $cliente->tipo ?? 'regular') == 'credito' ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label>Límite de Crédito</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Bs</span>
                                </div>
                                <input type="number" name="limite_credito" class="form-control" step="0.01" min="0"
                                       value="{{ old('limite_credito', $cliente->limite_credito ?? 0) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="1">{{ old('observaciones', $cliente->observaciones ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ isset($cliente) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </form>
    </div>
@stop

@push('js')
<script>
    document.getElementById('tipo_cliente').addEventListener('change', function() {
        var limiteDiv = document.getElementById('limite_credito_div');
        if (this.value === 'credito') {
            limiteDiv.style.display = 'block';
        } else {
            limiteDiv.style.display = 'none';
        }
    });
</script>
@endpush
