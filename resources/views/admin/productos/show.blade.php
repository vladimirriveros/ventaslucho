@extends('layouts.admin')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detalle del Producto</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><b>Detalle del Producto: {{ $producto->nombre }}</b></h3>
                    <div class="card-tools">
                        <a href="{{ route('productos.historial', $producto->id) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-history"></i> Ver Historial de Precios
                        </a>
                        <a href="{{ route('productos.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-header">
                    <h3 class="card-title"><b>Datos Registrados</b></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- SECCION DE LOS DATOS DEL PRODUCTO --}}
                        <div class="col-md-9">
                            {{-- PRIMER ROW --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Categoría</label>
                                        <p>{{ $producto->categoria->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Código</label>
                                        <p>{{ $producto->codigo }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <p>{{ $producto->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Marca</label>
                                        <p>{{ $producto->marca->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <div>
                                            @if ($producto->estado == 1)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- SEGUNDO ROW --}}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Descripción</label>
                                        <p>{!! $producto->descripcion !!}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- TERCER ROW --}}
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Precio Compra</label>
                                        <p><span style="color: green"><b>Bs. </b></span>{{ $producto->precio_compra }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Precio Venta</label>
                                        <p><span style="color: green"><b>Bs. </b></span>{{ $producto->precio_venta }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Porcentaje de ganancia</label>
                                        <p>{{ $producto->porcentaje_ganancia }} <span style="color: green"><b>%</b></span></p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Mínimo</label>
                                        <p>{{ $producto->stock_minimo }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Máximo</label>
                                        <p>{{ $producto->stock_maximo }}</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Unidad de Medida</label>
                                        <p>{{ $producto->unidad_medida }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- CUARTO ROW: CAMPOS DE PLOMERÍA --}}
                            @if($producto->categoria && $producto->categoria->nombre == 'PLOMERIA')
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title"><b>Especificaciones de Plomería</b></h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Norma Técnica</label>
                                                        <p><strong>{{ $producto->norma ?? 'No especificada' }}</strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Presión de Trabajo</label>
                                                        <p><strong>{{ $producto->presion ?? 'No especificada' }}</strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Diámetro</label>
                                                        <p><strong>{{ $producto->diametro ?? 'No especificado' }}</strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- SECCION DE LA IMAGEN --}}
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Imagen del producto</label>
                                        <br><br>
                                        <img src="{{ asset('storage/'.$producto->imagen) }}" width="100%" alt="Imagen del producto">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
