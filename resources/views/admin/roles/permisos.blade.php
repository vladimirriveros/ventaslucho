@php
    use Spatie\Permission\Models\Permission;
@endphp
@extends('layouts.admin')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item active" aria-current="page">Permisos para: <strong>{{ $rol->name }}</strong></li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Información del Rol -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        <b>Gestión de Permisos - Rol: {{ $rol->name }}</b>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">

                    @if($rol->name === 'admin')
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Rol Protegido</h5>
                            El rol <strong>"Administrador"</strong> tiene todos los permisos del sistema por defecto y no pueden ser modificados.
                        </div>
                    @else
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-info-circle"></i> Información</h5>
                            Selecciona los permisos que deseas asignar al rol <strong>{{ $rol->name }}</strong>.
                            Puedes usar los botones de selección rápida para agilizar el proceso.
                        </div>

                        <!-- Filtro de búsqueda MEJORADO -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="filtro-permisos"
                                           placeholder="Buscar permiso por nombre...">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="limpiar-filtro">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Escribe para filtrar permisos en tiempo real
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-success" id="seleccionar-todos">
                                        <i class="fas fa-check-double"></i> Seleccionar Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" id="deseleccionar-todos">
                                        <i class="fas fa-times"></i> Deseleccionar Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="expandir-todos">
                                        <i class="fas fa-expand-arrows-alt"></i> Expandir Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" id="colapsar-todos">
                                        <i class="fas fa-compress-arrows-alt"></i> Colapsar Todos
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ url('/admin/roles/' . $rol->id) }}" method="POST" id="form-permisos">
                        @csrf

                        <!-- Tarjetas de permisos por módulo -->
                        <div class="row" id="modulos-container">
                            @php
                                $iconosModulos = [
                                    'Categorías' => 'fa-tags',
                                    'Sucursales' => 'fa-store',
                                    'Productos' => 'fa-boxes',
                                    'Proveedores' => 'fa-truck',
                                    'Compras' => 'fa-shopping-cart',
                                    'Inventario' => 'fa-warehouse',
                                    'Tipo de Cambio' => 'fa-exchange-alt',
                                    'Roles' => 'fa-user-tag',
                                    'Usuarios' => 'fa-users',
                                    'Salidas' => 'fa-sign-out-alt',
                                    'Lotes' => 'fa-layer-group',
                                    'Ventas' => 'fa-dollar-sign',
                                    'Cotizaciones' => 'fa-file-invoice',
                                    'Clientes' => 'fa-users',
                                    'Caja' => 'fa-cash-register',
                                    'Reportes' => 'fa-chart-bar',
                                    'Bancas' => 'fa-university',
                                    // 'Otros Permisos' => 'fa-question-circle',
                                ];
                            @endphp

                            @foreach ($permisos as $modulo => $grupoPermisos)
                                <div class="col-md-4 col-sm-6 mb-4 modulo-col">
                                    <div class="card card-outline card-{{ $rol->name === 'admin' ? 'secondary' : 'primary' }} h-100 modulo-card"
                                        id="modulo-{{ Str::slug($modulo) }}">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas {{ $iconosModulos[$modulo] ?? 'fa-folder' }} text-primary mr-2"></i>
                                                    <b>{{ $modulo }}</b>
                                                </h5>
                                                <div>
                                                    <span class="badge badge-primary" id="count-{{ Str::slug($modulo) }}">
                                                        {{ $grupoPermisos->count() }} permisos
                                                    </span>
                                                    <button type="button"
                                                        class="btn btn-xs btn-outline-secondary ml-2 toggle-modulo"
                                                        data-target="#modulo-{{ Str::slug($modulo) }}-content">
                                                        <i class="fas fa-chevron-up"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body p-0" id="modulo-{{ Str::slug($modulo) }}-content">
                                            @if($rol->name !== 'admin')
                                                <!-- Botones de selección rápida por módulo -->
                                                <div class="btn-group btn-group-sm w-100" role="group" style="border-radius: 0;">
                                                    <button type="button" class="btn btn-outline-success seleccionar-modulo"
                                                        data-modulo="{{ Str::slug($modulo) }}" style="border-radius: 0;">
                                                        <i class="fas fa-check"></i> Seleccionar Todo
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger deseleccionar-modulo"
                                                        data-modulo="{{ Str::slug($modulo) }}" style="border-radius: 0;">
                                                        <i class="fas fa-times"></i> Deseleccionar Todo
                                                    </button>
                                                </div>
                                            @endif

                                            <!-- Lista de permisos -->
                                            <div class="permisos-lista" style="max-height: 350px; overflow-y: auto; padding: 10px;">
                                                @foreach ($grupoPermisos as $permiso)
                                                    @php
                                                        // Traducciones completas de permisos
                                                        $traduccionesPermisos = [
                                                            // Categorías
                                                            'categorias.index' => 'Ver categorías',
                                                            'categorias.create' => 'Crear categoría',
                                                            'categorias.store' => 'Guardar categoría',
                                                            'categorias.show' => 'Ver detalle de categoría',
                                                            'categorias.edit' => 'Editar categoría',
                                                            'categorias.update' => 'Actualizar categoría',
                                                            'categorias.destroy' => 'Eliminar categoría',

                                                            // Productos
                                                            'productos.index' => 'Ver productos',
                                                            'productos.create' => 'Crear producto',
                                                            'productos.store' => 'Guardar producto',
                                                            'productos.show' => 'Ver detalle de producto',
                                                            'productos.edit' => 'Editar producto',
                                                            'productos.update' => 'Actualizar producto',
                                                            'productos.destroy' => 'Eliminar producto',
                                                            'productos.verificar-codigo' => 'Verificar código',
                                                            'productos.ultimo-codigo' => 'Ver último código',
                                                            'productos.desactivar' => 'Desactivar producto',

                                                            // Sucursales
                                                            'sucursales.index' => 'Ver sucursales',
                                                            'sucursales.create' => 'Crear sucursal',
                                                            'sucursales.store' => 'Guardar sucursal',
                                                            'sucursales.show' => 'Ver detalle de sucursal',
                                                            'sucursales.edit' => 'Editar sucursal',
                                                            'sucursales.update' => 'Actualizar sucursal',
                                                            'sucursales.destroy' => 'Eliminar sucursal',

                                                            // Lotes
                                                            'lotes.index' => 'Ver lotes',
                                                            'lotes.create' => 'Crear lote',
                                                            'lotes.store' => 'Guardar lote',
                                                            'lotes.show' => 'Ver detalle de lote',
                                                            'lotes.edit' => 'Editar lote',
                                                            'lotes.update' => 'Actualizar lote',
                                                            'lotes.destroy' => 'Eliminar lote',
                                                            'lotes.vencidos' => 'Ver lotes vencidos',
                                                            'lotes.vencidos.sucursal' => 'Lotes vencidos por sucursal',
                                                            'lotes.vencidos.agregar' => 'Agregar lote a salida',
                                                            'lotes.vencidos.eliminar' => 'Quitar lote de salida',
                                                            'lotes.vencidos.finalizar' => 'Finalizar salida vencidos',
                                                            'lotes.pdf' => 'Exportar PDF de lotes',

                                                            // Proveedores
                                                            'proveedores.index' => 'Ver proveedores',
                                                            'proveedores.store' => 'Guardar proveedor',
                                                            'proveedores.update' => 'Actualizar proveedor',
                                                            'proveedores.destroy' => 'Eliminar proveedor',

                                                            // Compras
                                                            'compras.index' => 'Ver compras',
                                                            'compras.create' => 'Crear compra',
                                                            'compras.store' => 'Guardar compra',
                                                            'compras.show' => 'Ver detalle de compra',
                                                            'compras.edit' => 'Editar compra',
                                                            'compras.destroy' => 'Eliminar compra',
                                                            'compras.enviarCorreo' => 'Enviar correo',
                                                            'compras.finalizarCompra' => 'Finalizar compra',
                                                            'compras.procesarCarrito' => 'Procesar carrito',
                                                            'compras.correccion' => 'Corregir compras',

                                                            // Inventario
                                                            'inventario.index' => 'Ver inventario',
                                                            'sucursal_por_lotes.index' => 'Inventario por sucursal',
                                                            'mostrar_inventario_por_sucursal.show' => 'Ver inventario por sucursal',
                                                            'inventario.stock_bajo_sucursal' => 'Stock bajo por sucursal',
                                                            'inventario.sucursal.pdf' => 'Exportar inventario PDF',
                                                            'movimientos.index' => 'Ver movimientos',

                                                            // Tipo de Cambio
                                                            'tipo_cambio.index' => 'Ver tipo de cambio',
                                                            'tipo_cambio.store' => 'Guardar tipo de cambio',
                                                            'tipo_cambio.update' => 'Actualizar tipo de cambio',
                                                            'tipo_cambio.destroy' => 'Eliminar tipo de cambio',
                                                            'tipo_cambio.recalcular-venta' => 'Recalcular precios de venta',

                                                            // Roles
                                                            'roles.index' => 'Ver roles',
                                                            'roles.create' => 'Crear rol',
                                                            'roles.store' => 'Guardar rol',
                                                            'roles.edit' => 'Editar rol',
                                                            'roles.permisos' => 'Ver permisos del rol',
                                                            'roles.update_permisos' => 'Actualizar permisos del rol',
                                                            'roles.update' => 'Actualizar rol',
                                                            'roles.destroy' => 'Eliminar rol',

                                                            // Usuarios
                                                            'user.index' => 'Ver usuarios',
                                                            'user.assign-roles' => 'Asignar perfiles operativos',
                                                            'user.create' => 'Crear usuario',
                                                            'user.store' => 'Guardar usuario',
                                                            'user.edit' => 'Editar usuario',
                                                            'user.update' => 'Actualizar usuario',
                                                            'user.destroy' => 'Eliminar usuario',

                                                            // Salidas
                                                            'salidas.index' => 'Ver salidas',
                                                            'salidas.create' => 'Crear salida',
                                                            'salidas.store' => 'Guardar salida',
                                                            'salidas.show' => 'Ver detalle de salida',
                                                            'salidas.edit' => 'Editar salida',
                                                            'salidas.destroy' => 'Eliminar salida',
                                                            'salidas.finalizarSalida' => 'Finalizar salida',

                                                            // Ventas
                                                            'ventas.index' => 'Ver ventas',
                                                            'ventas.create' => 'Crear venta',
                                                            'ventas.store' => 'Guardar venta',
                                                            'ventas.show' => 'Ver detalle de venta',
                                                            'ventas.edit' => 'Editar venta',
                                                            'ventas.update' => 'Actualizar venta',
                                                            'ventas.destroy' => 'Eliminar venta',
                                                            'ventas.anular' => 'Anular venta',

                                                            // Cotizaciones
                                                            'cotizaciones.index' => 'Ver cotizaciones',
                                                            'cotizaciones.create' => 'Crear cotización',
                                                            'cotizaciones.store' => 'Guardar cotización',
                                                            'cotizaciones.show' => 'Ver detalle de cotización',
                                                            'cotizaciones.edit' => 'Editar cotización',
                                                            'cotizaciones.update' => 'Actualizar cotización',
                                                            'cotizaciones.destroy' => 'Eliminar cotización',
                                                            'cotizaciones.convertir' => 'Convertir a venta',
                                                            'cotizaciones.imprimir' => 'Imprimir cotización',

                                                            // Clientes
                                                            'clientes.index' => 'Ver clientes',
                                                            'clientes.create' => 'Crear cliente',
                                                            'clientes.store' => 'Guardar cliente',
                                                            'clientes.show' => 'Ver detalle de cliente',
                                                            'clientes.edit' => 'Editar cliente',
                                                            'clientes.update' => 'Actualizar cliente',
                                                            'clientes.destroy' => 'Eliminar cliente',
                                                            'clientes.toggle-activo' => 'Activar/Desactivar cliente',
                                                            'clientes.buscar' => 'Buscar cliente',

                                                            // Caja
                                                            'caja.index' => 'Ver caja',
                                                            'caja.apertura' => 'Abrir caja',
                                                            'caja.cierre' => 'Cerrar caja',
                                                            'caja.movimientos' => 'Ver movimientos de caja',
                                                            'caja.reportes' => 'Reportes de caja',

                                                            // Reportes
                                                            'reportes.ventas' => 'Reporte de ventas',
                                                            'reportes.ventas.diario' => 'Reporte diario',
                                                            'reportes.ventas.mensual' => 'Reporte mensual',
                                                            'reportes.vendedores' => 'Reporte por vendedor',

                                                            // Bancas
                                                            'bancas.index' => 'Ver bancas',
                                                            'bancas.create' => 'Crear banca',
                                                            'bancas.store' => 'Guardar banca',
                                                            'bancas.show' => 'Ver detalle de banca',
                                                            'bancas.edit' => 'Editar banca',
                                                            'bancas.update' => 'Actualizar banca',
                                                            'bancas.destroy' => 'Eliminar banca',
                                                            'bancas.movimientos' => 'Ver movimientos bancarios',
                                                            'bancas.cargar' => 'Cargar saldo a banca',

                                                        ];

                                                        if ($modulo == 'Otros Permisos') {
                                                            $nombreFormateado = str_replace('_', ' ', $permiso->name);
                                                            $nombreFormateado = ucwords(str_replace('.', ' - ', $nombreFormateado));
                                                            $iconoAccion = 'fa-cog';
                                                            $nombreAmigable = $nombreFormateado;
                                                        } else {
                                                            $partes = explode('.', $permiso->name);
                                                            $accion = $partes[1] ?? '';

                                                            $iconosAccion = [
                                                                'index' => 'fa-list',
                                                                'create' => 'fa-plus-circle',
                                                                'store' => 'fa-save',
                                                                'show' => 'fa-eye',
                                                                'edit' => 'fa-edit',
                                                                'update' => 'fa-sync',
                                                                'destroy' => 'fa-trash',
                                                                'desactivar' => 'fa-toggle-off',
                                                                'verificar-codigo' => 'fa-check-circle',
                                                                'ultimo-codigo' => 'fa-hashtag',
                                                                'enviarCorreo' => 'fa-envelope',
                                                                'finalizarCompra' => 'fa-check-double',
                                                                'procesarCarrito' => 'fa-shopping-cart',
                                                                'correccion' => 'fa-edit',
                                                                'migrar' => 'fa-exchange-alt',
                                                                'vencidos' => 'fa-calendar-times',
                                                                'agregar' => 'fa-plus',
                                                                'eliminar' => 'fa-trash',
                                                                'finalizar' => 'fa-check',
                                                                'pdf' => 'fa-file-pdf',
                                                                'apertura' => 'fa-unlock',
                                                                'cierre' => 'fa-lock',
                                                                'movimientos' => 'fa-history',
                                                                'reportes' => 'fa-chart-line',
                                                                'convertir' => 'fa-exchange-alt',
                                                                'imprimir' => 'fa-print',
                                                                'toggle-activo' => 'fa-toggle-on',
                                                                'buscar' => 'fa-search',
                                                                'recalcular-venta' => 'fa-calculator',
                                                            ];

                                                            $iconoAccion = $iconosAccion[$accion] ?? 'fa-circle';
                                                            $nombreAmigable = $traduccionesPermisos[$permiso->name] ?? $permiso->name;
                                                        }
                                                    @endphp

                                                    <div class="permiso-item" data-modulo="{{ Str::slug($modulo) }}"
                                                        data-nombre="{{ strtolower($nombreAmigable) }}"
                                                        id="item-{{ $permiso->id }}">
                                                        <label class="custom-checkbox-right @if($rol->name === 'admin') disabled-label @endif"
                                                            for="permiso-{{ $permiso->id }}">
                                                            <span class="checkbox-label">
                                                                <i class="fas {{ $iconoAccion }} text-secondary mr-2"></i>
                                                                {{ $nombreAmigable }}
                                                            </span>
                                                            <input type="checkbox" class="permiso-checkbox"
                                                                name="permisos[]" value="{{ $permiso->id }}"
                                                                id="permiso-{{ $permiso->id }}"
                                                                {{ $rol->name === 'admin' ? 'checked disabled' : ($rol->hasPermissionTo($permiso->name) ? 'checked' : '') }}>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @if($rol->name !== 'admin')
                                        <div class="card-footer text-muted small">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span id="selected-{{ Str::slug($modulo) }}">0</span> de
                                            {{ $grupoPermisos->count() }} seleccionados
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($rol->name !== 'admin')
                            <hr>

                            <!-- Resumen de selección -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="alert alert-secondary">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <div>
                                                <i class="fas fa-clipboard-list mr-2"></i>
                                                <strong>Resumen de selección:</strong>
                                                <span id="total-seleccionados">0</span> permisos seleccionados de <span
                                                    id="total-permisos">{{ Permission::count() }}</span> totales
                                            </div>
                                            <div class="mt-2 mt-sm-0">
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal"
                                                    data-target="#resumenModal">
                                                    <i class="fas fa-eye"></i> Ver resumen detallado
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" id="copiar-resumen">
                                                    <i class="fas fa-copy"></i> Copiar resumen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                                @if($rol->name !== 'admin')
                                    <button type="submit" class="btn btn-primary" id="btn-guardar">
                                        <i class="fas fa-save"></i> Guardar Permisos
                                    </button>
                                @else
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="fas fa-lock"></i> Rol Protegido - No modificable
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($rol->name !== 'admin')
    <!-- Modal de Resumen Mejorado -->
    <div class="modal fade" id="resumenModal" tabindex="-1" role="dialog" aria-labelledby="resumenModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="resumenModalLabel">
                        <i class="fas fa-clipboard-list"></i> Resumen de Permisos Seleccionados
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div id="resumen-contenido"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" id="copiar-resumen-modal">
                        <i class="fas fa-copy"></i> Copiar al portapapeles
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
    <style>
        .permiso-item {
            transition: all 0.2s;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 6px;
            border: 1px solid transparent;
        }

        .permiso-item:hover {
            background-color: #f0f7ff;
            border-color: #cce5ff;
            transform: translateX(2px);
        }

        .permiso-item.seleccionado {
            background-color: #e8f5e9;
            border-color: #a5d6a7;
        }

        .permiso-item.oculto {
            display: none;
        }

        .custom-checkbox-right {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            width: 100%;
        }

        .custom-checkbox-right input[type="checkbox"] {
            position: relative;
            width: 18px;
            height: 18px;
            cursor: pointer;
            order: 2;
            margin-left: 10px;
            accent-color: #28a745;
            transition: transform 0.2s;
        }

        .custom-checkbox-right input[type="checkbox"]:hover {
            transform: scale(1.1);
        }

        .custom-checkbox-right input[type="checkbox"]:disabled {
            accent-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.65;
        }

        .custom-checkbox-right.disabled-label {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .custom-checkbox-right .checkbox-label {
            display: flex;
            align-items: center;
            flex: 1;
            order: 1;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .custom-checkbox-right.disabled-label .checkbox-label {
            cursor: not-allowed;
        }

        .badge-primary {
            background-color: #007bff;
        }

        .badge-success {
            background-color: #28a745;
        }

        .permisos-lista::-webkit-scrollbar {
            width: 6px;
        }

        .permisos-lista::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .permisos-lista::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .permisos-lista::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .permiso-item.seleccionado .checkbox-label {
            color: #2e7d32;
            font-weight: 500;
        }

        .permiso-item.seleccionado i {
            color: #2e7d32 !important;
        }

        .card.card-outline.card-secondary .card-header {
            background-color: #f8f9fa;
            border-bottom-color: #6c757d;
        }

        /* Animación para el filtro */
        .modulo-card.modulo-oculto {
            display: none;
        }

        /* Tooltips personalizados */
        .btn-group .btn {
            transition: all 0.2s;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .permiso-item {
                font-size: 13px;
                padding: 6px 8px;
            }

            .custom-checkbox-right .checkbox-label {
                font-size: 12px;
            }

            .btn-group {
                flex-wrap: wrap;
                gap: 5px;
            }

            .btn-group .btn {
                margin-bottom: 5px;
                font-size: 12px;
                padding: 5px 8px;
            }

            .card-header h5 {
                font-size: 14px;
            }

            .badge {
                font-size: 10px;
            }
        }

        /* Módulo con todo seleccionado */
        .modulo-card.todo-seleccionado .card-header {
            background-color: #d4edda !important;
            border-bottom-color: #28a745 !important;
        }

        /* Animación de carga */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        .cargando {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Solo ejecutar scripts interactivos si NO es admin
            @if($rol->name !== 'admin')
                // Función para actualizar estado visual del módulo
                function actualizarEstadoModulo(modulo) {
                    var total = $('.permiso-item[data-modulo="' + modulo + '"]').length;
                    var seleccionados = $('.permiso-item[data-modulo="' + modulo + '"] .permiso-checkbox:checked').length;
                    var card = $('#modulo-' + modulo);

                    if (seleccionados === total && total > 0) {
                        card.addClass('todo-seleccionado');
                        card.find('.badge-primary').removeClass('badge-primary').addClass('badge-success');
                    } else {
                        card.removeClass('todo-seleccionado');
                        card.find('.badge-success').removeClass('badge-success').addClass('badge-primary');
                    }
                }

                // Marcar items inicialmente seleccionados
                $('.permiso-checkbox:checked').each(function() {
                    $(this).closest('.permiso-item').addClass('seleccionado');
                });

                // Actualizar contadores iniciales
                function actualizarContadorModulo(modulo) {
                    var total = $('.permiso-item[data-modulo="' + modulo + '"]').length;
                    var seleccionados = $('.permiso-item[data-modulo="' + modulo + '"] .permiso-checkbox:checked').length;
                    $('#selected-' + modulo).text(seleccionados);
                    actualizarEstadoModulo(modulo);
                }

                function actualizarTodosLosContadores() {
                    $('.modulo-card').each(function() {
                        var id = $(this).attr('id');
                        if (id) {
                            var modulo = id.replace('modulo-', '');
                            actualizarContadorModulo(modulo);
                        }
                    });
                }

                function actualizarTotalSeleccionados() {
                    var total = $('.permiso-checkbox:checked').length;
                    var totalPermisos = {{ Permission::count() }};
                    $('#total-seleccionados').text(total);

                    // Actualizar barra de progreso (opcional)
                    var porcentaje = total > 0 ? Math.round((total / totalPermisos) * 100) : 0;
                    // Puedes agregar una barra de progreso si lo deseas
                }

                // Evento cuando se marca/desmarca un checkbox
                $(document).on('change', '.permiso-checkbox', function() {
                    var item = $(this).closest('.permiso-item');
                    if ($(this).is(':checked')) {
                        item.addClass('seleccionado');
                    } else {
                        item.removeClass('seleccionado');
                    }

                    var modulo = item.data('modulo');
                    actualizarContadorModulo(modulo);
                    actualizarTotalSeleccionados();
                });

                // Filtro de búsqueda MEJORADO
                $('#filtro-permisos').on('keyup', function() {
                    var valor = $(this).val().toLowerCase().trim();

                    if (valor === '') {
                        // Mostrar todos los items
                        $('.permiso-item').removeClass('oculto').show();
                        $('.modulo-col').show();
                    } else {
                        // Filtrar permisos
                        $('.permiso-item').each(function() {
                            var texto = $(this).data('nombre') || $(this).find('.checkbox-label').text().toLowerCase();
                            var coincide = texto.indexOf(valor) > -1;

                            if (coincide) {
                                $(this).removeClass('oculto').show();
                            } else {
                                $(this).addClass('oculto').hide();
                            }
                        });

                        // Ocultar módulos sin permisos visibles
                        $('.modulo-col').each(function() {
                            var moduloId = $(this).find('.modulo-card').attr('id');
                            if (moduloId) {
                                var modulo = moduloId.replace('modulo-', '');
                                var visible = $('.permiso-item[data-modulo="' + modulo + '"]:visible').length > 0;
                                if (!visible) {
                                    $(this).hide();
                                } else {
                                    $(this).show();
                                }
                            }
                        });
                    }

                    // Animación de los resultados
                    $('.permiso-item:visible').addClass('cargando');
                    setTimeout(function() {
                        $('.permiso-item').removeClass('cargando');
                    }, 500);
                });

                $('#limpiar-filtro').click(function() {
                    $('#filtro-permisos').val('').trigger('keyup');
                });

                // Seleccionar todos los permisos de un módulo
                $('.seleccionar-modulo').click(function() {
                    var modulo = $(this).data('modulo');
                    var items = $('.permiso-item[data-modulo="' + modulo + '"]');
                    items.each(function() {
                        var checkbox = $(this).find('.permiso-checkbox');
                        if (!checkbox.prop('checked')) {
                            checkbox.prop('checked', true).trigger('change');
                        }
                    });
                });

                // Deseleccionar todos los permisos de un módulo
                $('.deseleccionar-modulo').click(function() {
                    var modulo = $(this).data('modulo');
                    var items = $('.permiso-item[data-modulo="' + modulo + '"]');
                    items.each(function() {
                        var checkbox = $(this).find('.permiso-checkbox');
                        if (checkbox.prop('checked')) {
                            checkbox.prop('checked', false).trigger('change');
                        }
                    });
                });

                // Seleccionar todos los permisos
                $('#seleccionar-todos').click(function() {
                    $('.permiso-checkbox').prop('checked', true).trigger('change');
                    Swal.fire({
                        icon: 'success',
                        title: 'Todos seleccionados',
                        text: 'Se seleccionaron todos los permisos disponibles',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });

                // Deseleccionar todos los permisos
                $('#deseleccionar-todos').click(function() {
                    $('.permiso-checkbox').prop('checked', false).trigger('change');
                    Swal.fire({
                        icon: 'info',
                        title: 'Todos deseleccionados',
                        text: 'Se deseleccionaron todos los permisos',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });

                // Expandir/colapsar módulo
                $('.toggle-modulo').click(function() {
                    var target = $(this).data('target');
                    $(target).slideToggle('fast');
                    $(this).find('i').toggleClass('fa-chevron-up fa-chevron-down');
                });

                // Expandir todos los módulos
                $('#expandir-todos').click(function() {
                    $('[id$="-content"]').slideDown('fast');
                    $('.toggle-modulo i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                });

                // Colapsar todos los módulos
                $('#colapsar-todos').click(function() {
                    $('[id$="-content"]').slideUp('fast');
                    $('.toggle-modulo i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                });

                // Generar resumen en modal
                function generarResumenHtml() {
                    var html = '<div class="row">';
                    var totalGeneral = 0;

                    $('.modulo-card').each(function() {
                        var moduloNombre = $(this).find('.card-title b').text();
                        var moduloId = $(this).attr('id').replace('modulo-', '');
                        var seleccionados = $('.permiso-item[data-modulo="' + moduloId + '"] .permiso-checkbox:checked').length;

                        if (seleccionados > 0) {
                            totalGeneral += seleccionados;
                            html += '<div class="col-md-6 mb-3">';
                            html += '<div class="card card-outline card-success h-100">';
                            html += '<div class="card-header bg-success text-white">';
                            html += '<h6><b><i class="fas fa-folder-open"></i> ' + moduloNombre + '</b> <span class="badge badge-light float-right">' + seleccionados + ' permisos</span></h6>';
                            html += '</div>';
                            html += '<div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">';
                            html += '<ul class="list-unstyled mb-0">';

                            $('.permiso-item[data-modulo="' + moduloId + '"] .permiso-checkbox:checked').each(function() {
                                var label = $(this).closest('.permiso-item').find('.checkbox-label').text().trim();
                                var icono = $(this).closest('.permiso-item').find('.checkbox-label i').attr('class');
                                html += '<li class="mb-2"><i class="' + icono + ' text-success mr-2"></i> ' + label + '</li>';
                            });

                            html += '</ul>';
                            html += '</div></div></div>';
                        }
                    });

                    html += '</div>';

                    if ($('.permiso-checkbox:checked').length === 0) {
                        html = '<div class="alert alert-warning text-center">⚠️ No hay permisos seleccionados para este rol.</div>';
                    } else {
                        html = '<div class="alert alert-success mb-3"><i class="fas fa-chart-bar"></i> <strong>Total de permisos seleccionados:</strong> ' + totalGeneral + '</div>' + html;
                    }

                    return html;
                }

                // Mostrar resumen
                $('#resumenModal').on('show.bs.modal', function() {
                    $('#resumen-contenido').html(generarResumenHtml());
                });

                // Copiar resumen al portapapeles
                function copiarResumen() {
                    var texto = '';
                    $('.modulo-card').each(function() {
                        var moduloNombre = $(this).find('.card-title b').text();
                        var moduloId = $(this).attr('id').replace('modulo-', '');
                        var seleccionados = $('.permiso-item[data-modulo="' + moduloId + '"] .permiso-checkbox:checked').length;

                        if (seleccionados > 0) {
                            texto += '\n📁 ' + moduloNombre + ' (' + seleccionados + ' permisos):\n';
                            $('.permiso-item[data-modulo="' + moduloId + '"] .permiso-checkbox:checked').each(function() {
                                var label = $(this).closest('.permiso-item').find('.checkbox-label').text().trim();
                                texto += '  ✓ ' + label + '\n';
                            });
                        }
                    });

                    if (texto === '') {
                        texto = 'No hay permisos seleccionados.';
                    }

                    navigator.clipboard.writeText(texto).then(function() {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Copiado!',
                            text: 'El resumen se ha copiado al portapapeles',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    });
                }

                $('#copiar-resumen, #copiar-resumen-modal').click(copiarResumen);

                // Actualizar contadores
                actualizarTodosLosContadores();
                actualizarTotalSeleccionados();

                // Confirmación antes de guardar con SweetAlert
                $('#btn-guardar').click(function(e) {
                    e.preventDefault();
                    var seleccionados = $('.permiso-checkbox:checked').length;
                    var total = {{ Permission::count() }};

                    Swal.fire({
                        title: '¿Guardar cambios?',
                        html: `Se asignarán <strong>${seleccionados}</strong> permisos al rol <strong>{{ $rol->name }}</strong><br>
                               <small class="text-muted">Total disponibles: ${total} permisos</small>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
                        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Mostrar loading
                            Swal.fire({
                                title: 'Guardando...',
                                text: 'Asignando permisos al rol',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            $('#form-permisos').submit();
                        }
                    });
                });

                // Atajo de teclado Ctrl+F para enfocar el buscador
                $(document).on('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
                        e.preventDefault();
                        $('#filtro-permisos').focus();
                    }
                });
            @endif
        });
    </script>
@stop
