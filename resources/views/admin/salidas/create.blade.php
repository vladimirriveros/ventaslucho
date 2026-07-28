@extends('layouts.admin')

@section('title', 'Nueva Salida')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item">
                {{-- <a href="{{ url('/admin/salidas') }}">Salidas</a> --}}
                <a href="{{ route('salidas.index') }}">Salidas</a>
            </li>
            <li class="breadcrumb-item active">
                Creación de Salidas
            </li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-primary">

                <div class="card-header">
                    <h3 class="card-title">
                        <b>Paso 1 | Datos de la salida</b>
                    </h3>
                </div>

                <div class="card-body">

                    <form action="{{ route('salidas.store') }}" method="POST">
                        @csrf

                        <div class="row">

                            {{-- SUCURSAL ASIGNADA AL USUARIO --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Sucursal de salida</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="fas fa-store"></i></span>
                                        <div class="form-control bg-light">{{ $sucursal->nombre }}</div>
                                    </div>
                                    <small class="text-muted">No editable. Los productos se retirarán únicamente del inventario de esta sucursal.</small>
                                </div>
                            </div>

                            {{-- FECHA - CON VALOR POR DEFECTO = HOY --}}
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Fecha de salida <b style="color:red">(*)</b></label>

                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>

                                        {{-- Opción 1: Usando PHP --}}
                                        <input type="date" name="fecha" class="form-control"
                                            value="{{ old('fecha', date('Y-m-d')) }}" required>

                                        {{-- Opción 2: Usando Carbon (si prefieres) --}}
                                        {{-- <input type="date" name="fecha" class="form-control"
                                            value="{{ old('fecha', \Carbon\Carbon::now()->format('Y-m-d')) }}" required> --}}
                                    </div>

                                    {{-- Texto pequeño indicando que se puede cambiar --}}
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Por defecto se muestra la fecha actual. Puedes cambiarla si es necesario.
                                    </small>

                                    @error('fecha')
                                        <small style="color:red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- MOTIVO --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Motivo de salida <b style="color:red">(*)</b></label>

                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </span>

                                        <select name="motivo" class="form-control" required>
                                            <option value="">Seleccione motivo</option>
                                            {{-- <option value="Venta">tienda</option> --}}
                                            <option value="Daño">Producto dañado</option>
                                            {{-- <option value="Caducidad">Producto caducado</option> --}}
                                            <option value="Desperfecto">Desperfecto</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>

                                    @error('motivo')
                                        <small style="color:red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- OBSERVACIONES DE LA COMPRA --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                        </div>
                                        <input type="text" name="observaciones" id="observaciones" class="form-control"
                                            value="{{ old('observaciones') }}" placeholder="Observaciones de la compra">
                                    </div>
                                    @error('observaciones')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">

                                <a href="{{ url('/admin/salidas') }}" class="btn btn-secondary">
                                    Cancelar
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    Crear salida y añadir productos
                                </button>

                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@stop
