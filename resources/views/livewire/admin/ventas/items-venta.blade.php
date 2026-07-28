<div>
    @if ($borrador_restaurado)
        <div class="alert alert-info d-flex align-items-center gap-2"><i class="fas fa-history"></i><div><strong>Venta recuperada.</strong> Se restauró el borrador guardado automáticamente en esta sesión.</div></div>
    @elseif(count($carrito) > 0)
        <div class="small text-success mb-2"><i class="fas fa-cloud mr-1"></i>Borrador guardado automáticamente</div>
    @endif

    {{-- ALERTA DE CAJA CERRADA --}}
    @if ($sucursal_id && !$this->verificarCajaAbierta())
        <div class="alert alert-danger mb-3 text-center">
            <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
            <strong>¡CAJA CERRADA!</strong><br>
            No hay una caja abierta en esta sucursal.<br>
            Debe abrir la caja antes de realizar ventas.
            <div class="mt-3">
                <a href="{{ route('caja.index') }}" class="btn btn-danger">
                    <i class="fas fa-cash-register"></i> Ir a Abrir Caja
                </a>
            </div>
        </div>
    @endif

    {{-- FORMULARIO DE VENTA (solo visible si hay caja abierta O si no hay sucursal seleccionada) --}}
    @if (!$sucursal_id || ($sucursal_id && $this->verificarCajaAbierta()))
        <div class="row">
            {{-- COLUMNA IZQUIERDA: FORMULARIO DE VENTA --}}
            <div class="col-md-5">
                {{-- La sucursal siempre se obtiene del usuario autenticado. --}}
                <div class="card card-primary card-outline mb-3 app-operation-card">
                    <div class="card-body py-3">
                        <label class="form-label mb-1">Sucursal de operación</label>
                        <div class="form-control bg-light d-flex align-items-center">
                            <i class="fas fa-store mr-2 text-primary"></i>{{ auth()->user()->sucursal->nombre }}
                        </div>
                        <small class="text-muted">No editable. La venta descuenta inventario únicamente de esta sucursal.</small>
                    </div>
                </div>

                {{-- Búsqueda de Cliente --}}
                <div class="card card-info card-outline mb-3 app-operation-card">
                    <div class="card-body">
                        <div class="form-group mb-2 product-search-wrapper">
                            <label>Cliente</label>
                            <div class="input-group">
                                <input type="text" wire:model.live="busqueda_cliente" class="form-control"
                                    placeholder="Buscar por nombre, NIT o teléfono..." autocomplete="off">
                                <div class="input-group-append">
                                    @can('clientes.store')
                                        <button class="btn btn-outline-success" type="button"
                                            wire:click="abrirModalCliente" title="Agregar cliente rápido">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    @endcan
                                    @if ($cliente_id)
                                        <button class="btn btn-outline-danger" type="button"
                                            wire:click="limpiarCliente" title="Limpiar cliente">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Resultados de búsqueda de clientes --}}
                            @if ($mostrar_resultados_clientes && count($clientes_filtrados) > 0)
                                <div class="list-group mt-1 app-search-results">
                                    @foreach ($clientes_filtrados as $cliente)
                                        <a href="#" class="list-group-item list-group-item-action"
                                            wire:click.prevent="seleccionarCliente({{ $cliente->id }})">
                                            <strong>{{ $cliente->nombre }}</strong><br>
                                            <small>NIT: {{ $cliente->nit ?: 'N/A' }} | Tel:
                                                {{ $cliente->telefono ?: 'N/A' }}</small>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($cliente_id)
                            @php $cliente = \App\Models\Cliente::find($cliente_id); @endphp
                            <div class="alert alert-success mb-0 mt-2 p-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-check"></i>
                                        <strong>{{ $cliente->nombre }}</strong>
                                        @if ($cliente->nit)
                                            <span class="badge badge-info ml-1">NIT: {{ $cliente->nit }}</span>
                                        @endif
                                        @if ($cliente->tipo == 'credito')
                                            <span class="badge badge-warning">Crédito</span>
                                            <small>Límite: Bs {{ number_format($cliente->limite_credito, 2) }}</small>
                                        @endif
                                    </div>
                                    @can('clientes.update')
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="editarCliente"
                                            title="Editar cliente">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Búsqueda de Productos --}}
                <div class="form-group mb-2 product-search-wrapper">
                    <label>Producto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" wire:model.live="busqueda_producto" id="buscador-producto"
                            class="form-control" placeholder="Buscar por código, nombre o marca..." autocomplete="off"
                            aria-autocomplete="list" aria-expanded="false">
                        <div class="input-group-append">
                            <button class="btn btn-primary" wire:click="agregarAlCarrito"
                                @if (!$productoId) disabled @endif>
                                <i class="fas fa-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>

                    {{-- Resultados de búsqueda de productos --}}
                    @if (count($productos_filtrados) > 0)
                        <div id="resultados-busqueda" class="list-group mt-1 app-search-results product-search-results">
                            @foreach ($productos_filtrados as $index => $producto)
                                <a href="#" class="list-group-item list-group-item-action resultado-item"
                                    data-id="{{ $producto->id }}" data-nombre="{{ $producto->nombre }}"
                                    data-index="{{ $index }}"
                                    wire:click.prevent="seleccionarProductoYAgregar({{ $producto->id }})">
                                    <strong>{{ $producto->codigo }} - {{ $producto->nombre }}</strong>
                                    <small class="text-muted d-block">{{ $producto->marca->nombre ?: 'Sin marca' }}</small>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- OPCIONES DE PAGO --}}
                @if (count($carrito) > 0)
                    <div class="card card-warning mt-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0"><i class="fas fa-wallet mr-2"></i>Cobro</h3>
                            <span class="badge badge-primary">Total Bs {{ number_format($totalVenta, 2) }}</span>
                        </div>
                        <div class="card-body">
                            @can('ventas.aplicar-descuento')
                                <button type="button" class="btn btn-outline-warning btn-sm mb-3" wire:click="abrirModalDescuento"><i class="fas fa-percent mr-1"></i>Aplicar descuento</button>
                            @endcan

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Tipo de venta</label>
                                    <select wire:model.live="tipo_venta" class="form-select"><option value="contado">Contado</option><option value="credito">Crédito</option></select>
                                </div>
                                @if ($tipo_venta === 'contado')
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Método de pago</label>
                                        <select wire:model.live="metodo_pago" class="form-select">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="qr">QR</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="mixto">Mixto: efectivo + QR</option>
                                        </select>
                                    </div>
                                @endif
                            </div>

                            @if ($tipo_venta === 'contado' && in_array($metodo_pago, ['qr', 'transferencia', 'tarjeta', 'mixto'], true))
                                <div class="mt-3">
                                    <label class="form-label">Cuenta de destino <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $banca_seleccionada?->nombre ?? 'Seleccione una cuenta activa' }}" readonly>
                                        <button class="btn btn-outline-primary" type="button" wire:click="$set('mostrar_modal_bancas', true)"><i class="fas fa-search"></i></button>
                                        @if($banca_id)<button class="btn btn-outline-danger" type="button" wire:click="limpiarBanca"><i class="fas fa-times"></i></button>@endif
                                    </div>
                                </div>

                                @if($banca_seleccionada)
                                    <div class="payment-qr-card mt-3">
                                        <div><strong>{{ $banca_seleccionada->banco }}</strong><div>{{ $banca_seleccionada->nombre }}</div><small>Cuenta: {{ $banca_seleccionada->numero_cuenta }}</small></div>
                                        @if($banca_seleccionada->qr_code)
                                            <img src="{{ asset('storage/' . $banca_seleccionada->qr_code) }}" alt="QR de {{ $banca_seleccionada->nombre }}" class="payment-qr-image">
                                        @elseif(in_array($metodo_pago, ['qr', 'mixto'], true))
                                            <span class="badge badge-warning">Esta cuenta no tiene imagen QR</span>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            @if ($tipo_venta === 'contado' && $metodo_pago === 'mixto')
                                <div class="row g-3 mt-1">
                                    <div class="col-12 col-md-4"><label class="form-label">Parte en efectivo</label><div class="input-group"><span class="input-group-text">Bs</span><input type="number" min="0.01" max="{{ $totalVenta }}" step="0.01" wire:model.live.debounce.300ms="monto_efectivo_mixto" class="form-control"></div></div>
                                    <div class="col-12 col-md-4"><label class="form-label">Parte por QR</label><div class="input-group"><span class="input-group-text">Bs</span><input type="text" value="{{ number_format($monto_qr_mixto, 2, '.', '') }}" class="form-control" readonly></div></div>
                                    <div class="col-12 col-md-4"><label class="form-label">Efectivo recibido</label><div class="input-group"><span class="input-group-text">Bs</span><input type="number" min="{{ $monto_efectivo_mixto }}" step="0.01" wire:model.live.debounce.300ms="efectivo_recibido" class="form-control"></div><small class="text-muted">Cambio: Bs {{ number_format($cambio, 2) }}</small></div>
                                </div>
                            @elseif ($tipo_venta === 'contado' && $metodo_pago === 'efectivo')
                                <div class="row g-3 mt-1">
                                    <div class="col-12 col-md-6"><label class="form-label">Efectivo recibido</label><div class="input-group"><span class="input-group-text">Bs</span><input type="number" wire:model.live.debounce.300ms="efectivo_recibido" class="form-control" step="0.01" min="{{ $totalVenta }}" onclick="this.select()"></div><small class="text-muted">Debe ser mayor o igual al total.</small></div>
                                    <div class="col-12 col-md-6"><label class="form-label">Cambio</label><input type="text" class="form-control" value="Bs {{ number_format($cambio, 2) }}" readonly></div>
                                </div>
                            @endif

                            @if ($tipo_venta === 'credito' && !$cliente_id)
                                <div class="alert alert-warning mt-3 mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>Seleccione un cliente para registrar una venta a crédito.</div>
                            @endif
                            @if ($tipo_venta === 'contado' && in_array($metodo_pago, ['qr','transferencia','tarjeta','mixto'], true) && !$banca_id)
                                <div class="alert alert-warning mt-3 mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>Seleccione una cuenta bancaria.</div>
                            @endif

                            @php
                                $pagoInvalido = ($tipo_venta === 'credito' && !$cliente_id)
                                    || ($tipo_venta === 'contado' && in_array($metodo_pago, ['qr','transferencia','tarjeta','mixto'], true) && !$banca_id)
                                    || ($tipo_venta === 'contado' && $metodo_pago === 'efectivo' && (float)$efectivo_recibido < (float)$totalVenta)
                                    || ($tipo_venta === 'contado' && $metodo_pago === 'mixto' && ((float)$monto_efectivo_mixto <= 0 || (float)$monto_qr_mixto <= 0 || (float)$efectivo_recibido < (float)$monto_efectivo_mixto));
                            @endphp
                            <button class="btn btn-lg w-100 mt-3 {{ $pagoInvalido ? 'btn-secondary' : 'btn-success' }}" wire:click="confirmarVenta" @disabled($pagoInvalido)>
                                <i class="fas fa-check-circle mr-1"></i>{{ $tipo_venta === 'contado' ? 'Cobrar y finalizar venta' : 'Registrar venta a crédito' }}
                            </button>
                        </div>
                    </div>
                @endif

            </div>

            {{-- COLUMNA DERECHA: CARRITO DE VENTA --}}
            <div class="col-md-7">
                <div class="card {{ count($carrito) > 0 ? 'card-success' : 'card-secondary' }} card-outline app-operation-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shopping-cart"></i> Carrito de Venta
                        </h3>
                        @if (count($carrito) > 0)
                            <div class="card-tools d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="descartarBorrador">
                                    <i class="fas fa-eraser"></i> Descartar borrador
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" wire:click="vaciarCarrito">
                                    <i class="fas fa-trash-alt"></i> Vaciar carrito
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if (count($carrito) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Lote</th>
                                            <th>Vence</th>
                                            <th width="80">Cant.</th>
                                            <th width="100">Precio</th>
                                            <th width="100">Subtotal</th>
                                            <th width="40"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($carrito as $index => $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item['producto_nombre'] }}</strong><br>
                                                    <small class="text-muted">{{ $item['producto_codigo'] }}</small>
                                                    @if (isset($item['multi_lote']) && $item['multi_lote'])
                                                        <br>
                                                        <small class="badge badge-info">CPP: Bs
                                                            {{ number_format($item['precio_unitario'], 2) }}</small>
                                                        <br>
                                                        <small class="text-muted">
                                                            @foreach ($item['lotes_usados'] as $lote)
                                                                📦 {{ $lote['lote_codigo'] }}: {{ $lote['cantidad'] }}
                                                                und
                                                                @ Bs
                                                                {{ number_format($lote['precio_unitario'], 2) }}<br>
                                                            @endforeach
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item['multi_lote']) && $item['multi_lote'])
                                                        <small>MÚLTIPLES LOTES</small>
                                                    @else
                                                        <small>{{ $item['lote_codigo'] }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (!$item['multi_lote'] && $item['created_at'])
                                                        <small class="badge badge-info">
                                                            {{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y') }}
                                                        </small>
                                                    @else
                                                        <small class="text-muted">-</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="number" value="{{ $item['cantidad'] }}"
                                                        wire:change="actualizarCantidadCarrito({{ $index }}, $event.target.value)"
                                                        class="form-control form-control-sm text-center"
                                                        min="1"
                                                        max="{{ $this->obtenerStockMaximoProducto($item['producto_id']) }}">
                                                </td>
                                                <td class="text-left" style="width: 150px;">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Bs</span>
                                                        </div>
                                                        @can('ventas.modificar-precio')
                                                            <input type="number" value="{{ $item['precio_unitario'] }}"
                                                                wire:change="actualizarPrecioUnitario({{ $index }}, $event.target.value)"
                                                                class="form-control form-control-sm text-left"
                                                                step="0.01" min="0.01" style="font-weight: bold;">
                                                        @else
                                                            <input type="text" value="{{ number_format($item['precio_unitario'], 2) }}"
                                                                class="form-control form-control-sm text-left" readonly>
                                                        @endcan
                                                    </div>
                                                    @if (isset($item['multi_lote']) && $item['multi_lote'])
                                                        <small class="text-muted">(Precio promedio CPP)</small>
                                                    @endif
                                                </td>

                                                <td class="text-right">
                                                    <strong>Bs {{ number_format($item['subtotal'], 2) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="eliminarDelCarrito({{ $index }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <th colspan="5" class="text-right">Subtotal:</th>
                                            <th class="text-right">Bs {{ number_format($subtotalVenta, 2) }}</th>
                                            <th></th>
                                        </tr>
                                        @if ($descuento_monto > 0)
                                            <tr class="text-success">
                                                <th colspan="5" class="text-right">Descuento:</th>
                                                <th class="text-right">- Bs {{ number_format($descuento_monto, 2) }}</th>
                                                <th></th>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th colspan="5" class="text-right">TOTAL:</th>
                                            <th class="text-right">Bs {{ number_format($totalVenta, 2) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle"></i> El carrito está vacío. Busque y agregue productos.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- DATOS ADICIONALES DE LA VENTA --}}
                @if (count($carrito) > 0)
                    <div class="card card-secondary mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clipboard-list"></i> Datos Adicionales de la Venta
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Incluye Impuesto</label>
                                        <select wire:model="incluye_impuesto" class="form-control">
                                            <option value="con_impuesto">Con Impuesto de Ley</option>
                                            <option value="sin_impuesto">Sin Impuesto</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Lugar de Entrega</label>
                                        <input type="text" wire:model="lugar_entrega" class="form-control"
                                            placeholder="Dirección completa de entrega">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Forma de Pago</label>
                                        <select wire:model="forma_pago" class="form-control">
                                            <option value="contado">Al Contado</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Plazo de Entrega (días)</label>
                                        <input type="number" wire:model="plazo_entrega" class="form-control"
                                            min="1" max="30">
                                        <small class="text-muted">Tiempo estimado de entrega en días hábiles</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Validez Economica (horas)</label>
                                        <input type="number" wire:model="validez_economica" class="form-control"
                                            min="1" max="72">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Observaciones Adicionales</label>
                                        <textarea wire:model="observaciones_adicionales" class="form-control" rows="2"
                                            placeholder="Notas adicionales sobre la venta..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- MODALES (Cliente Rápido, Descuento, Editar Cliente, Seleccionar Banca) --}}
        {{-- Mantén todos tus modales aquí --}}
    @else
        {{-- Si no hay sucursal seleccionada, mostrar mensaje --}}
        @if (!$sucursal_id)
            <div class="alert alert-info text-center mt-4">
                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                <strong>Seleccione una sucursal</strong><br>
                Para comenzar, seleccione una sucursal en el panel superior.
            </div>
        @endif
    @endif

    {{-- MODALES (siempre deben estar fuera de la condición para funcionar) --}}
    {{-- MODAL: Cliente Rápido --}}
    @if ($mostrar_modal_cliente)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus"></i> Agregar Cliente Rápido
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalCliente">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nuevo_cliente_nombre" class="form-control"
                                placeholder="Nombre completo">
                            @error('nuevo_cliente_nombre')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>NIT</label>
                            <input type="text" wire:model="nuevo_cliente_nit" class="form-control"
                                placeholder="NIT (opcional)">
                            @error('nuevo_cliente_nit')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" wire:model="nuevo_cliente_telefono" class="form-control"
                                placeholder="Teléfono (opcional)">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" wire:model="nuevo_cliente_email" class="form-control"
                                placeholder="Email (opcional)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="cerrarModalCliente">Cancelar</button>
                        <button type="button" class="btn btn-success" wire:click="guardarClienteRapido">Guardar
                            Cliente</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Descuento --}}
    @if ($mostrar_modal_descuento)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-percent"></i> Aplicar Descuento
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalDescuento">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Subtotal original:</strong> Bs {{ number_format($subtotalVenta, 2) }}
                        </div>

                        <div class="form-group">
                            <label>Descontar al Total (Bs)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Bs</span>
                                </div>
                                <input type="number" wire:model.live="nuevo_total" class="form-control"
                                    step="0.01" min="0" max="{{ $subtotalVenta }}">
                            </div>
                            <small class="text-muted">Ingrese directamente el nuevo total de la venta</small>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Monto a descontar (Bs)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Bs</span>
                                </div>
                                <input type="number" wire:model.live="descuento_monto" class="form-control"
                                    step="0.01" min="0" max="{{ $subtotalVenta }}">
                            </div>
                        </div>

                        @if ($descuento_monto > 0)
                            <div class="alert alert-success mt-2">
                                <strong>Resumen del descuento:</strong><br>
                                Descuento: {{ number_format($descuento_porcentaje, 2) }}% (Bs
                                {{ number_format($descuento_monto, 2) }})<br>
                                <strong>Total a pagar: Bs {{ number_format($nuevo_total, 2) }}</strong>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalDescuento">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-info" wire:click="aplicarDescuento">
                            <i class="fas fa-check"></i> Aplicar Descuento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Editar Cliente --}}
    @if ($mostrar_modal_editar_cliente)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-edit"></i> Editar Cliente
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalEditarCliente">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="cliente_edit_nombre" class="form-control"
                                placeholder="Nombre completo">
                            @error('cliente_edit_nombre')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>NIT</label>
                            <input type="text" wire:model="cliente_edit_nit" class="form-control"
                                placeholder="NIT (opcional)">
                            @error('cliente_edit_nit')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" wire:model="cliente_edit_telefono" class="form-control"
                                placeholder="Teléfono (opcional)">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" wire:model="cliente_edit_email" class="form-control"
                                placeholder="Email (opcional)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalEditarCliente">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="actualizarCliente">
                            <i class="fas fa-save"></i> Actualizar Cliente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                                                            <strong>N° Cuenta:</strong> {{ $banca->numero_cuenta }}
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



@push('js')

    <script>
        // Verificar que SweetAlert2 está disponible

        // Manejar Enter dentro de la tabla del carrito
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                // Si es un input de cantidad en el carrito
                if (target.matches('table tbody input[type="number"]')) {
                    e.preventDefault();
                    // Disparar el evento change para que Livewire actualice
                    target.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                    // Mover al siguiente input de cantidad o al botón
                    const currentRow = target.closest('tr');
                    const nextRow = currentRow?.nextElementSibling;
                    if (nextRow) {
                        const nextInput = nextRow.querySelector('input[type="number"]');
                        if (nextInput) {
                            nextInput.focus();
                        }
                    } else {
                        // Si es la última fila, ir al botón de finalizar venta
                        const finalizarBtn = document.querySelector('button[wire\\:click="confirmarVenta"]');
                        if (finalizarBtn) finalizarBtn.focus();
                    }
                }
                // Si es un input de precio en el carrito
                if (target.matches('table tbody input[type="number"][step="0.1"]')) {
                    e.preventDefault();
                    target.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }
        });


        document.addEventListener('livewire:init', function() {

            // Variables para navegación por teclado
            let currentIndex = -1;
            let resultados = [];
            let buscador = null;
            let resultadosDiv = null;

            // Función para actualizar la lista de resultados
            function actualizarResultados() {
                resultadosDiv = document.getElementById('resultados-busqueda');
                if (resultadosDiv && resultadosDiv.style.display !== 'none') {
                    resultados = Array.from(resultadosDiv.querySelectorAll('.resultado-item'));
                    currentIndex = -1;
                }
            }

            // Función para resaltar el elemento seleccionado
            function resaltarElemento(index) {
                // Remover clase active de todos
                resultados.forEach(item => {
                    item.classList.remove('active');
                    item.style.backgroundColor = '';
                    item.style.borderLeft = '';
                });

                if (index >= 0 && index < resultados.length) {
                    resultados[index].classList.add('active');
                    resultados[index].style.backgroundColor = '#e9ecef';
                    resultados[index].style.borderLeft = '3px solid #28a745';

                    // Scroll al elemento seleccionado
                    resultados[index].scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth'
                    });
                }
            }

            // Función para seleccionar el elemento actual
            function seleccionarElementoActual() {
                if (currentIndex >= 0 && currentIndex < resultados.length) {
                    const elemento = resultados[currentIndex];
                    const productoId = elemento.getAttribute('data-id');
                    const productoNombre = elemento.getAttribute('data-nombre');


                    // Ocultar resultados
                    if (resultadosDiv) {
                        resultadosDiv.style.display = 'none';
                    }

                    // Llamar al método de Livewire
                    @this.call('seleccionarProductoYAgregar', productoId, productoNombre);

                    // Limpiar variables
                    currentIndex = -1;
                    resultados = [];

                    return true;
                }
                return false;
            }

            // Observar cambios en el DOM para detectar cuando se actualizan los resultados
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'subtree') {
                        const nuevoResultadosDiv = document.getElementById('resultados-busqueda');
                        if (nuevoResultadosDiv) {
                            if (nuevoResultadosDiv !== resultadosDiv) {
                                resultadosDiv = nuevoResultadosDiv;
                                if (resultadosDiv.style.display !== 'none') {
                                    resultados = Array.from(resultadosDiv.querySelectorAll(
                                        '.resultado-item'));
                                    currentIndex = -1;
                                }
                            } else if (resultadosDiv && resultadosDiv.style.display !== 'none') {
                                const nuevosResultados = Array.from(resultadosDiv.querySelectorAll(
                                    '.resultado-item'));
                                if (nuevosResultados.length !== resultados.length) {
                                    resultados = nuevosResultados;
                                    currentIndex = -1;
                                }
                            }
                        }
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Evento para el input de búsqueda
            buscador = document.getElementById('buscador-producto');
            if (buscador) {
                buscador.addEventListener('input', function() {
                    setTimeout(actualizarResultados, 150);
                });

                buscador.addEventListener('keydown', function(e) {
                    resultadosDiv = document.getElementById('resultados-busqueda');

                    // Verificar si hay resultados visibles
                    const hayResultadosVisibles = resultadosDiv &&
                        resultadosDiv.style.display !== 'none' &&
                        resultadosDiv.children.length > 0;

                    if (hayResultadosVisibles) {
                        // ========== NAVEGACIÓN CON RESULTADOS VISIBLES ==========
                        resultados = Array.from(resultadosDiv.querySelectorAll('.resultado-item'));

                        if (resultados.length === 0) return;

                        // Tecla flecha abajo
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            currentIndex = (currentIndex + 1) % resultados.length;
                            resaltarElemento(currentIndex);
                        }
                        // Tecla flecha arriba
                        else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            currentIndex = currentIndex <= 0 ? resultados.length - 1 : currentIndex - 1;
                            resaltarElemento(currentIndex);
                        }
                        // Tecla Enter - seleccionar producto de la lista
                        else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (currentIndex >= 0 && currentIndex < resultados.length) {
                                // Hay un elemento resaltado, seleccionarlo
                                seleccionarElementoActual();
                            } else if (resultados.length > 0) {
                                // No hay elemento resaltado, seleccionar el primero
                                currentIndex = 0;
                                seleccionarElementoActual();
                            }
                        }
                        // Tecla Escape - cerrar resultados
                        else if (e.key === 'Escape') {
                            e.preventDefault();
                            resultadosDiv.style.display = 'none';
                            currentIndex = -1;
                        }
                    } else {
                        // ========== SIN RESULTADOS VISIBLES ==========
                        // Tecla Enter - agregar producto si ya está seleccionado
                        if (e.key === 'Enter') {
                            e.preventDefault();

                            // Obtener el productoId actual del componente Livewire
                            const productoId = @this.get('productoId');

                            if (productoId && productoId !== '' && productoId !== null) {
                                @this.call('agregarAlCarrito');
                            } else {
                                // Opcional: mostrar mensaje de alerta
                                Swal.fire({
                                    title: '⚠️ Producto no seleccionado',
                                    text: 'Primero seleccione un producto de la lista usando las flechas y Enter',
                                    icon: 'warning',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        }
                    }
                });

                // Mostrar resultados cuando el input tiene foco
                buscador.addEventListener('focus', function() {
                    if (this.value.length >= 2) {
                        setTimeout(() => {
                            const resultadosDivTemp = document.getElementById(
                                'resultados-busqueda');
                            if (resultadosDivTemp && resultadosDivTemp.children.length > 0) {
                                resultadosDivTemp.style.display = 'block';
                                actualizarResultados();
                            }
                        }, 100);
                    }
                });

                // Ocultar resultados al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (buscador && !buscador.contains(e.target)) {
                        const resultadosDivTemp = document.getElementById('resultados-busqueda');
                        if (resultadosDivTemp && !resultadosDivTemp.contains(e.target)) {
                            resultadosDivTemp.style.display = 'none';
                            currentIndex = -1;
                        }
                    }
                });
            }

            // Escuchar el evento de Livewire cuando se agrega un producto para limpiar la búsqueda
            Livewire.on('producto-agregado', () => {
                const buscadorInput = document.getElementById('buscador-producto');
                if (buscadorInput) {
                    buscadorInput.value = '';
                    buscadorInput.focus();
                }
                const resultadosDivTemp = document.getElementById('resultados-busqueda');
                if (resultadosDivTemp) {
                    resultadosDivTemp.style.display = 'none';
                }
                currentIndex = -1;
                resultados = [];
            });

            // ========== EVENTOS DE VENTA ==========
            if (typeof Swal === 'undefined') {
                return;
            }

            // Evento para confirmar venta viene de ItemsVenta(confirmarVenta) y manda a (precesarVenta)
            Livewire.on('mostrar-confirmacion-venta', (data) => {

                const total = data.total;
                const tipo = data.tipo;
                const metodo_pago = data.metodo_pago;
                const cambio = data.cambio;
                const cliente_id = data.cliente_id;

                let html = `
                    <div style="text-align: left">
                        <p><strong>Total:</strong> Bs ${Number(total).toFixed(2)}</p>
                        <p><strong>Tipo:</strong> ${tipo === 'contado' ? 'Contado' : 'Crédito'}</p>
                `;
                if (tipo === 'contado') {
                    html += `<p><strong>Método de pago:</strong> ${metodo_pago}</p>`;
                    if (cambio > 0) {
                        html += `<p><strong>Cambio:</strong> Bs ${Number(cambio).toFixed(2)}</p>`;
                    }
                }
                html += `</div>`;

                Swal.fire({
                    title: '¿Confirmar venta?',
                    html: html,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, confirmar venta',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('procesarVenta', {
                            cliente_id: cliente_id
                        });
                    }
                });
            });

            // Evento cuando la venta finaliza
            // Evento cuando la venta finaliza
            Livewire.on('venta-finalizada', (data) => {
                let cambioHtml = '';
                if (data.cambio > 0) {
                    cambioHtml = `<p><strong>Cambio:</strong> Bs ${Number(data.cambio).toFixed(2)}</p>`;
                }

                Swal.fire({
                    title: '¡Venta realizada!',
                    html: `
            <p>Venta #${data.ventaId} registrada exitosamente.</p>
            ${cambioHtml}
                    <div class="mt-3">
                        <a href="${data.notaUrl}" target="_blank" class="btn btn-success">
                            <i class="fas fa-eye"></i> Ver Nota de Venta
                        </a>
                        <a href="${data.descargarUrl}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                    </div>
                `,
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Recargar la página después de cerrar el SweetAlert
                        window.location.reload();
                    }
                });
            });
        });

        // ========== NAVEGACIÓN CON ENTER EN TODO EL FORMULARIO ==========
        function setupEnterNavigation() {
            // Obtener todos los campos editables en orden
            const getFocusableElements = () => {
                const selectors = [
                    'input:not([readonly]):not([disabled])',
                    'select:not([disabled])',
                    'textarea:not([readonly]):not([disabled])',
                    'button:not([disabled]):not(.btn-primary):not(.btn-success):not(.btn-warning):not(.btn-info)'
                ];

                const allElements = document.querySelectorAll(selectors.join(','));

                // Filtrar solo los visibles y dentro del área principal
                return Array.from(allElements).filter(el => {
                    return el.offsetParent !== null &&
                        !el.closest('.modal') &&
                        el.id !== 'buscador-producto';
                });
            };

            // Función para encontrar el siguiente campo
            const getNextFocusable = (current, elements) => {
                const currentIndex = elements.indexOf(current);
                if (currentIndex !== -1 && currentIndex + 1 < elements.length) {
                    return elements[currentIndex + 1];
                }
                return null;
            };

            // Función para encontrar el campo anterior
            const getPrevFocusable = (current, elements) => {
                const currentIndex = elements.indexOf(current);
                if (currentIndex > 0) {
                    return elements[currentIndex - 1];
                }
                return null;
            };

            // Manejar Enter en todos los campos
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    const target = e.target;

                    // Excluir textareas (permitir saltos de línea con Enter)
                    if (target.tagName === 'TEXTAREA') {
                        return;
                    }

                    // Excluir el buscador de productos porque ya tiene su propia lógica
                    if (target.id === 'buscador-producto') {
                        return;
                    }

                    // Excluir botones que ya tienen su propia acción
                    if (target.tagName === 'BUTTON' || target.closest('button')) {
                        const button = target.tagName === 'BUTTON' ? target : target.closest('button');
                        // Si es el botón de confirmar venta, ejecutar click
                        if (button && (button.innerText.includes('Cobrar') || button.innerText.includes(
                                    'Finalizar') ||
                                button.innerText.includes('Registrar') || button.classList.contains('btn-success')
                            )) {
                            e.preventDefault();
                            button.click();
                            return;
                        }
                    }

                    const focusableElements = getFocusableElements();
                    const nextElement = getNextFocusable(target, focusableElements);

                    if (nextElement) {
                        e.preventDefault();
                        nextElement.focus();
                        nextElement.select?.();
                    }
                }

                // Manejar Shift+Enter para ir al campo anterior
                if (e.key === 'Enter' && e.shiftKey) {
                    const target = e.target;

                    if (target.id === 'buscador-producto') {
                        return;
                    }

                    const focusableElements = getFocusableElements();
                    const prevElement = getPrevFocusable(target, focusableElements);

                    if (prevElement) {
                        e.preventDefault();
                        prevElement.focus();
                        prevElement.select?.();
                    }
                }
            });
        }

        // Inicializar navegación cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            setupEnterNavigation();
        });

        // Re-inicializar después de que Livewire actualice el DOM
        Livewire.hook('element.updated', () => {
            setupEnterNavigation();
        });
    </script>
@endpush
