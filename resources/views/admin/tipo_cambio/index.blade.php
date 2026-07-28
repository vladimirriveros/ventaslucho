{{-- resources/views/admin/tipo_cambio/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Tipos de Cambio')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tipo_cambio.index') }}">Tipos de cambio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Listado</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    {{-- TARJETA INFORMATIVA SUPERIOR --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b><i class="fas fa-info-circle"></i> Información de Tipos de Cambio</b></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-bank"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tipo de Cambio OFICIAL</span>
                                    <span class="info-box-number">
                                        @if ($tipoCambioOficial)
                                            1 USD = {{ number_format($tipoCambioOficial->precio_dolar, 2) }} Bs
                                            <br>
                                            <small>Actualizado:
                                                {{ $tipoCambioOficial->updated_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-warning">No definido</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tipo de Cambio ACTIVO (visualización)</span>
                                    <span class="info-box-number">
                                        @if ($tipoCambioActivo)
                                            1 USD = {{ number_format($tipoCambioActivo->precio_dolar, 2) }} Bs
                                            <br>
                                            <small>
                                                @if ($tipoCambioActivo->is_oficial)
                                                    <span class="badge badge-primary">OFICIAL</span>
                                                @else
                                                    <span class="badge badge-warning">ALTERNATIVO</span>
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-warning">Ninguno activo</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Mostrar última actualización de precios --}}
                    {{-- Mostrar última actualización de precios --}}
                    @if ($ultimoTipoCambioUsado && $ultimaAccion == 'actualizar_precios')
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-warning mb-0">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3">
                                            <i class="fas fa-history fa-2x"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <strong>Última actualización de precios de venta:</strong><br>

                                            {{-- Tipo de cambio usado --}}
                                            <div class="mb-2">
                                                <span class="badge badge-warning">Tipo de cambio</span>
                                                <strong>1 USD = {{ number_format($ultimoTipoCambioUsado['precio'], 2) }}
                                                    Bs</strong>
                                            </div>

                                            {{-- Filtros aplicados --}}
                                            @if ($ultimosFiltros)
                                                <div class="mb-2">
                                                    <span class="badge badge-info">Alcance</span>
                                                    @if ($ultimosFiltros['tipo'] == 'todos')
                                                        <span class="text-success"><i class="fas fa-globe"></i> TODOS los
                                                            productos</span>
                                                    @else
                                                        <span class="text-primary"><i class="fas fa-filter"></i> Productos
                                                            filtrados</span>

                                                        {{-- Mostrar categorías seleccionadas --}}
                                                        @if (!empty($nombresCategorias))
                                                            <div class="mt-1">
                                                                <small class="text-muted"><i class="fas fa-tags"></i>
                                                                    Categorías:</small>
                                                                @foreach ($nombresCategorias as $categoria)
                                                                    <span
                                                                        class="badge badge-warning">{{ $categoria }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        {{-- Mostrar marcas seleccionadas --}}
                                                        @if (!empty($nombresMarcas))
                                                            <div class="mt-1">
                                                                <small class="text-muted"><i class="fas fa-copyright"></i>
                                                                    Marcas:</small>
                                                                @foreach ($nombresMarcas as $marca)
                                                                    <span
                                                                        class="badge badge-info">{{ $marca }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- Fórmula --}}
                                            <small class="text-muted d-block mt-2">
                                                <i class="fas fa-calculator"></i>
                                                Fórmula: (precio_compra × (nuevo_tipo ÷ tipo_oficial)) × (1 + %ganancia ÷
                                                100)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta si no hay tipo de cambio oficial --}}
    @if (!$tipoCambioOficial)
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fas fa-exclamation-triangle fa-2x float-left mr-3"></i>
                    <h5><b>⚠️ No hay tipo de cambio oficial definido</b></h5>
                    <p class="mb-0">Para poder actualizar precios, debes establecer un tipo de cambio como OFICIAL usando
                        el botón <span class="badge badge-primary">Asignar como TC Oficial</span> en la tabla.</p>
                    <small>El tipo de cambio oficial es la base para la fórmula: (precio_compra × (nuevo_tipo ÷
                        tipo_oficial)) × (1 + %ganancia ÷ 100)</small>
                </div>
            </div>
        </div>
    @endif

    {{-- SECCIÓN DE AYUDA --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><b><i class="fas fa-question-circle"></i> ¿Cómo funciona?</b></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-primary text-center">
                                <i class="fas fa-star fa-2x"></i>
                                <h5><b>Establecer como OFICIAL</b></h5>
                                <p>Define el tipo de cambio base para cálculos</p>
                                <small>⭐ Solo uno puede ser oficial</small>
                            </div>
                        </div>
                        {{-- <div class="col-md-3">
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                                <h5><b>ACTIVAR</b></h5>
                                <p>Para mostrar precios en la interfaz</p>
                                <small>Puede ser oficial o alternativo</small>
                            </div>
                        </div> --}}
                        <div class="col-md-6">
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-calculator fa-2x"></i>
                                <h5><b>Actualizar precios de venta</b></h5>
                                <p>Usa la fórmula con el oficial</p>
                                <small>Precio_venta = (compra * (nuevo/oficial)) + %</small>
                            </div>
                        </div>
                        {{-- <div class="col-md-4">
                            <div class="alert alert-secondary text-center">
                                <i class="fas fa-percent fa-2x"></i>
                                <h5><b>Recalcular por ganancia</b></h5>
                                <p>Solo aplica % de ganancia actual</p>
                                <small>Mantiene tipo de cambio</small>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE TIPOS DE CAMBIO --}}
    {{-- TABLA DE TIPOS DE CAMBIO --}}
    {{-- TABLA DE TIPOS DE CAMBIO --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b>Listado de Tipos de Cambio</b></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTipoCambio">
                            <i class="fas fa-plus"></i> Nuevo tipo de cambio
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Tipo de Cambio</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th class="text-center" colspan="4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tipoCambio as $index => $cambio)
                                <tr class="{{ $cambio->estado ? 'table-success' : '' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>1 USD = {{ number_format($cambio->precio_dolar, 2) }} Bs</strong>
                                        @if ($cambio->is_oficial)
                                            <span class="badge badge-primary">OFICIAL</span>
                                        @endif
                                    </td>
                                    <td>{{ $cambio->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($cambio->is_oficial)
                                            <span class="badge badge-primary">Oficial</span>
                                        @else
                                            <span class="badge badge-secondary">Alternativo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($cambio->estado)
                                            <span class="badge badge-success" style="font-size: 0.9rem;">
                                                <i class="fas fa-check-circle"></i> ACTIVO
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times-circle"></i> Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Botón: Establecer como OFICIAL --}}
                                    <td class="text-center" style="width: 10%">
                                        @if (!$cambio->is_oficial)
                                            <button type="button" class="btn btn-primary btn-sm btn-block"
                                                onclick="confirmarSetOficial({{ $cambio->precio_dolar }}, {{ $cambio->id }})">
                                                <i class="fas fa-star"></i> Asignar como TC Oficial
                                            </button>
                                        @else
                                            <span class="badge badge-primary">Es oficial</span>
                                        @endif
                                    </td>

                                    {{-- Botón: ACTUALIZAR VENTA (con modal de categorías) --}}
                                    {{-- Botón: ACTUALIZAR VENTA (con modal de categorías) --}}
                                    {{-- Botón: ACTUALIZAR VENTA (con modal de categorías) --}}
                                    {{-- Botón: ACTUALIZAR VENTA (siempre habilitado) --}}
                                    <td class="text-center" style="width: 15%">
                                        @if (!$tipoCambioOficial)
                                            {{-- No hay oficial, botón deshabilitado con tooltip --}}
                                            <button type="button" class="btn btn-secondary btn-sm btn-block" disabled
                                                data-toggle="tooltip"
                                                title="No hay un tipo de cambio oficial definido. Establece uno primero.">
                                                <i class="fas fa-exclamation-triangle"></i> Sin oficial
                                            </button>
                                        @else
                                            {{-- SIEMPRE HABILITADO, independientemente del estado --}}
                                            <button type="button" class="btn btn-warning btn-sm btn-block"
                                                onclick="abrirModalActualizarPrecios({{ $cambio->precio_dolar }}, {{ $cambio->id }})"
                                                data-toggle="tooltip"
                                                title="Actualizar precios con este tipo de cambio ({{ $cambio->estado ? 'Activo actualmente' : 'Inactivo' }})">
                                                <i class="fas fa-calculator"></i>
                                                Actualizar {{ $cambio->estado ? '(activo)' : '' }}
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Botones de editar/eliminar --}}
                                    {{-- Botones de editar/eliminar --}}
                                    <td class="text-center" style="width: 15%">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal"
                                                data-target="#modalTipoCambio" data-id="{{ $cambio->id }}"
                                                data-precio="{{ $cambio->precio_dolar }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            {{-- Solo mostrar botón eliminar si NO es oficial --}}
                                            @if (!$cambio->is_oficial)
                                                @if (!$cambio->estado)
                                                    {{-- No es oficial y no está activo: se puede eliminar --}}
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="confirmDelete({{ $cambio->id }}, false)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    {{-- Está activo pero no es oficial (raro, pero posible): tooltip --}}
                                                    <button type="button" class="btn btn-secondary btn-sm" disabled
                                                        data-toggle="tooltip"
                                                        title="No se puede eliminar un tipo de cambio activo">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            @else
                                                {{-- Es oficial: botón deshabilitado con tooltip --}}
                                                <button type="button" class="btn btn-secondary btn-sm" disabled
                                                    data-toggle="tooltip"
                                                    title="No se puede eliminar el tipo de cambio oficial">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @foreach ($tipoCambio as $cambio)
                        <form action="{{ route('tipo_cambio.destroy', $cambio->id) }}" method="POST"
                            id="deleteForm{{ $cambio->id }}" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ACCIÓN GLOBAL: Recalcular por % de ganancia --}}
    {{-- <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><b>Recalcular precios de venta por % de ganancia</b></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <a href="{{ route('tipo_cambio.recalcular-venta') }}"
                                class="btn btn-secondary btn-block btn-lg"
                                onclick="event.preventDefault(); confirmarRecalcularGanancia()">
                                <i class="fas fa-percent"></i> Recalcular por % de ganancia
                            </a>
                            <p class="text-center text-muted mt-2">
                                <small>Solo aplica el porcentaje de ganancia al precio de compra actual</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- MODAL PARA CREAR/EDITAR TIPO DE CAMBIO --}}
    <div class="modal fade" id="modalTipoCambio" tabindex="-1" role="dialog" aria-labelledby="modalTipoCambioLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header {{ isset($tipoCambio) ? 'bg-warning' : 'bg-primary' }}">
                    <h5 class="modal-title" id="modalTipoCambioLabel">
                        <i class="fas {{ isset($tipoCambio) ? 'fa-edit' : 'fa-plus' }}"></i>
                        {{ isset($tipoCambio) ? 'Editar Tipo de Cambio' : 'Nuevo Tipo de Cambio' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formTipoCambio" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="method" value="POST">

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="precio">Precio del Dolar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">1 USD =</span>
                                </div>
                                <input type="number" class="form-control" id="precio" name="precio" step="0.01"
                                    min="0.01" placeholder="Ej: 6.96" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">Bs</span>
                                </div>
                            </div>
                            <small class="text-muted">Ingresa el valor del dólar en bolivianos</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Los nuevos tipos de cambio se crean como <strong>INACTIVOS y NO OFICIALES</strong>.
                            Usa los botones correspondientes para activarlos o hacerlos oficiales.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- DEPURACIÓN TEMPORAL --}}
    {{-- <div style="background: #f0f0f0; padding: 10px; margin: 10px 0;">
        <h4>Depuración:</h4>
        <p>Categorías: {{ $categorias->count() }}</p>
        @foreach ($categorias as $cat)
            <span class="badge badge-info">{{ $cat->nombre }}</span>
        @endforeach

        <p class="mt-2">Marcas: {{ $marcas->count() }}</p>
        @foreach ($marcas as $mar)
            <span class="badge badge-success">{{ $mar }}</span>
        @endforeach
    </div> --}}

    {{-- MODAL PARA ACTUALIZAR PRECIOS POR CATEGORÍA --}}
    {{-- MODAL PARA ACTUALIZAR PRECIOS POR CATEGORÍA Y MARCA --}}
    {{-- MODAL PARA ACTUALIZAR PRECIOS CON PESTAÑAS --}}
    <div class="modal fade" id="modalActualizarPrecios" tabindex="-1" role="dialog"
        aria-labelledby="modalActualizarPreciosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalActualizarPreciosLabel">
                        <i class="fas fa-calculator"></i>
                        Actualizar precios de venta
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formActualizarPrecios" method="POST" action="{{ route('tipo_cambio.actualizar-precios') }}">
                    @csrf
                    <input type="hidden" name="tipo_cambio_id" id="tipo_cambio_id_modal">

                    <div class="modal-body">
                        {{-- Tarjeta del tipo de cambio seleccionado --}}
                        <div class="alert alert-info" style="border-left: 4px solid #17a2b8;">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Tipo de cambio seleccionado:</strong><br>
                                    <span style="font-size: 1.3rem; font-weight: bold;">1 USD = <span
                                            id="precio_modal"></span> Bs</span>
                                </div>
                            </div>
                        </div>

                        {{-- Selector de alcance con diseño mejorado --}}
                        {{-- Selector de alcance con diseño mejorado --}}
                        <div class="form-group">
                            <label class="font-weight-bold">¿A qué productos aplicar?</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary" style="cursor: pointer;"
                                        onclick="document.getElementById('aplicar_todos').click()">
                                        <div class="card-body text-center p-3">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="aplicar_todos" name="aplicar_a" value="todos"
                                                    class="custom-control-input">
                                                <label class="custom-control-label font-weight-bold" for="aplicar_todos">
                                                    <i class="fas fa-globe-americas fa-2x d-block mb-2 text-primary"></i>
                                                    TODOS los productos
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-outline card-success" style="cursor: pointer;"
                                        onclick="document.getElementById('aplicar_seleccionados').click()">
                                        <div class="card-body text-center p-3">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="aplicar_seleccionados" name="aplicar_a"
                                                    value="seleccionados" class="custom-control-input" checked>
                                                {{-- 👈 CAMBIADO: checked por defecto --}}
                                                <label class="custom-control-label font-weight-bold"
                                                    for="aplicar_seleccionados">
                                                    <i class="fas fa-filter fa-2x d-block mb-2 text-success"></i>
                                                    Productos filtrados
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filtros con pestañas --}}
                        <div id="filtros_container" style="display: none; margin-top: 20px;">
                            <ul class="nav nav-tabs" id="filtrosTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="categorias-tab" data-toggle="tab"
                                        href="#categorias-tab-pane" role="tab">
                                        <i class="fas fa-tags text-warning"></i> Categorías
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="marcas-tab" data-toggle="tab" href="#marcas-tab-pane"
                                        role="tab">
                                        <i class="fas fa-copyright text-info"></i> Marcas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="resumen-tab" data-toggle="tab" href="#resumen-tab-pane"
                                        role="tab">
                                        <i class="fas fa-chart-pie text-success"></i> Resumen
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content p-3 border border-top-0 rounded-bottom"
                                style="background-color: #f8f9fa;">
                                {{-- Pestaña Categorías --}}
                                <div class="tab-pane fade show active" id="categorias-tab-pane" role="tabpanel">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold text-warning">
                                            <i class="fas fa-tags"></i> Seleccionar Categorías
                                        </label>
                                        <select name="categorias[]" id="categorias"
                                            class="form-control select2-categorias" multiple style="width: 100%">
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Puedes seleccionar múltiples categorías</small>

                                        {{-- Contador visual --}}
                                        <div class="mt-2" id="categorias-seleccionadas-info" style="display: none;">
                                            <span class="badge badge-warning" id="categorias-count">0</span> categorías
                                            seleccionadas
                                        </div>
                                    </div>
                                </div>

                                {{-- Pestaña Marcas --}}
                                <div class="tab-pane fade" id="marcas-tab-pane" role="tabpanel">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold text-info">
                                            <i class="fas fa-copyright"></i> Seleccionar Marcas
                                        </label>
                                        <select name="marcas[]" id="marcas" class="form-control select2-marcas"
                                            multiple style="width: 100%">
                                            @foreach ($marcas as $marca)
                                                <option value="{{ $marca }}">{{ $marca }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Puedes seleccionar múltiples marcas</small>

                                        {{-- Contador visual --}}
                                        <div class="mt-2" id="marcas-seleccionadas-info" style="display: none;">
                                            <span class="badge badge-info" id="marcas-count">0</span> marcas seleccionadas
                                        </div>
                                    </div>
                                </div>

                                {{-- Pestaña Resumen --}}
                                <div class="tab-pane fade" id="resumen-tab-pane" role="tabpanel">
                                    <div class="text-center p-3">
                                        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                                        <h5>Resumen de filtros aplicados</h5>
                                        <div id="resumen-filtros" class="text-left">
                                            <p class="text-muted">Selecciona categorías o marcas para ver el resumen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Información adicional --}}
                            <div class="alert alert-info mt-3" style="border-left: 4px solid #17a2b8;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Combinación de filtros:</strong> Si seleccionas categorías Y marcas, se aplicará a
                                productos que cumplan <strong>AMBAS condiciones</strong>.
                            </div>
                        </div>

                        {{-- Fórmula y advertencia --}}
                        <div class="alert alert-warning mt-3" style="border-left: 4px solid #ffc107;">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Fórmula aplicada:</strong><br>
                                    <code>precio_venta = (precio_compra × (nuevo_tipo ÷ tipo_oficial)) × (1 + %ganancia ÷
                                        100)</code>
                                    <br><br>
                                    <strong>Esta acción:</strong>
                                    <ul class="mb-0">
                                        <li>✅ Recalculará los precios de venta</li>
                                        <li>✅ ACTIVARÁ este tipo de cambio automáticamente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnActualizarPrecios">
                            <i class="fas fa-calculator"></i> Actualizar precios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            // ===== CONFIGURACIÓN INICIAL =====

            // Configurar Select2 para categorías y marcas
            $('.select2-categorias').select2({
                placeholder: 'Selecciona categorías...',
                allowClear: true,
                theme: 'bootstrap4',
                width: '100%',
                language: {
                    noResults: () => "No se encontraron categorías"
                }
            });

            $('.select2-marcas').select2({
                placeholder: 'Selecciona marcas...',
                allowClear: true,
                theme: 'bootstrap4',
                width: '100%',
                language: {
                    noResults: () => "No se encontraron marcas"
                }
            });

            // ===== MODAL CREAR/EDITAR TIPO CAMBIO =====
            $('#modalTipoCambio').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var precio = button.data('precio');

                var modal = $(this);
                var form = modal.find('#formTipoCambio');
                var method = modal.find('#method');

                if (id) {
                    modal.find('.modal-header').removeClass('bg-primary').addClass('bg-warning');
                    modal.find('.modal-title').html('<i class="fas fa-edit"></i> Editar Tipo de Cambio');
                    form.attr('action', '{{ url('admin/tipo_cambio') }}/' + id);
                    method.val('PUT');
                    modal.find('#precio').val(precio);
                    modal.find('#btnGuardar').removeClass('btn-primary').addClass('btn-warning');
                } else {
                    modal.find('.modal-header').removeClass('bg-warning').addClass('bg-primary');
                    modal.find('.modal-title').html('<i class="fas fa-plus"></i> Nuevo Tipo de Cambio');
                    form.attr('action', '{{ route('tipo_cambio.store') }}');
                    method.val('POST');
                    modal.find('#precio').val('');
                    modal.find('#btnGuardar').removeClass('btn-warning').addClass('btn-primary');
                }
            });

            // ===== MODAL ACTUALIZAR PRECIOS =====

            // Mostrar/ocultar filtros al cambiar radio button
            // Mostrar/ocultar filtros al cambiar radio button
            $('input[name="aplicar_a"]').on('change', function() {
                if ($(this).val() === 'seleccionados') {
                    $('#filtros_container').slideDown(300);
                } else {
                    $('#filtros_container').slideUp(300);
                    // Limpiar selecciones cuando se elige "Todos"
                    $('#categorias').val(null).trigger('change');
                    $('#marcas').val(null).trigger('change');
                    $('#categorias-seleccionadas-info').hide();
                    $('#marcas-seleccionadas-info').hide();
                    $('#resumen-filtros').html(
                        '<p class="text-muted text-center">Selecciona categorías o marcas para ver el resumen</p>'
                        );
                }
            });

            // Actualizar contador de categorías
            $('#categorias').on('change', function() {
                let count = $(this).val() ? $(this).val().length : 0;
                $('#categorias-count').text(count);
                if (count > 0) {
                    $('#categorias-seleccionadas-info').show();
                } else {
                    $('#categorias-seleccionadas-info').hide();
                }
                actualizarResumen();
            });

            // Actualizar contador de marcas
            $('#marcas').on('change', function() {
                let count = $(this).val() ? $(this).val().length : 0;
                $('#marcas-count').text(count);
                if (count > 0) {
                    $('#marcas-seleccionadas-info').show();
                } else {
                    $('#marcas-seleccionadas-info').hide();
                }
                actualizarResumen();
            });
        });

        // ===== FUNCIONES GLOBALES =====

        // Función para abrir modal de actualización
        // Función para abrir modal de actualización
        function abrirModalActualizarPrecios(precio, id) {
            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();

            $('#tipo_cambio_id_modal').val(id);
            $('#precio_modal').text(precio.toFixed(2));

            // 👇 Resetear todo
            $('#aplicar_seleccionados').prop('checked', true); // Seleccionar "Productos filtrados"
            $('#aplicar_todos').prop('checked', false);

            // 👇 Mostrar el contenedor de filtros (porque "Productos filtrados" está seleccionado)
            $('#filtros_container').show();

            // 👇 Limpiar completamente los selects de categorías y marcas
            $('#categorias').val(null).trigger('change');
            $('#marcas').val(null).trigger('change');

            // 👇 Ocultar los contadores
            $('#categorias-seleccionadas-info').hide();
            $('#marcas-seleccionadas-info').hide();

            // 👇 Limpiar el resumen
            $('#resumen-filtros').html(
                '<p class="text-muted text-center">Selecciona categorías o marcas para ver el resumen</p>');

            // Resetear pestañas a la primera
            $('#filtrosTab a:first').tab('show');

            $('#modalActualizarPrecios').modal('show');
        }

        // Función para actualizar el resumen
        function actualizarResumen() {
            let categorias = $('#categorias').val() || [];
            let marcas = $('#marcas').val() || [];
            let precio = $('#precio_modal').text();

            let html = '';

            if (categorias.length === 0 && marcas.length === 0) {
                html = '<p class="text-muted text-center">No has seleccionado ningún filtro</p>';
            } else {
                html = '<ul class="list-group">';

                if (categorias.length > 0) {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-tags text-warning"></i> Categorías</span>
                            <span class="badge badge-warning badge-pill">${categorias.length}</span>
                        </li>`;
                }

                if (marcas.length > 0) {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-copyright text-info"></i> Marcas</span>
                            <span class="badge badge-info badge-pill">${marcas.length}</span>
                        </li>`;
                }

                html += `
                    <li class="list-group-item list-group-item-success d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-dollar-sign"></i> Tipo de cambio</span>
                        <span><strong>1 USD = ${precio} Bs</strong></span>
                    </li>`;

                html += '</ul>';
            }

            $('#resumen-filtros').html(html);
        }

        // Confirmar establecer como OFICIAL
        function confirmarSetOficial(precio, id) {
            Swal.fire({
                title: '¿Establecer como OFICIAL?',
                html: `
                    <div style="text-align: left;">
                        <p>⏺️ <strong>Tipo de cambio:</strong> 1 USD = ${precio.toFixed(2)} Bs</p>
                        <p>⏺️ <strong>Efecto:</strong> Este será el tipo de cambio base para todos los cálculos.</p>
                        <div style="background-color: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <p style="margin: 0; color: #004085;">
                                <strong>⭐ Solo puede haber un tipo de cambio oficial</strong>
                            </p>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '⭐ Sí, hacer oficial',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('tipo_cambio.set-oficial') }}';

                    var csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';

                    var inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'tipo_cambio_id';
                    inputId.value = id;

                    form.appendChild(csrf);
                    form.appendChild(inputId);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Confirmar eliminar
        // Confirmar eliminar
        function confirmDelete(id, esOficial) {
            // Esta función ya no necesita el parámetro esOficial porque
            // el botón no se muestra para oficiales, pero lo mantenemos por si acaso

            Swal.fire({
                title: '¿Eliminar tipo de cambio?',
                text: "Esta acción no se puede deshacer",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("deleteForm" + id).submit();
                }
            });
        }

        // Confirmar recalcular por % de ganancia
        function confirmarRecalcularGanancia() {
            Swal.fire({
                title: '¿Recalcular por % de ganancia?',
                html: `
                    <div style="text-align: left;">
                        <p>⏺️ <strong>Acción:</strong> Aplicar % de ganancia al precio de compra actual</p>
                        <p>⏺️ <strong>Fórmula:</strong> precio_venta = precio_compra * (1 + %ganancia/100)</p>
                        <p>⏺️ <strong>Nota:</strong> Mantiene el tipo de cambio actual</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '✅ Sí, recalcular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('tipo_cambio.recalcular-venta') }}';
                }
            });
        }

        // Confirmar actualización desde el modal
        $('#formActualizarPrecios').on('submit', function(e) {
            e.preventDefault();

            let tipoCambioId = $('#tipo_cambio_id_modal').val();
            let precio = $('#precio_modal').text();
            let aplicarA = $('input[name="aplicar_a"]:checked').val();
            let categorias = $('#categorias').val();
            let marcas = $('#marcas').val();

            if (aplicarA === 'seleccionados') {
                if ((!categorias || categorias.length === 0) && (!marcas || marcas.length === 0)) {
                    Swal.fire('Error', 'Debes seleccionar al menos una categoría o una marca', 'error');
                    return;
                }
            }

            let filtrosTexto = [];
            if (categorias && categorias.length > 0) filtrosTexto.push(`${categorias.length} categoría(s)`);
            if (marcas && marcas.length > 0) filtrosTexto.push(`${marcas.length} marca(s)`);

            let mensajeFiltros = aplicarA === 'todos' ?
                '<p>📂 <strong>Aplicar a:</strong> TODOS los productos</p>' :
                `<p>📂 <strong>Filtros aplicados:</strong> ${filtrosTexto.join(' y ') || 'Ninguno'}</p>`;

            Swal.fire({
                title: '¿Actualizar precios de venta?',
                html: `
                    <div style="text-align: left;">
                        <p>⏺️ <strong>Tipo de cambio:</strong> 1 USD = ${precio} Bs</p>
                        ${mensajeFiltros}
                        <p>⏺️ <strong>Fórmula aplicada:</strong></p>
                        <div style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;">
                            precio_venta = (precio_compra × (nuevo_tipo ÷ tipo_oficial)) × (1 + %ganancia ÷ 100)
                        </div>
                        <p class="mt-2">⏺️ <strong>Efecto:</strong></p>
                        <ul>
                            <li>✅ Se recalcularán los precios de venta seleccionados</li>
                            <li>✅ Este tipo de cambio se ACTIVARÁ automáticamente</li>
                        </ul>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '💰 Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviar el formulario
                    $('#formActualizarPrecios')[0].submit();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {

            // Verificar que los elementos existen

            // Inicializar Select2 de forma simple
            $('#categorias').select2({
                placeholder: 'Selecciona categorías',
                width: '100%'
            });

            $('#marcas').select2({
                placeholder: 'Selecciona marcas',
                width: '100%'
            });

        });
    </script>
@stop
