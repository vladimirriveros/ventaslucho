@extends('layouts.admin')

@section('title', 'Compras')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
            {{-- <li class="breadcrumb-item"><a href="{{ url('/admin/compras') }}">Compras</a></li> --}}
            <li class="breadcrumb-item active" aria-current="page">Datos de la Compra nro {{ $compra->id }}</li>
        </ol>
    </nav>
    <hr>

    @if ($compra->estado == 'Recibido')
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{ route('compras.nota-pdf', $compra->id) }}" target="_blank" class="btn btn-success">
                                <i class="fas fa-file-pdf"></i> Ver Nota de Compra
                            </a>
                            <a href="{{ route('compras.descargar-nota', $compra->id) }}" class="btn btn-primary">
                                <i class="fas fa-download"></i> Descargar Nota
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('content')

    {{-- CARD-BODY CON LOS DATOS DE LA COMPRA CREADA --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><b>Compra creada</b></h3>
                    <!-- /.card-tools -->
                    {{-- Dentro del card-header --}}
                    <div class="card-tools">
                        @if ($compra->estado == 'Recibido')
                            <a href="{{ route('compras.correccion.edit', $compra->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Corregir Compra
                            </a>
                        @endif
                        {{-- otros botones --}}
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body" style="display: block;">
                    <div class="row">

                        {{-- PROVEEDOR DE LA COMPRA  --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="proveedor_id">Proveedores</label>
                                {{-- <p>{{ $compra->proveedor->nombre . ' | ' . $compra->proveedor->empresa }}</p> --}}
                                <p>{{ $compra->proveedor->nombre }}</p>
                            </div>
                        </div>

                        {{-- USUARIO QUE HIZO LA COMPRA --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="usuario">Usuario Responsable</label>
                                <p>
                                    @if ($compra->user)
                                        <i class="fas fa-user"></i> {{ $compra->user->name }}
                                    @else
                                        <span class="text-muted">No registrado</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- FECHA DE LA COMPRA  --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fecha">Fecha de la compra</label>
                                <p>{{ $compra->fecha }}</p>
                            </div>
                        </div>

                        {{-- OBSERVACIONES DE LA COMPRA --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <p>{{ $compra->observaciones }}</p>
                            </div>
                        </div>

                        {{-- ESTADO DE LA COMPRA  --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="estado">Estado de la compra</label>
                                <p>{{ $compra->estado }}</p>
                            </div>
                        </div>

                        {{-- TOTAL DE LA COMPRA --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="total_compra">Total de la compra</label>
                                <p>{{ $compra->total }}</p>
                            </div>
                        </div>

                        {{-- SUCURSAL DESTINO DE LA COMPRA --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="total_compra">Sucursal de destino</label>
                                {{-- <p>{{ $sucursal_destino->nombre }}</p> --}}
                                @if ($sucursal_destino)
                                    <p>{{ $sucursal_destino->nombre }}</p>
                                @else
                                    <p>No se ha seleccionado ninguna sucursal.</p>
                                @endif
                            </div>
                        </div>

                    </div>

                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>


    {{-- CARD-BODY PARA AGREGAR PRODUCTOS A LA COMPRA --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b>Detalle de la compra</b></h3>
                    <!-- /.card-tools -->
                </div>
                <!-- /.card-header -->
                <div class="card-body" style="display: block;">

                    <table id="example1" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Nro</th>
                                <th>Producto</th>
                                <th>Marca</th> {{-- NUEVA COLUMNA --}}
                                <th>Lote</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compra->detalles as $detalle)
                                <tr>
                                    <td style="text-align: center">{{ $loop->iteration }}</td>
                                    <td>{{ $detalle->producto->nombre }}</td>
                                    <td>
                                        @if ($detalle->producto->marca)
                                            <span class="badge badge-info">{{ $detalle->producto->marca }}</span>
                                        @else
                                            <span class="badge badge-secondary">Sin marca</span>
                                        @endif
                                    </td>
                                    <td>{{ $detalle->lote->codigo_lote }}</td>
                                    <td style="text-align: center">{{ $detalle->cantidad }}</td>
                                    <td style="text-align: right">Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                                    <td style="text-align: right">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-right">Total:</th>
                                <th class="text-right">Bs {{ number_format($compra->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    <a href="{{ route('compras.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
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
            /* TamaÃ±o de fuente */
        }

        /* Colores por tipo de botÃ³n */
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

        /* Estilo para los badges de marca */
        .badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 5,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorias",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Categorias",
                    "infoFiltered": "(Filtrado de _MAX_ total Categorias)",
                    "lengthMenu": "Mostrar _MENU_ Categorias",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
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
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        } // Actualizado para incluir Marca
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdfHtml5',
                        className: 'btn btn-danger',
                        orientation: 'portrait',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }, // Actualizado para incluir Marca
                        customize: function(doc) {

                            // ===== FORZAR ANCHO COMPLETO DE LA TABLA =====
                            var tableIndex = doc.content.findIndex(item => item.table && item.table
                                .body);

                            if (tableIndex !== -1) {

                                // ===== OCUPAR TODO EL ANCHO DE LA PÁGINA =====
                                var columnCount = doc.content[tableIndex].table.body[0].length;
                                doc.content[tableIndex].table.widths = Array(columnCount).fill('*');

                                // ===== CENTRAR TODO EL CONTENIDO DE LA TABLA =====
                                doc.content[tableIndex].table.body.forEach(function(row, rowIndex) {
                                    row.forEach(function(cell) {
                                        cell.alignment = 'center';
                                    });
                                });
                            }

                            // ===== TÍTULO =====
                            doc.content.unshift({
                                text: 'INFORME DE COMPRA',
                                alignment: 'center',
                                fontSize: 16,
                                bold: true,
                                margin: [0, 0, 0, 15]
                            });

                            // ===== DATOS GENERALES =====
                            doc.content.splice(1, 0, {
                                alignment: 'center',
                                table: {
                                    widths: ['50%', '50%'],
                                    body: [
                                        [{
                                            text: 'Salida N°:',
                                            bold: true,
                                            alignment: 'right'
                                        }, {
                                            text: '{{ $compra->id }}',
                                            alignment: 'left'
                                        }],
                                        [{
                                            text: 'Fecha:',
                                            bold: true,
                                            alignment: 'right'
                                        }, {
                                            text: '{{ $compra->fecha }}',
                                            alignment: 'left'
                                        }],
                                        [{
                                            text: 'Sucursal:',
                                            bold: true,
                                            alignment: 'right'
                                        }, {
                                            text: '{{ $sucursal_destino->nombre ?? '---' }}',
                                            alignment: 'left'
                                        }],
                                        [{
                                            text: 'Estado:',
                                            bold: true,
                                            alignment: 'right'
                                        }, {
                                            text: '{{ $compra->estado }}',
                                            alignment: 'left'
                                        }],
                                        [{
                                            text: 'Total:',
                                            bold: true,
                                            alignment: 'right'
                                        }, {
                                            text: 'Bs {{ number_format($compra->total, 2) }}',
                                            alignment: 'left'
                                        }],
                                    ]
                                },
                                layout: {
                                    hLineWidth: () => 0,
                                    vLineWidth: () => 0,
                                    paddingTop: () => 4,
                                    paddingBottom: () => 4
                                },
                                margin: [0, 0, 0, 20]
                            });

                            // ===== ESTILOS DE TABLA =====
                            doc.styles.tableHeader = {
                                bold: true,
                                fontSize: 10,
                                color: 'white',
                                fillColor: '#343a40',
                                alignment: 'center'
                            };

                            doc.defaultStyle.fontSize = 9;

                            // ===== PIE DE PÁGINA =====
                            doc.footer = function(currentPage, pageCount) {
                                return {
                                    columns: [{
                                            text: 'Sistema de Inventario',
                                            alignment: 'left',
                                            margin: [20, 0]
                                        },
                                        {
                                            text: 'Página ' + currentPage + ' de ' +
                                                pageCount,
                                            alignment: 'right',
                                            margin: [0, 0, 20]
                                        }
                                    ],
                                    fontSize: 8
                                };
                            };
                        }
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-warning',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }, // Actualizado para incluir Marca
                        customize: function(win) {

                            // Centrar todo el contenido
                            $(win.document.body).css('text-align', 'center');

                            // Centrar la TABLA
                            $(win.document.body).find('table')
                                .css({
                                    marginLeft: 'auto',
                                    marginRight: 'auto'
                                });

                            // Centrar encabezados y celdas
                            $(win.document.body).find('th, td')
                                .css('text-align', 'center');

                            // Tu encabezado
                            $(win.document.body)
                                .prepend(`
                                <div style="text-align:center;">
                                    <h2>INFORME DE COMPRA</h2>
                                    <p><strong>Compra N°:</strong> {{ $compra->id }}</p>
                                    <p><strong>Fecha:</strong> {{ $compra->fecha }}</p>
                                    <p><strong>Proveedor:</strong> {{ $compra->proveedor->nombre }}</p>
                                    <p><strong>Sucursal:</strong> {{ $sucursal_destino->nombre ?? '---' }}</p>
                                    <p><strong>Estado:</strong> {{ $compra->estado }}</p>
                                    <p><strong>Total:</strong> Bs {{ number_format($compra->total, 2) }}</p>
                                    <hr>
                                </div>
                            `);
                        }
                    }
                ]
            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
