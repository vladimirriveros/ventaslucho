<div>
    {{-- SECCIÓN DE PRODUCTOS SUGERIDOS (STOCK BAJO) --}}
    @if (
        !empty($productos_a_comprar) &&
            is_array($productos_a_comprar) &&
            count($productos_a_comprar) > 0 &&
            $compra->detalles()->count() == 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Productos con stock bajo pendientes de reponer
                        </h3>
                        <div class="card-tools">
                            <button class="btn btn-sm btn-success"
                                wire:click="agregarTodosLosProductosSugeridosAlCarrito">
                                <i class="fas fa-cart-plus"></i> Agregar todos al carrito
                                ({{ is_array($productos_a_comprar) ? count($productos_a_comprar) : 0 }})
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="bg-warning">
                                    <tr>
                                        <th>#</th>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Precio Compra</th>
                                        <th>Cantidad Sugerida</th>
                                        <th>Lote</th>
                                        <th>F. Vencimiento</th>
                                        <th style="width: 120px">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($productos_a_comprar as $index => $producto)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $producto['codigo'] ?? '' }}</td>
                                            <td>{{ $producto['nombre'] ?? '' }}</td>
                                            {{-- <td>{{ $producto['marca'] ?? '' }}</td> --}}
                                            <td>{{ $producto['marca']['nombre'] ?? '' }}</td>
                                            <td class="text-right">
                                                Bs {{ number_format($producto['precio_compra'] ?? 0, 2) }}</td>
                                            <td class="text-center">
                                                <input type="number"
                                                    wire:model.live="productos_a_comprar.{{ $index }}.cantidad_sugerida"
                                                    class="form-control form-control-sm text-center" style="width: 80px"
                                                    min="1">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    wire:model.live="productos_a_comprar.{{ $index }}.codigo_lote"
                                                    class="form-control form-control-sm" style="width: 150px">
                                            </td>
                                            <td>
                                                <input type="date"
                                                    wire:model.live="productos_a_comprar.{{ $index }}.fecha_vencimiento"
                                                    class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success"
                                                        wire:click="agregarProductoSugeridoAlCarrito({{ $index }})"
                                                        title="Agregar al carrito">
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                    <button class="btn btn-danger"
                                                        wire:click="eliminarProductoSugerido({{ $index }})"
                                                        title="Eliminar de la lista">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-2 mb-0">
                            <i class="fas fa-info-circle"></i>
                            Estos productos provienen de la alerta de stock bajo. Al agregarlos, se añadirán al carrito
                            temporal.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- FORMULARIO PARA AGREGAR PRODUCTOS AL CARRITO --}}
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cart-plus"></i> Agregar producto al carrito
                    </h3>
                </div>
                <div class="card-body">
                    @if ($compra->detalles()->count() == 0)
                        <div class="form-group">
                            <label for="nombre"> Producto <b style="color: red">(*)</b></label>

                            <div class="product-search-wrapper position-relative">
                                <div class="input-group">
                                    <input type="search"
                                        wire:model.live.debounce.250ms="busqueda_producto"
                                        id="buscador-producto-compra"
                                        class="form-control"
                                        placeholder="Buscar por código, nombre o marca..."
                                        autocomplete="off"
                                        aria-label="Buscar producto para comprar">
                                    <div class="input-group-append">
                                        <span class="input-group-text" wire:loading wire:target="busqueda_producto">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </div>
                                </div>

                                @if (mb_strlen(trim($busqueda_producto)) >= 1)
                                    <div class="list-group mt-1 app-search-results product-search-results"
                                        id="resultados-busqueda-compra">
                                        @forelse ($productos_filtrados as $producto)
                                            <button type="button"
                                                class="list-group-item list-group-item-action text-left"
                                                wire:key="compra-producto-{{ $producto->id }}"
                                                wire:click="seleccionarProductoYAgregar({{ $producto->id }})">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <span>
                                                        <strong>{{ $producto->codigo }} - {{ $producto->nombre }}</strong>
                                                        <small class="d-block text-muted">
                                                            {{ $producto->marca?->nombre ?? 'Sin marca' }}
                                                        </small>
                                                    </span>
                                                    <span class="text-right ml-2">
                                                        <span class="badge {{ $producto->estado ? 'badge-success' : 'badge-secondary' }}">
                                                            {{ $producto->estado ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                        <small class="d-block mt-1">Bs {{ number_format($producto->precio_compra, 2) }}</small>
                                                    </span>
                                                </div>
                                            </button>
                                        @empty
                                            <div class="list-group-item text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                No se encontraron productos con ese código, nombre o marca.
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                            <small class="form-text text-muted">
                                Escriba el código, nombre o marca y pulse sobre el producto. Los productos inactivos se activarán al recibir la compra.
                            </small>

                            @error('productoId')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            La compra ya ha sido finalizada. No se pueden agregar más productos.
                        </div>
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Volver a compras
                        </a>
                    @endif
                </div>
            </div>

            @if (count($carrito) > 0)
                {{-- SUCURSAL FIJADA POR EL USUARIO AUTENTICADO --}}
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card card-info">
                            <div class="card-body">
                                <div class="alert alert-primary mb-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <i class="fas fa-store mr-2"></i>
                                            <strong>Sucursal de destino:</strong> {{ $sucursalNombre }}
                                        </div>
                                        <span class="badge badge-light mt-2 mt-sm-0">Asignada al usuario</span>
                                    </div>
                                    <small>La sucursal no se selecciona manualmente. Toda la compra ingresará al inventario de su sucursal asignada.</small>
                                </div>

                                <div class="alert alert-info mb-3">
                                    Total productos: <strong>{{ count($carrito) }}</strong><br>
                                    Monto total: <strong>Bs {{ number_format($totalCompra, 2) }}</strong>
                                </div>

                                <button class="btn btn-lg btn-block btn-success" wire:click="confirmarYFinalizar">
                                    <i class="fas fa-check-circle"></i>
                                    Finalizar compra y recibir productos
                                </button>
                                <small class="text-muted">Al finalizar, los lotes y movimientos se registrarán automáticamente en {{ $sucursalNombre }}.</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- CARRITO DE COMPRAS --}}
        <div class="col-md-8">
            <div class="card {{ $compra->detalles()->count() > 0 ? 'card-success' : 'card-info' }} card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        @if ($compra->detalles()->count() > 0)
                            <i class="fas fa-check-circle"></i> Productos Recibidos
                        @else
                            <i class="fas fa-shopping-cart"></i> Carrito de Compras
                        @endif
                    </h3>

                    <div class="card-tools">
                        @if ($compra->detalles()->count() == 0 && count($carrito) > 0)
                            <button type="button" class="btn btn-success btn-sm mr-2"
                                onclick="confirmarEnvioWhatsappPdf({{ $compra->id }})">
                                <i class="fab fa-whatsapp mr-2"></i> Enviar pedido Proveedor Whatsapp
                            </button>
                            <button type="button" class="btn btn-info btn-sm mr-2"
                                onclick="confirmarEnvioCorreo({{ $compra->id }})">
                                <i class="fas fa-envelope"></i> Enviar pedido Proveedor Email
                            </button>
                            <button class="btn btn-danger btn-sm" wire:click="limpiarCarrito">
                                <i class="fas fa-trash"></i> Limpiar Carrito
                            </button>

                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if ($compra->detalles()->count() > 0)
                        {{-- MOSTRAR PRODUCTOS CONFIRMADOS (SOLO LECTURA) --}}
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover table-sm">
                                <thead>
                                    <tr style="text-align: center">
                                        <th style="text-align: center">#</th>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Lote</th>
                                        <th>F. Vencimiento</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($compra->detalles as $detalle)
                                        <tr>
                                            <td style="text-align: center">{{ $loop->iteration }}</td>
                                            <td>{{ $detalle->producto->nombre }}</td>
                                            <td>{{ $detalle->producto->marca->nombre ?? 'Sin marca' }}</td>
                                            <td>{{ $detalle->lote->codigo_lote }}</td>
                                            <td class="text-center">
                                                {{ $detalle->lote->fecha_vencimiento ? date('d/m/Y', strtotime($detalle->lote->fecha_vencimiento)) : 'No especificado' }}
                                            </td>
                                            <td style="text-align: center">{{ $detalle->cantidad }}</td>
                                            <td style="text-align: center">
                                                Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                                            <td class="text-right">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-right">Total:</th>
                                        <th class="text-right">Bs {{ number_format($compra->total, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        {{-- MOSTRAR CARRITO TEMPORAL --}}
                        @if (count($carrito) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover table-sm">
                                    <thead>
                                        <tr style="text-align: center">
                                            <th style="text-align: center">#</th>
                                            <th>Producto</th>
                                            <th>Marca</th>
                                            <th>Lote</th>
                                            <th>F. Vencimiento</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                            <th style="text-align: center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($carrito as $index => $item)
                                            <tr>
                                                <td style="text-align: center">{{ $loop->iteration }}</td>
                                                <td>{{ $item['producto_nombre'] }}</td>
                                                <td>
                                                    <input type="text"
                                                        wire:change="actualizarMarcaCarrito('{{ $item['id'] }}', $event.target.value)"
                                                        {{-- value="{{ $item['marca'] }}" --}}
                                                        value="{{ $item['marca']['nombre'] ?? '' }}"
                                                        class="form-control form-control-sm" style="width: 120px;">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                        wire:model.live="carrito.{{ $index }}.codigo_lote"
                                                        class="form-control form-control-sm" style="width: 150px;">
                                                </td>
                                                <td style="text-align: center; width: 150px;">
                                                    <div class="input-group input-group-sm">
                                                        <input type="date"
                                                            wire:model.live="carrito.{{ $index }}.fecha_vencimiento"
                                                            class="form-control form-control-sm"
                                                            style="width: 130px;">
                                                        <button class="btn btn-sm btn-outline-secondary"
                                                            type="button"
                                                            wire:click="limpiarFechaVencimiento('{{ $item['id'] }}')"
                                                            title="Eliminar fecha">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td style="text-align: center; width: 100px;">
                                                    <input type="number"
                                                        wire:change="actualizarCantidadCarrito('{{ $item['id'] }}', $event.target.value)"
                                                        value="{{ $item['cantidad'] }}"
                                                        class="form-control form-control-sm text-center"
                                                        min="1" style="width: 80px;">
                                                </td>
                                                <td style="text-align: center; width: 120px;">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                            wire:change="actualizarPrecioUnitario('{{ $item['id'] }}', $event.target.value)"
                                                            value="{{ $item['precio_unitario'] }}"
                                                            class="form-control form-control-sm" step="0.01"
                                                            min="0.01" style="width: 80px;">
                                                    </div>
                                                </td>
                                                <td class="text-right">Bs {{ number_format($item['subtotal'], 2) }}</td>
                                                <td style="text-align: center">
                                                    <button class="btn btn-primary btn-sm"
                                                        wire:click="abrirModalEdicion({{ $index }})"
                                                        title="Editar producto">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="eliminarDelCarrito('{{ $item['id'] }}')"
                                                        title="Eliminar del carrito">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="8" class="text-right">Total:</th>
                                            <th class="text-right">Bs {{ number_format($totalCompra, 2) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                El carrito está vacío. Agregue productos usando el formulario de la izquierda.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA EDITAR PRODUCTO DEL CARRITO --}}
    @if ($mostrarModalEdicion && count($carrito) > 0 && isset($carrito[$itemEditarIndex]))
        <div class="modal fade show" id="modalEditarCarrito"
            style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i> Editar producto del carrito
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalEdicion">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <input type="text" class="form-control"
                                        value="{{ $carrito[$itemEditarIndex]['producto_nombre'] }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código</label>
                                    <input type="text" class="form-control"
                                        value="{{ $carrito[$itemEditarIndex]['producto_codigo'] }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <input type="text" wire:model="carrito.{{ $itemEditarIndex }}.marca.nombre"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código de Lote</label>
                                    <input type="text" wire:model="carrito.{{ $itemEditarIndex }}.codigo_lote"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de Vencimiento</label>
                                    <div class="input-group">
                                        <input type="date"
                                            wire:model="carrito.{{ $itemEditarIndex }}.fecha_vencimiento"
                                            class="form-control">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="limpiarFechaVencimiento('{{ $carrito[$itemEditarIndex]['id'] }}')">
                                                <i class="fas fa-times"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Dejar en blanco si no aplica</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" wire:model="carrito.{{ $itemEditarIndex }}.cantidad"
                                        class="form-control" min="1" step="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Bs</span>
                                        </div>
                                        <input type="number"
                                            wire:model="carrito.{{ $itemEditarIndex }}.precio_unitario"
                                            class="form-control" step="0.01" min="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Bs</span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="{{ number_format($carrito[$itemEditarIndex]['cantidad'] * $carrito[$itemEditarIndex]['precio_unitario'], 2) }}"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalEdicion">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="guardarEdicionCarrito">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        function confirmarEnvioCorreo(compraId) {
            Swal.fire({
                title: '¿Enviar pedido al proveedor?',
                text: 'Se enviará un correo con el detalle del carrito actual.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Enviando...',
                    text: 'Por favor espere.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = @json(route('compras.enviarCorreo', $compra->id));
                form.innerHTML = @json(csrf_field());
                document.body.appendChild(form);
                form.submit();
            });
        }

        function confirmarEnvioWhatsappPdf(compraId) {
            Swal.fire({
                title: '¿Preparar pedido para WhatsApp?',
                text: 'Se generará el PDF del pedido.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, preparar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Generando PDF...',
                    text: 'Por favor espere.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const url = @json(route('compras.enviarWhatsappPdf', ['compra' => '__ID__'])).replace('__ID__', compraId);
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async response => {
                    if (!response.ok) throw new Error(await response.text());
                    return response.json();
                })
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'No se pudo preparar el mensaje.');
                    window.open(data.url, '_blank');
                    Swal.fire({
                        title: 'PDF preparado',
                        html: `<p>WhatsApp se abrió en una nueva pestaña.</p><a href="${data.pdf_url}" target="_blank" class="btn btn-success">Descargar PDF</a>`,
                        icon: 'success'
                    });
                })
                .catch(error => Swal.fire('Error', error.message || 'No se pudo generar el PDF.', 'error'));
            });
        }
    </script>
@endpush
