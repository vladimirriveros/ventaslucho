<div>
    <div class="row">
        {{-- COLUMNA IZQUIERDA: Caja Activa --}}
        <div class="col-md-5">
            <div class="card {{ $caja_activa ? 'card-success' : 'card-secondary' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cash-register"></i>
                        Caja {{ $caja_activa ? 'ACTIVA' : 'CERRADA' }}
                    </h3>
                    <div class="card-tools">
                        @if ($caja_activa)
                            <span class="badge badge-success">
                                Abierta: {{ \Carbon\Carbon::parse($caja_activa->fecha_apertura)->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        @if (auth()->user()->can('operaciones.todas-sucursales'))
                            <label for="sucursal-supervision-caja">Sucursal a supervisar</label>
                            <select id="sucursal-supervision-caja" wire:model.live="sucursal_id" class="form-control">
                                @foreach ($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Filtro de consulta. El Superadministrador no puede abrir, cerrar ni modificar cajas.</small>
                        @else
                            <label>Sucursal de caja</label>
                            <div class="form-control bg-light">
                                <i class="fas fa-store mr-2 text-primary"></i>{{ auth()->user()->sucursal?->nombre ?? 'Sin sucursal' }}
                            </div>
                            <small class="text-muted">No editable. La caja pertenece a la sucursal asignada al usuario.</small>
                        @endif
                    </div>

                    @if ($sucursal_id)
                        @if ($caja_activa)
                            <div class="alert alert-success">
                                <strong>Monto inicial:</strong> Bs {{ number_format($caja_activa->monto_inicial, 2) }}
                                <hr class="my-2">
                                <strong>📊 INGRESOS POR MÉTODO:</strong><br>
                                <span class="text-dark">💰 Efectivo: + Bs
                                    {{ number_format($total_ingresos_efectivo, 2) }}</span><br>
                                <span class="text-dark">📱 QR/Transferencia: + Bs
                                    {{ number_format($total_ingresos_qr_transferencia, 2) }}</span><br>
                                <span class="text-warning">💳 Tarjeta: + Bs
                                    {{ number_format($total_ingresos_tarjeta, 2) }}</span>
                                <hr class="my-2">
                                <strong>📉 EGRESOS:</strong> <span class="text-danger">- Bs
                                    {{ number_format($total_egresos, 2) }}</span>
                                <hr class="my-2">
                                <strong>💰 TOTAL ESPERADO:</strong> Bs {{ number_format($monto_esperado, 2) }}
                            </div>

                            @if (auth()->user()->can('caja.movimientos') || auth()->user()->can('caja.cierre'))
                                <div class="row">
                                    @can('caja.movimientos')
                                        <div class="col-md-6">
                                            <button class="btn btn-warning btn-block" wire:click="abrirModalMovimiento">
                                                <i class="fas fa-exchange-alt"></i> Nuevo Movimiento
                                            </button>
                                        </div>
                                    @endcan
                                    @can('caja.cierre')
                                        <div class="col-md-6">
                                            <button class="btn btn-danger btn-block" wire:click="abrirModalCierre">
                                                <i class="fas fa-lock"></i> Cerrar Caja
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            @else
                                <div class="alert alert-primary mb-2">
                                    <i class="fas fa-eye mr-1"></i> Modo supervisión: esta caja es de solo lectura.
                                </div>
                            @endif
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    @can('ventas.create')
                                        <a href="{{ route('ventas.create') }}" class="btn btn-info btn-block">
                                            <i class="fas fa-cash-register"></i> Ir a Ventas
                                        </a>
                                    @endcan
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    @can('caja.reportes')
                                        <button class="btn btn-secondary btn-block" wire:click="abrirModalHistorial">
                                            <i class="fas fa-history"></i> Ver Historial de Cajas
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">No hay caja abierta.</div>

                            @can('caja.apertura')
                                <div class="form-group">
                                    <label>Monto Inicial</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Bs</span>
                                        </div>
                                        <input type="number" wire:model="monto_inicial" class="form-control" step="0.01"
                                            min="0">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Observaciones</label>
                                    <textarea wire:model="observaciones_apertura" class="form-control" rows="2"></textarea>
                                </div>

                                <button class="btn btn-success btn-block" wire:click="confirmarApertura">
                                    <i class="fas fa-unlock-alt"></i> Abrir Caja
                                </button>
                            @else
                                <div class="alert alert-primary">
                                    <i class="fas fa-eye mr-1"></i> Modo supervisión: no puede abrir una caja.
                                </div>
                            @endcan

                            <div class="row mt-2">
                                <div class="col-md-12">
                                    @can('caja.reportes')
                                        <button class="btn btn-secondary btn-block" wire:click="abrirModalHistorial">
                                            <i class="fas fa-history"></i> Ver Historial de Cajas
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">Seleccione una sucursal.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: Movimientos del día --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Movimientos del día
                        @if ($caja_activa)
                            <small class="text-muted">(Caja activa)</small>
                        @endif
                    </h3>
                    <div class="card-tools">
                        @if ($caja_activa && $movimientos_dia && $movimientos_dia->count() > 0)
                            <span class="badge badge-info">{{ $movimientos_dia->count() }} movimientos</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($caja_activa && $movimientos_dia && $movimientos_dia->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0"
                                style="min-width: 900px; table-layout: fixed;">
                                <thead class="bg-light">
                                    <tr class="text-center">
                                        <th style="width: 80px;">Hora</th>
                                        <th style="width: 80px;">Tipo</th>
                                        <th style="width: 200px;">Concepto</th>
                                        <th style="width: 100px;">Método</th>
                                        <th style="width: 100px;">Banca</th>
                                        <th style="width: 120px;">Monto</th>
                                        <th style="width: 70px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movimientos_dia as $mov)
                                        <tr>
                                            <td class="text-center">
                                                {{ \Carbon\Carbon::parse($mov->fecha)->format('H:i') }}</td>
                                            <td class="text-center">
                                                @if ($mov->tipo == 'ingreso')
                                                    <span class="badge badge-success">INGRESO</span>
                                                @else
                                                    <span class="badge badge-danger">EGRESO</span>
                                                @endif
                                            </td>
                                            <td style="word-wrap: break-word; white-space: normal;">
                                                {{ $mov->concepto }}</td>
                                            <td class="text-center">
                                                @switch($mov->metodo_pago)
                                                    @case('efectivo')
                                                        <span class="badge badge-success">Efectivo</span>
                                                    @break

                                                    @case('qr')
                                                        <span class="badge badge-info">QR</span>
                                                    @break

                                                    @case('transferencia')
                                                        <span class="badge badge-primary">Transferencia</span>
                                                    @break

                                                    @case('tarjeta')
                                                        <span class="badge badge-warning">Tarjeta</span>
                                                    @break

                                                    @default
                                                        <span class="badge badge-secondary">{{ $mov->metodo_pago }}</span>
                                                @endswitch
                                            </td>
                                            <td class="text-center">
                                                @if ($mov->metodo_pago == 'qr' || $mov->metodo_pago == 'transferencia')
                                                    @php
                                                        $partes = explode(' - ', $mov->concepto);
                                                        $bancaNombre = count($partes) > 1 ? end($partes) : null;
                                                    @endphp
                                                    @if ($bancaNombre)
                                                        <span class="badge badge-info">{{ $bancaNombre }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right {{ $mov->tipo == 'ingreso' ? 'text-success' : 'text-danger' }}"
                                                style="white-space: nowrap;">
                                                {{ $mov->tipo == 'ingreso' ? '+' : '-' }} Bs
                                                {{ number_format($mov->monto, 2) }}
                                            </td>
                                            <td class="text-center">
                                                @if ($mov->tipo == 'ingreso' && $mov->venta_id)
                                                    <button class="btn btn-sm btn-info"
                                                        wire:click="verDetalleVenta({{ $mov->venta_id }})"
                                                        title="Ver detalle de la venta">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="bg-success text-white">
                                        <th colspan="6" class="text-right">MONTO ESPERADO:</th>
                                        <th class="text-right">Bs {{ number_format($monto_esperado, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i>
                            @if ($caja_activa)
                                No hay movimientos registrados en el día.
                            @else
                                No hay una caja activa para mostrar movimientos.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Historial de Cajas Cerradas --}}
    @if ($mostrar_modal_historial)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-history"></i> Historial de Cajas Cerradas
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalHistorial">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label>Fecha Desde</label>
                                <input type="date" wire:model.live="fecha_desde" class="form-control">
                            </div>
                            <div class="col-md-5">
                                <label>Fecha Hasta</label>
                                <input type="date" wire:model.live="fecha_hasta" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button class="btn btn-secondary btn-block" wire:click="cargarCajasCerradas">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        @if (count($cajas_cerradas) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr class="text-center">
                                            <th>#</th>
                                            <th>Fecha Apertura</th>
                                            <th>Fecha Cierre</th>
                                            <th>Usuario</th>
                                            <th class="text-right">Monto Inicial</th>
                                            <th class="text-right">Monto Final</th>
                                            <th class="text-right">Diferencia</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cajas_cerradas as $caja)
                                            <tr>
                                                <td class="text-center">{{ $caja->id }}</td>
                                                <td>{{ \Carbon\Carbon::parse($caja->fecha_apertura)->format('d/m/Y H:i') }}
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($caja->fecha_cierre)->format('d/m/Y H:i') }}</td>
                                                <td>{{ $caja->user->name }}</td>
                                                <td class="text-right">Bs {{ number_format($caja->monto_inicial, 2) }}
                                                </td>
                                                <td class="text-right">Bs {{ number_format($caja->monto_final, 2) }}
                                                </td>
                                                <td
                                                    class="text-right {{ $caja->diferencia >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $caja->diferencia >= 0 ? '+' : '' }}Bs
                                                    {{ number_format($caja->diferencia, 2) }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-sm btn-info"
                                                            wire:click="imprimirReporte({{ $caja->id }})"
                                                            title="Imprimir reporte">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-success"
                                                            wire:click="verVentasCaja({{ $caja->id }})"
                                                            title="Ver ventas de esta caja">
                                                            <i class="fas fa-chart-line"></i> Ver Ventas
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No hay cajas cerradas en el período seleccionado.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalHistorial">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Nuevo Movimiento --}}
    @if ($mostrar_modal_movimiento)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exchange-alt"></i> Nuevo Movimiento de Caja
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalMovimiento">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tipo de Movimiento</label>
                            <select wire:model="tipo_movimiento" class="form-control">
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monto</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Bs</span>
                                </div>
                                <input type="number" wire:model="monto_movimiento" class="form-control"
                                    step="0.01" min="0.01">
                            </div>
                            @error('monto_movimiento')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <select wire:model="metodo_pago_movimiento" class="form-control">
                                <option value="efectivo">Efectivo</option>
                                <option value="qr">QR</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Concepto</label>
                            <textarea wire:model="concepto_movimiento" class="form-control" rows="2"
                                placeholder="Ej: Venta, Gasto, Retiro, etc."></textarea>
                            @error('concepto_movimiento')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        @if (!$caja_activa)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> No hay caja abierta. Este movimiento se
                                registrará sin asociar a una caja (solo administradores).
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="cerrarModalMovimiento">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="confirmarMovimiento">
                            <i class="fas fa-save"></i> Registrar Movimiento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Ver Ventas de Caja --}}
    @if ($mostrar_modal_ventas)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-chart-line"></i> Ventas de Caja #{{ $caja_seleccionada_id }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalVentas">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        {{-- Tarjetas de resumen --}}
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>{{ number_format($resumen_ventas_caja['cantidad_ventas']) }}</h3>
                                        <p>Total Ventas</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-secondary">
                                    <div class="inner">
                                        <h3>{{ number_format($resumen_ventas_caja['cantidad_productos']) }}</h3>
                                        <p>Productos Vendidos</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-boxes"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3>Bs {{ number_format($resumen_ventas_caja['total_compras'], 2) }}</h3>
                                        <p>Costo de Compra</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>Bs {{ number_format($resumen_ventas_caja['total_ventas'], 2) }}</h3>
                                        <p>Ingresos por Ventas</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta de ganancia --}}
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div
                                    class="card {{ $resumen_ventas_caja['total_ganancia'] >= 0 ? 'card-success' : 'card-danger' }}">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Resumen de Ganancia
                                        </h3>
                                    </div>
                                    <div class="card-body text-center">
                                        <h2
                                            class="{{ $resumen_ventas_caja['total_ganancia'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            Bs {{ number_format($resumen_ventas_caja['total_ganancia'], 2) }}
                                        </h2>
                                        <p class="text-muted">Ganancia = Ingresos - Costo de Compra</p>
                                        <div class="progress">
                                            @php
                                                $porcentajeGanancia =
                                                    $resumen_ventas_caja['total_ventas'] > 0
                                                        ? ($resumen_ventas_caja['total_ganancia'] /
                                                                $resumen_ventas_caja['total_ventas']) *
                                                            100
                                                        : 0;
                                            @endphp
                                            <div class="progress-bar {{ $porcentajeGanancia >= 0 ? 'bg-success' : 'bg-danger' }}"
                                                style="width: {{ abs($porcentajeGanancia) }}%">
                                                {{ number_format($porcentajeGanancia, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de ventas --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list"></i> Detalle de Ventas</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead class="bg-light">
                                            <tr class="text-center">
                                                <th>#</th>
                                                <th>Fecha</th>
                                                <th>Código</th>
                                                <th>Cliente</th>
                                                <th>Vendedor</th>
                                                <th>Producto</th>
                                                <th>Cant.</th>
                                                <th class="text-right">P.Compra</th>
                                                <th class="text-right">P.Venta</th>
                                                <th class="text-right">Subtotal Compra</th>
                                                <th class="text-right">Subtotal Venta</th>
                                                <th class="text-right">Ganancia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($detalles_ventas_caja as $index => $detalle)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-center">
                                                        {{ \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/Y') }}
                                                    </td>
                                                    <td class="text-center">{{ $detalle['venta_codigo'] }}</td>
                                                    <td>{{ $detalle['cliente'] }}</td>
                                                    <td>{{ $detalle['vendedor'] }}</td>
                                                    <td>
                                                        <strong>{{ $detalle['producto_nombre'] }}</strong><br>
                                                        <small
                                                            class="text-muted">{{ $detalle['producto_codigo'] }}</small>
                                                    </td>
                                                    <td class="text-center">{{ $detalle['cantidad'] }}</td>
                                                    <td class="text-right">Bs
                                                        {{ number_format($detalle['precio_compra'], 2) }}</td>
                                                    <td class="text-right">Bs
                                                        {{ number_format($detalle['precio_venta'], 2) }}</td>
                                                    <td class="text-right">Bs
                                                        {{ number_format($detalle['subtotal_compra'], 2) }}</td>
                                                    <td class="text-right">Bs
                                                        {{ number_format($detalle['subtotal_venta'], 2) }}</td>
                                                    <td
                                                        class="text-right {{ $detalle['ganancia'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        Bs {{ number_format($detalle['ganancia'], 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="12" class="text-center text-muted">No hay ventas
                                                        registradas en este período</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr class="font-weight-bold">
                                                <td colspan="9" class="text-right">TOTALES:</td>
                                                <td class="text-right">Bs
                                                    {{ number_format($resumen_ventas_caja['total_compras'], 2) }}</td>
                                                <td class="text-right">Bs
                                                    {{ number_format($resumen_ventas_caja['total_ventas'], 2) }}</td>
                                                <td
                                                    class="text-right {{ $resumen_ventas_caja['total_ganancia'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    Bs {{ number_format($resumen_ventas_caja['total_ganancia'], 2) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalVentas">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Detalle de Venta --}}
    @if ($mostrar_modal_detalle_venta && $detalle_venta_actual)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-receipt"></i> Detalle de Venta #{{ $detalle_venta_actual->codigo }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalDetalleVenta">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Información de la venta --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Fecha:</strong>
                                    {{ \Carbon\Carbon::parse($detalle_venta_actual->fecha)->format('d/m/Y H:i') }}<br>
                                    <strong>Cliente:</strong>
                                    {{ $detalle_venta_actual->cliente ? $detalle_venta_actual->cliente->nombre : 'CLIENTE OCASIONAL' }}<br>
                                    <strong>Vendedor:</strong> {{ $detalle_venta_actual->user->name }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <strong>Tipo:</strong> {{ ucfirst($detalle_venta_actual->tipo) }}<br>
                                    <strong>Estado:</strong>
                                    @if ($detalle_venta_actual->estado == 'pagada')
                                        <span class="badge badge-success">Pagada</span>
                                    @elseif($detalle_venta_actual->estado == 'pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">Anulada</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de productos --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="bg-light">
                                    <tr class="text-center">
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Lote</th>
                                        <th>Cantidad</th>
                                        <th class="text-right">Precio Unit.</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($productos_venta as $index => $detalle)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $detalle->producto->nombre }}</strong><br>
                                                <small class="text-muted">{{ $detalle->producto->codigo }}</small>
                                            </td>
                                            <td>{{ $detalle->lote->codigo_lote ?? 'N/A' }}</td>
                                            <td class="text-center">{{ $detalle->cantidad }}</td>
                                            <td class="text-right">Bs
                                                {{ number_format($detalle->precio_unitario, 2) }}</td>
                                            <td class="text-right">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="font-weight-bold">
                                        <td colspan="5" class="text-right">TOTAL:</td>
                                        <td class="text-right">Bs {{ number_format($detalle_venta_actual->total, 2) }}
                                        </td>
                                    </tr>
                                    @if ($detalle_venta_actual->descuento > 0)
                                        <tr>
                                            <td colspan="5" class="text-right">Descuento:</td>
                                            <td class="text-right">Bs
                                                {{ number_format($detalle_venta_actual->descuento, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="5" class="text-right">Pagado:</td>
                                        <td class="text-right">Bs
                                            {{ number_format($detalle_venta_actual->pagado, 2) }}</td>
                                    </tr>
                                    @if ($detalle_venta_actual->pendiente > 0)
                                        <tr class="text-warning">
                                            <td colspan="5" class="text-right">Pendiente:</td>
                                            <td class="text-right">Bs
                                                {{ number_format($detalle_venta_actual->pendiente, 2) }}</td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>

                        @if ($detalle_venta_actual->observaciones)
                            <div class="alert alert-secondary mt-3">
                                <strong>Observaciones:</strong><br>
                                {{ $detalle_venta_actual->observaciones }}
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('ventas.nota-pdf', $detalle_venta_actual->id) }}" target="_blank"
                            class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Ver Nota de Venta
                        </a>
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalDetalleVenta">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        document.addEventListener('livewire:init', function() {
            // ========== MODAL DE APERTURA ==========
            Livewire.on('mostrar-modal-apertura', () => {
                Swal.fire({
                    title: '¿Abrir caja?',
                    html: `
                    <div style="text-align: left">
                        <p><strong>Sucursal:</strong> ${document.querySelector('[wire\\:model="sucursal_id"] option:checked')?.text || 'Seleccionada'}</p>
                        <p><strong>Monto inicial:</strong> Bs ${parseFloat(@this.monto_inicial).toFixed(2)}</p>
                    </div>
                `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, abrir caja',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('confirmarApertura');
                    }
                });
            });

            // ========== MODAL DE CIERRE ==========
            // ========== MODAL DE CIERRE ==========
            // ========== MODAL DE CIERRE ==========
            Livewire.on('mostrar-modal-cierre', () => {
                // Obtener los valores actuales
                const montoEsperado = parseFloat(@this.monto_esperado).toFixed(2);
                const efectivo = parseFloat(@this.total_ingresos_efectivo).toFixed(2);
                const qrTransferencia = parseFloat(@this.total_ingresos_qr_transferencia).toFixed(2);
                const tarjeta = parseFloat(@this.total_ingresos_tarjeta).toFixed(2);

                Swal.fire({
                    title: '¿Cerrar caja?',
                    html: `
                        <div style="text-align: left">
                            <div class="alert alert-info mb-3">
                                <strong>📊 DESGLOSE DE INGRESOS:</strong><br>
                                <span class="text-black">💰 Efectivo: Bs ${efectivo}</span><br>
                                <span class="text-black">📱 QR/Transferencia: Bs ${qrTransferencia}</span><br>
                            </div>
                            <hr>
                            <p><strong>💰 MONTO ESPERADO TOTAL:</strong> Bs ${montoEsperado}</p>
                            <div class="form-group mt-3">
                                <label>💵 Monto final real (efectivo en caja):</label>
                                <input type="number" id="monto_final_real" class="swal2-input" step="0.01" value="${montoEsperado}" style="width: 100%">
                                <small class="text-muted">Ingrese el monto físico que hay en la caja (solo efectivo)</small>
                            </div>
                            <div class="form-group mt-2">
                                <label>📝 Observaciones:</label>
                                <textarea id="observaciones_cierre" class="swal2-textarea" placeholder="Notas sobre el cierre..."></textarea>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar caja',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const montoFinal = document.getElementById('monto_final_real').value;
                        const observaciones = document.getElementById('observaciones_cierre')
                            .value;
                        if (!montoFinal || montoFinal === '') {
                            Swal.showValidationMessage('Debe ingresar el monto final');
                            return false;
                        }
                        if (parseFloat(montoFinal) < 0) {
                            Swal.showValidationMessage('El monto final no puede ser negativo');
                            return false;
                        }
                        return {
                            montoFinal: parseFloat(montoFinal),
                            observaciones: observaciones
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        @this.set('monto_final_real', result.value.montoFinal);
                        @this.set('observaciones_cierre', result.value.observaciones);
                        @this.call('confirmarCierre');
                    }
                });
            });

            Livewire.on('mostrar-alerta-cierre-con-pdf', (data) => {
                Swal.fire({
                    title: '✅ Caja Cerrada',
                    html: `
            <div style="text-align: left">
                <p>${data.mensaje.replace(/\n/g, '<br>')}</p>
                <hr>
                <p><strong>¿Qué deseas hacer?</strong></p>
                <div class="mt-3">
                    <button id="btnDescargarPDF" class="btn btn-success btn-block mb-2">
                        <i class="fas fa-file-pdf"></i> Descargar Reporte de Ventas
                    </button>
                    <button id="btnVerVentas" class="btn btn-info btn-block">
                        <i class="fas fa-chart-line"></i> Ver Ventas del Día
                    </button>
                </div>
            </div>
        `,
                    icon: 'success',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar',
                    didOpen: () => {
                        const btnDescargar = document.getElementById('btnDescargarPDF');
                        const btnVerVentas = document.getElementById('btnVerVentas');

                        if (btnDescargar) {
                            btnDescargar.addEventListener('click', () => {
                                window.open(data.pdf_url, '_blank');
                            });
                        }

                        if (btnVerVentas) {
                            btnVerVentas.addEventListener('click', () => {
                                @this.call('verVentasCaja', data.caja_id);
                                Swal.close();
                            });
                        }
                    }
                });
            });

            // ========== ALERTA DE CIERRE EXITOSO ==========
            Livewire.on('mostrar-alerta-cierre-exitoso', (data) => {
                Swal.fire({
                    title: '✅ Caja Cerrada',
                    html: `
                    <div style="text-align: left">
                        <p>${data.mensaje.replace(/\n/g, '<br>')}</p>
                        <hr>
                        <p><strong>¿Qué deseas hacer?</strong></p>
                    </div>
                `,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '📊 Ver Reporte de Ventas',
                    cancelButtonText: '📄 Descargar Reporte PDF',
                    showDenyButton: true,
                    denyButtonText: '🔍 Ver en Reportes',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#ffc107',
                    denyButtonColor: '#17a2b8'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(`{{ url('admin/caja/reporte-ventas') }}/${data.caja_id}`,
                            '_blank');
                    } else if (result.isDenied) {
                        window.location.href =
                            `{{ url('admin/reportes/ventas') }}?caja_id=${data.caja_id}&fecha_desde=${data.fecha_apertura.split(' ')[0]}&fecha_hasta=${new Date().toISOString().split('T')[0]}`;
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        Swal.fire('Caja cerrada',
                            'Puedes generar el reporte más tarde desde el historial.', 'info');
                    }
                });
            });

            // ========== ALERTA GENERAL ==========
            Livewire.on('mostrar-alerta', (data) => {
                Swal.fire({
                    title: data.mensaje,
                    icon: data.icono,
                    confirmButtonText: 'Aceptar'
                });
            });
        });
    </script>
@endpush
