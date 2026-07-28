<div>
    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" wire:model.live="search" class="form-control"
                        placeholder="Buscar por código, cliente, vendedor...">
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="fecha_desde" class="form-control" placeholder="Fecha Desde">
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="fecha_hasta" class="form-control" placeholder="Fecha Hasta">
                </div>
                <div class="col-md-2">
                    <select wire:model.live="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="pagada">Pagada</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="anulada">Anulada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="tipo" class="form-control">
                        <option value="">Todos los tipos</option>
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-secondary btn-block" wire:click="limpiarFiltros">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TARJETAS DE RESUMEN --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($total_productos_vendidos) }}</h3>
                    <p>Productos Vendidos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>Bs {{ number_format($total_costo_compras, 2) }}</h3>
                    <p>Costo de Productos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Bs {{ number_format($total_ingresos_ventas, 2) }}</h3>
                    <p>Ingresos por Ventas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Bs {{ number_format($total_ganancia, 2) }}</h3>
                    <p>Ganancia Total</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Ventas --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Listado de Ventas
            </h3>
            {{-- <div class="card-tools">
                <a href="{{ route('ventas.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nueva Venta
                </a>
            </div> --}}
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Sucursal</th>
                            <th>Tipo</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Pagado</th>
                            <th class="text-right">Pendiente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $venta)
                            <tr>
                                <td class="text-center">{{ $venta->id }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $venta->codigo }}</td>
                                <td>{{ $venta->cliente ? $venta->cliente->nombre : 'CLIENTE OCASIONAL' }}</td>
                                <td>{{ $venta->user->name }}</td>
                                <td>{{ $venta->sucursal->nombre }}</td>
                                <td class="text-center">
                                    @if ($venta->tipo == 'contado')
                                        <span class="badge badge-success">Contado</span>
                                    @else
                                        <span class="badge badge-warning">Crédito</span>
                                    @endif
                                </td>
                                <td class="text-right">Bs {{ number_format($venta->total, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pagado, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($venta->pendiente, 2) }}</td>
                                <td class="text-center">
                                    @if ($venta->estado == 'pagada')
                                        <span class="badge badge-success">Pagada</span>
                                    @elseif($venta->estado == 'pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">Anulada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('ventas.nota-pdf', $venta->id) }}" target="_blank"
                                            class="btn btn-info" title="Ver Nota">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @if ($venta->estado == 'pendiente')
                                            <button class="btn btn-warning" title="Registrar Pago"
                                                wire:click="registrarPago({{ $venta->id }})">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                            {{-- <button class="btn btn-danger" title="Anular"
                                                onclick="confirmarAnulacion(event, {{ $venta->id }})">
                                                <i class="fas fa-ban"></i>
                                            </button> --}}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay ventas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {!! $ventas->links('pagination::bootstrap-4') !!}
        </div>
    </div>

    {{-- MODAL: Registrar Pagos Múltiples --}}
    @if ($mostrar_modal_pago)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-money-bill"></i> Registrar Pagos - Venta #{{ $venta_codigo_pago }}
                        </h5>
                        <button type="button" class="close" wire:click="cerrarModalPago">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            {{-- COLUMNA IZQUIERDA: Información y formulario --}}
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Cliente:</strong> {{ $cliente_pago }}<br>
                                    <strong>Total Venta:</strong> Bs {{ number_format($total_venta, 2) }}<br>
                                    <strong>Total Pagado:</strong> Bs {{ number_format($pagado_venta, 2) }}<br>
                                    <strong class="text-warning">Saldo Pendiente:</strong> Bs
                                    {{ number_format($pendiente_pago, 2) }}
                                </div>

                                @if ($pendiente_pago > 0)
                                    <h6 class="mt-3 mb-3">Registrar Nuevo Pago</h6>

                                    <div class="form-group">
                                        <label>Monto a Pagar <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" wire:model="monto_pago" class="form-control"
                                                step="0.01" min="0.01" max="{{ $pendiente_pago }}">
                                        </div>
                                        @error('monto_pago')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha de Pago <span class="text-danger">*</span></label>
                                        <input type="date" wire:model="fecha_pago" class="form-control">
                                        @error('fecha_pago')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label>Método de Pago</label>
                                        <select wire:model.live="metodo_pago" class="form-control">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="qr">QR</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="tarjeta">Tarjeta</option>
                                        </select>
                                    </div>

                                    {{-- Selección de Banca para QR o Transferencia --}}
                                    {{-- Selección de Banca para QR o Transferencia --}}
                                    @if (in_array($metodo_pago, ['qr', 'transferencia', 'tarjeta'], true))
                                        <div class="form-group">
                                            <label>Cuenta Bancaria @if (in_array($metodo_pago, ['qr', 'transferencia', 'tarjeta'], true))
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="{{ $banca_seleccionada ? $banca_seleccionada->nombre . ' - ' . $banca_seleccionada->banco . ' (' . $banca_seleccionada->numero_cuenta . ')' : 'Seleccione una cuenta' }}"
                                                    readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary" type="button"
                                                        wire:click="$set('mostrar_modal_bancas', true)">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                    @if ($banca_id)
                                                        <button class="btn btn-outline-danger" type="button"
                                                            wire:click="limpiarBanca">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                @if ($metodo_pago == 'qr')
                                                    Escanee el código QR de la cuenta seleccionada para realizar el pago.
                                                @else
                                                    Realice el pago a la cuenta seleccionada.
                                                @endif
                                            </small>
                                            @if ($metodo_pago === 'qr' && $banca_seleccionada)
                                                @if ($banca_seleccionada->qr_code)
                                                    <div class="payment-qr-card mt-3">
                                                        <div><strong>{{ $banca_seleccionada->banco }}</strong><div>{{ $banca_seleccionada->nombre }}</div><small>Cuenta: {{ $banca_seleccionada->numero_cuenta }}</small></div>
                                                        <img src="{{ asset('storage/' . $banca_seleccionada->qr_code) }}" alt="QR de pago" class="payment-qr-image">
                                                    </div>
                                                @else
                                                    <div class="alert alert-warning mt-2 mb-0">Esta cuenta no tiene imagen QR.</div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif

                                    <div class="form-group">
                                        <label>Referencia (opcional)</label>
                                        <input type="text" wire:model="referencia_pago" class="form-control"
                                            placeholder="N° de transferencia, voucher, etc.">
                                    </div>

                                    <div class="form-group">
                                        <label>Observaciones (opcional)</label>
                                        <textarea wire:model="observaciones_pago" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                                    </div>

                                    <button class="btn btn-warning btn-block" wire:click="guardarPago"
                                        {{ $pendiente_pago <= 0 ? 'disabled' : '' }}
                                        @if ((in_array($metodo_pago, ['qr', 'transferencia', 'tarjeta'], true)) && !$banca_id) disabled @endif>
                                        <i class="fas fa-save"></i> Registrar Pago
                                    </button>
                                @else
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> ¡Esta venta está completamente pagada!
                                    </div>
                                @endif
                            </div>

                            {{-- COLUMNA DERECHA: Listado de pagos --}}
                            <div class="col-md-6">
                                <h6 class="mb-3">Historial de Pagos</h6>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr class="text-center">
                                                <th>Fecha</th>
                                                <th>Monto</th>
                                                <th>Método</th>
                                                <th>Referencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($pagos_venta as $pago)
                                                <tr>
                                                    <td class="text-center">
                                                        {{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}</td>
                                                    <td class="text-right">Bs {{ number_format($pago->monto, 2) }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($pago->metodo_pago == 'efectivo')
                                                            <span class="badge badge-success">Efectivo</span>
                                                        @elseif($pago->metodo_pago == 'qr')
                                                            <span class="badge badge-info">QR</span>
                                                        @elseif($pago->metodo_pago == 'transferencia')
                                                            <span class="badge badge-primary">Transferencia</span>
                                                        @else
                                                            <span class="badge badge-secondary">Tarjeta</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $pago->referencia ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">
                                                        No hay pagos registrados
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr class="font-weight-bold">
                                                <td class="text-right">TOTAL:</td>
                                                <td class="text-right">Bs {{ number_format($pagado_venta, 2) }}</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalPago">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Seleccionar Banca --}}
    {{-- MODAL: Seleccionar Banca --}}
    @if ($mostrar_modal_bancas)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-university"></i> Seleccionar Cuenta Bancaria
                        </h5>
                        <button type="button" class="close text-white"
                            wire:click="$set('mostrar_modal_bancas', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if (count($bancas) > 0)
                            <div class="row">
                                @foreach ($bancas as $banca)
                                    <div class="col-md-6 mb-3">
                                        <div class="card {{ $banca_id == $banca->id ? 'border-primary' : '' }}"
                                            style="cursor: pointer; {{ $banca_id == $banca->id ? 'background-color: #e8f0fe;' : '' }}"
                                            wire:click="seleccionarBanca({{ $banca->id }})">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title mb-2">
                                                            <strong>{{ $banca->nombre }}</strong>
                                                            @if ($banca_id == $banca->id)
                                                                <span
                                                                    class="badge badge-success ml-2">Seleccionada</span>
                                                            @endif
                                                        </h6>
                                                        <p class="card-text small mb-1">
                                                            <strong>Banco:</strong> {{ $banca->banco }}<br>
                                                            <strong>N° Cuenta:</strong> {{ $banca->numero_cuenta }}<br>
                                                            <strong>Nombre de cuenta:</strong>
                                                            {{ $banca->nombre }}
                                                        </p>
                                                    </div>
                                                    @if ($banca->qr_code)
                                                        <img src="{{ asset('storage/' . $banca->qr_code) }}"
                                                            alt="QR"
                                                            style="width: 60px; height: 60px; object-fit: cover;">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No hay cuentas bancarias registradas.
                                <a href="{{ route('bancas.index') }}" class="alert-link">Registrar una</a>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('mostrar_modal_bancas', false)">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

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
    </style>
@stop

@push('js')
    <script>
        function confirmarAnulacion(event, ventaId) {
            event.preventDefault();
            Swal.fire({
                title: '¿Anular venta?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('anularVenta', ventaId);
                }
            });
        }

        document.addEventListener('livewire:init', function() {

            Livewire.on('venta-liquidada', () => {
            });
        });

        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informaciÃ³n",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Ventas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Categorias",
                    "infoFiltered": "(Filtrado de _MAX_ total Ventas)",
                    "lengthMenu": "Mostrar _MENU_ Ventas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ãšltimo",
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
@endpush
