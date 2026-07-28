@extends('layouts.admin')

@section('title', 'Sucursales Stock')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sucursal_por_lotes.index') }}">Listado de sucursales</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sucursal: {{ $sucursal->nombre }}</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <!-- /.card-header -->
                <div class="card-header">
                    <div class="row w-100 align-items-center">
                        <div class="col-md-8">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-warehouse mr-2 text-primary"></i>
                                <b>Inventario - Sucursal: {{ $sucursal->nombre }}</b>
                            </h3>
                            <small class="text-muted ml-2">
                                <i class="fas fa-boxes"></i> Total: {{ count($inventario_sucursal_por_lotes) }} productos
                            </small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group" role="group">
                                <a href="{{ route('inventario.sucursal.pdf', $sucursal->id) }}"
                                    class="btn btn-danger btn-sm" target="_blank"
                                    title="Generar PDF del inventario completo">
                                    <i class="fas fa-file-pdf"></i>
                                    <span class="d-none d-md-inline">PDF</span>
                                </a>

                                <a href="{{ route('inventario.stock_bajo_sucursal', $sucursal->id) }}"
                                    class="btn btn-warning btn-sm" title="Ver solo productos con stock bajo">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="d-none d-md-inline">Stock Bajo</span>
                                </a>

                                <button type="button" class="btn btn-info btn-sm" onclick="window.location.reload()"
                                    title="Actualizar vista">
                                    <i class="fas fa-sync-alt"></i>
                                    <span class="d-none d-md-inline">Actualizar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table id="example1" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Nro</th>
                                <th>Código Producto</th>
                                <th>Producto</th>
                                <th>Cantidad Total</th>
                                <th>Stock Mínimo</th>
                                <th>Stock Maximo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inventario_sucursal_por_lotes as $item)
                                <tr class="{{ $item->cantidad <= $item->stock_minimo ? 'table-danger' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->codigo_producto }}</td>
                                    <td>{{ $item->producto }}</td>
                                    <td>{{ $item->cantidad }}</td>
                                    <td>{{ $item->stock_minimo }}</td>
                                    <td>{{ $item->stock_maximo }}</td>
                                    <td>
                                        @if ($item->cantidad <= $item->stock_minimo)
                                            <span class="badge badge-danger">Stock bajo</span>
                                        @elseif ($item->cantidad >= $item->stock_maximo)
                                            <span class="badge badge-success">Inventario completo</span>
                                        @else
                                            <span class="badge badge-warning">OK</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->cantidad == 0)
                                            <form action="{{ route('productos.desactivar', $item->producto_id) }}"
                                                id="miformulario{{ $item->producto_id }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="preguntar{{ $item->producto_id }}(event)">
                                                    <i class="fas fa-ban"></i> Desactivar
                                                </button>
                                            </form>
                                            <script>
                                                function preguntar{{ $item->producto_id }}(event) {
                                                    event.preventDefault();
                                                    Swal.fire({
                                                        title: "¿Desactivar {{ $item->producto }}?",
                                                        text: "El producto quedará inactivo en el sistema",
                                                        icon: "question",
                                                        showCancelButton: true,
                                                        confirmButtonColor: "#3085d6",
                                                        cancelButtonColor: "#d33",
                                                        confirmButtonText: "Sí, desactivar",
                                                        cancelButtonText: "Cancelar"
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            document.getElementById("miformulario{{ $item->producto_id }}").submit();
                                                        }
                                                    });
                                                }
                                            </script>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Estilos para DataTables */
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        #example1_wrapper .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        /* Colores de botones */
        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-default {
            background-color: #6e7176;
            color: #212529;
            border: none;
        }

        /* Estilos del header */
        /* Estilos del header */
        .card-header {
            padding: 0.75rem 1.25rem;
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .card-header .row {
            margin: 0;
            width: 100%;
        }

        .card-header .col-md-8,
        .card-header .col-md-4 {
            padding: 0;
        }

        .card-header .text-right {
            text-align: right !important;
        }

        .card-tools .btn-group .btn {
            border-radius: 4px !important;
            margin-left: 5px;
        }

        .card-tools .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .card-header .col-md-8,
            .card-header .col-md-4 {
                text-align: center !important;
                margin-bottom: 10px;
            }

            .card-header .col-md-4 {
                margin-bottom: 0;
            }

            .card-header .btn-group {
                justify-content: center;
            }

            .card-tools {
                margin-top: 10px;
                width: 100%;
            }

            .card-tools .btn-group {
                display: flex;
                width: 100%;
            }

            .card-tools .btn-group .btn {
                flex: 1;
                margin: 0 2px;
            }

            .d-none.d-md-inline {
                display: inline !important;
                margin-left: 5px;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ productos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 productos",
                    "infoFiltered": "(Filtrado de _MAX_ total productos)",
                    "lengthMenu": "Mostrar _MENU_ productos",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,

            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
