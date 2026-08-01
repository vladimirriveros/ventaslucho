@extends('layouts.admin')

@section('title', 'Compras')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
            <li class="breadcrumb-item active" aria-current="page">Listado de Compras</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- TARJETA DE RESUMEN DE TOTALES --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $compras->count() }}</h3>
                            <p>Total de Compras</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <i class="fas fa-chart-line"></i> Registradas
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>Bs {{ number_format($compras->sum('total'), 2) }}</h3>
                            <p>Total Invertido</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <i class="fas fa-chart-pie"></i> Suma total
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            @php
                                $comprasPendientes = $compras->where('estado', 'pendiente')->count();
                                $comprasRecibidas = $compras->where('estado', 'Recibido')->count();
                                $comprasEnviadas = $compras->where('estado', 'enviado al proveedor')->count();
                            @endphp
                            <h3>{{ $comprasPendientes }} / {{ $comprasRecibidas }} / {{ $comprasEnviadas }}</h3>
                            <p>Pendientes / Recibidas / Enviadas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <i class="fas fa-filter"></i> Por estado
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b>Compras registradas</b></h3>

                    <div class="card-tools">
                        @can('compras.create')
                            <a class="btn btn-primary" href="{{ route('compras.create') }}">Crear nuevo</a>
                        @else
                            <span class="badge badge-info px-3 py-2"><i class="fas fa-eye mr-1"></i>Modo supervisión</span>
                        @endcan
                    </div>
                    <!-- /.card-tools -->
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive" style="display: block;">
                    <table id="example1" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Nro</th>
                                <th>Proveedor</th>
                                <th>Usuario</th>
                                <th>Fecha de la Compra</th>
                                <th>Total de la Compra</th>
                                <th>Estado</th>
                                <th style="text-align: center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compras as $compra)
                                <tr class="{{ $compra->estado == 'Finalice y envie a Sucursal' ? 'table-primary' : '' }}">
                                    <td style="text-align: center">{{ $loop->iteration }}</td>
                                    {{-- <td>{{ $compra->proveedor_id }}</td> --}}
                                    <td>{{ $compra->proveedor()->first()->nombre }}</td>
                                    <td>{{ $compra->user->name }}</td>
                                    <td>{{ $compra->fecha }}</td>
                                    <td class="text-right">Bs {{ number_format($compra->total, 2) }}</td>
                                    <td>
                                        @if ($compra->estado == 'Recibido')
                                            <span class="badge badge-success">{{ $compra->estado }}</span>
                                        @elseif($compra->estado == 'pendiente')
                                            <span class="badge badge-warning">{{ $compra->estado }}</span>
                                        @elseif($compra->estado == 'enviado al proveedor')
                                            <span class="badge badge-info">{{ $compra->estado }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $compra->estado }}</span>
                                        @endif
                                    </td>

                                    <td style="text-align: center">
                                        <div class="btn-group" role="group" aria-label="Basic example">
                                            <a href="{{ route('compras.show', $compra->id) }}"
                                                class="btn btn-info btn-sm"><i class="fas fa-eye"></i> </a>

                                            @if ($compra->estado != 'Recibido')
                                                @can('compras.edit')
                                                    <a href="{{ route('compras.edit', $compra->id) }}"
                                                        class="btn btn-success btn-sm"><i class="fas fa-edit"></i> Continuar</a>
                                                @endcan
                                                @if ($compra->estado != 'Finalice y envie a Sucursal')
                                                    @can('compras.destroy')
                                                        <form action="{{ route('compras.destroy', $compra->id) }}"
                                                            id="miformulario{{ $compra->id }}" method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                onclick="preguntar{{ $compra->id }}(event)">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                        <script>
                                                            function preguntar{{ $compra->id }}(event) {
                                                                event.preventDefault();
                                                                Swal.fire({
                                                                    title: "¿Desea cancelar la compra #{{ $compra->id }}?",
                                                                    text: "Esta acción no se puede revertir.",
                                                                    icon: "question",
                                                                    showCancelButton: true,
                                                                    confirmButtonColor: "#3085d6",
                                                                    cancelButtonColor: "#d33",
                                                                    confirmButtonText: "Sí, cancelar",
                                                                    cancelButtonText: "Volver"
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        document.getElementById("miformulario{{ $compra->id }}").submit();
                                                                    }
                                                                });
                                                            }
                                                        </script>
                                                    @endcan
                                                @endif
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">TOTAL GENERAL:</th>
                                <th class="text-right">Bs {{ number_format($compras->sum('total'), 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
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
        /* Fondo transparente y sin borde en el contenedor */
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            /* Centrar los botones */
            gap: 10px;
            /* Espaciado entre botones */
            margin-bottom: 15px;
            /* Separar botones de la tabla */
        }

        /* Estilo personalizado para los botones */
        #example1_wrapper .btn {
            color: #fff;
            /* Color del texto en blanco */
            border-radius: 4px;
            /* Bordes redondeados */
            padding: 5px 15px;
            /* Espaciado interno */
            font-size: 14px;
            /* Tamaño de fuente */
        }

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

        /* Estilos para badges de estado */
        .badge {
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 4px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Estilos para las tarjetas de resumen */
        .small-box {
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .small-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .small-box .inner h3 {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }

        .small-box .inner p {
            font-size: 14px;
            margin: 5px 0 0 0;
        }

        .small-box .icon {
            font-size: 50px;
            opacity: 0.3;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "scrollX": true,
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Compras",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Compras",
                    "infoFiltered": "(Filtrado de _MAX_ total Compras)",
                    "lengthMenu": "Mostrar _MENU_ Compras",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando",
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
                buttons: [{
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-warning',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                ]
            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
