<div>
    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" wire:model.live="search" class="form-control"
                        placeholder="Buscar por código, cliente...">
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
                        <option value="activa">Activa</option>
                        <option value="convertida">Convertida a Venta</option>
                        <option value="vencida">Vencida</option>
                        <option value="anulada">Anulada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary btn-block" wire:click="limpiarFiltros">
                        <i class="fas fa-undo"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Cotizaciones --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-invoice"></i> Listado de Cotizaciones
            </h3>
            <div class="card-tools">
                @can('cotizaciones.create')
                    <a href="{{ route('cotizaciones.create') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-plus"></i> Nueva Cotización
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Sucursal</th>
                            <th>Válida Hasta</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cotizaciones as $cotizacion)
                            <tr>
                                <td class="text-center">{{ $cotizacion->id }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}
                                </td>
                                <td class="text-center">{{ $cotizacion->codigo }}</td>
                                <td>{{ $cotizacion->cliente ? $cotizacion->cliente->nombre : 'CLIENTE OCASIONAL' }}</td>
                                <td>{{ $cotizacion->user->name }}</td>
                                <td>{{ $cotizacion->sucursal->nombre }}</td>
                                <td class="text-center">
                                    @if ($cotizacion->valida_hasta)
                                        {{ \Carbon\Carbon::parse($cotizacion->valida_hasta)->format('d/m/Y') }}
                                        @if (\Carbon\Carbon::parse($cotizacion->valida_hasta)->isPast() && $cotizacion->estado == 'activa')
                                            <span class="badge badge-danger">Vencida</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="text-right">Bs {{ number_format($cotizacion->total, 2) }}</td>
                                <td class="text-center">
                                    @if ($cotizacion->estado == 'activa')
                                        <span class="badge badge-warning">Activa</span>
                                    @elseif($cotizacion->estado == 'convertida')
                                        <span class="badge badge-success">Convertida</span>
                                    @elseif($cotizacion->estado == 'vencida')
                                        <span class="badge badge-danger">Vencida</span>
                                    @else
                                        <span class="badge badge-secondary">Anulada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cotizaciones.imprimir', $cotizacion->id) }}" target="_blank"
                                            class="btn btn-info" title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @if ($cotizacion->estado == 'activa')
                                            <a href="{{ route('cotizaciones.edit', $cotizacion->id) }}"
                                                class="btn btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{-- 🔄 CAMBIO: Botón fuera del form, solo el botón --}}
                                            <button type="button" class="btn btn-success btn-sm"
                                                title="Convertir a Venta"
                                                onclick="validarYConvertir({{ $cotizacion->id }})">
                                                <i class="fas fa-exchange-alt"></i> Convertir a Venta
                                            </button>
                                            <button class="btn btn-danger" title="Anular"
                                                onclick="confirmarAnulacion(event, {{ $cotizacion->id }})">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay cotizaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $cotizaciones->links() }}
        </div>
    </div>
</div>

@push('js')
    <script>
        // 🔄 CAMBIO: Función global única, fuera del bucle
        window.validarYConvertir = function(cotizacionId) {
            Swal.fire({
                title: 'Verificando stock...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Llamada AJAX para verificar stock
            fetch(`/admin/cotizaciones/verificar-stock/${cotizacionId}`)
                .then(response => {
                    // 🔄 CAMBIO: Primero verificar si la respuesta es exitosa
                    if (!response.ok) {
                        // Si no es ok (ej: 403, 500), convertir a JSON y lanzar error con la respuesta
                        return response.json().then(errorData => {
                            throw {
                                status: response.status,
                                data: errorData
                            };
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.ok) {
                        Swal.fire({
                            title: 'Stock disponible',
                            text: 'Todos los productos tienen stock. ¿Desea convertir a venta?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, convertir',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `/admin/ventas/create?cotizacion_id=${cotizacionId}`;
                            }
                        });
                    } else {
                        let mensaje = 'No se puede convertir a venta:\n\n';
                        if (data.sin_stock && data.sin_stock.length > 0) {
                            mensaje += '📦 PRODUCTOS SIN STOCK:\n';
                            data.sin_stock.forEach(p => {
                                mensaje += `• ${p.nombre} (${p.codigo}) - Cantidad: ${p.cantidad}\n`;
                            });
                        }
                        if (data.stock_insuficiente && data.stock_insuficiente.length > 0) {
                            mensaje += '\n⚠️ PRODUCTOS CON STOCK INSUFICIENTE:\n';
                            data.stock_insuficiente.forEach(p => {
                                mensaje +=
                                    `• ${p.nombre} (${p.codigo}) - Necesita: ${p.cantidad_necesaria} - Stock disponible: ${p.stock_disponible}\n`;
                            });
                        }
                        Swal.fire({
                            title: 'Stock insuficiente',
                            html: mensaje.replace(/\n/g, '<br>'),
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                })
                .catch(error => {

                    if (error.data?.error === 'caja_cerrada') {
                        Swal.fire({
                            title: 'Caja cerrada',
                            html: `${error.data.message || 'No hay una caja abierta en esta sucursal.'}<br><br>Debe abrir la caja antes de convertir a venta.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ir a abrir caja',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/admin/caja';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo verificar el stock. Intente nuevamente.',
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonText: 'Reintentar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                validarYConvertir(cotizacionId);
                            }
                        });
                    }
                });
        };

        function confirmarAnulacion(event, cotizacionId) {
            event.preventDefault();
            Swal.fire({
                title: '¿Anular cotización?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('anularCotizacion', cotizacionId);
                }
            });
        }
    </script>
@endpush
