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

                            <div class="row">
                                <div class="col-12 col-md-9 product-search-wrapper">
                                    {{-- INPUT DE BÚSQUEDA --}}
                                    <input type="text" class="form-control" id="buscador-producto"
                                        placeholder="Escriba para buscar producto..."
                                        onkeyup="filtrarProductos(this.value)"
                                        onfocus="if(this.value.length>=2) filtrarProductos(this.value)"
                                        autocomplete="off">

                                    {{-- SELECT OCULTO PARA LIVEWIRE --}}
                                    <select wire:model.live="productoId" class="form-control" required
                                        id="producto-select" style="display: none;">
                                        <option value="">Seleccione un producto</option>
                                        @foreach ($productos as $producto)
                                            <option value="{{ $producto->id }}"
                                                data-nombre="{{ $producto->codigo }} {{ $producto->nombre }} {{ $producto->marca->nombre ?? '' }}"
                                                data-codigo="{{ $producto->codigo }}"
                                                data-marca="{{ $producto->marca->nombre ?? 'Sin marca' }}">
                                                {{ $producto->codigo . ' - ' . $producto->nombre }}
                                                ({{ $producto->marca->nombre ?? 'Sin marca' }})
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- CONTENEDOR DE RESULTADOS --}}
                                    <div id="resultados-busqueda" class="list-group app-search-results product-search-results" style="display:none">
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mt-2 mt-md-0">
                                    <button class="btn btn-primary btn-block" wire:click="agregarAlCarrito"
                                        @if (!$productoId) disabled @endif>
                                        <i class="fas fa-cart-plus"></i> AGREGAR
                                    </button>
                                </div>
                            </div>

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
        // ... tu código JavaScript aquí ...
    </script>
@endpush

{{-- SCRIPTS PARA EL BUSCADOR --}}
@push('js')
    <script>
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let timeoutBusqueda = null;
        let currentIndex = -1;
        let resultados = [];
        let buscador = null;
        let resultadosDiv = null;
        let isNavigating = false;
        let navigationTimeout = null;

        // ============================================
        // FUNCIONES DE BÚSQUEDA (ORIGINALES MODIFICADAS)
        // ============================================

        function filtrarProductos(texto) {
            // Limpiar timeout anterior
            if (timeoutBusqueda) {
                clearTimeout(timeoutBusqueda);
            }

            // Si estamos navegando, no hacer nueva búsqueda
            if (isNavigating) {
                return;
            }

            // Esperar 300ms después de que el usuario deje de escribir
            timeoutBusqueda = setTimeout(() => {
                const select = document.getElementById('producto-select');
                const contenedorResultados = document.getElementById('resultados-busqueda');

                if (!select || !contenedorResultados) return;

                const opciones = Array.from(select.options).slice(1); // Ignorar primera opción vacía

                if (texto.length < 2) {
                    contenedorResultados.style.display = 'none';
                    currentIndex = -1;
                    return;
                }

                texto = texto.toLowerCase().trim();

                // Filtrar opciones que coincidan con la búsqueda
                const filtradas = opciones.filter(opt => {
                    const nombreCompleto = opt.getAttribute('data-nombre') || opt.text;
                    return nombreCompleto.toLowerCase().includes(texto);
                });

                // Mostrar resultados
                if (filtradas.length > 0) {
                    contenedorResultados.innerHTML = filtradas.map((opt, idx) => {
                        const textoOriginal = opt.text;
                        const textoResaltado = textoOriginal.replace(
                            new RegExp(texto, 'gi'),
                            match =>
                            `<strong style="background-color: #ffc107; color: #000;">${match}</strong>`
                        );

                        return `<a href="#" class="list-group-item list-group-item-action resultado-item"
                            data-id="${opt.value}"
                            data-nombre="${opt.getAttribute('data-nombre') || opt.text}"
                            data-index="${idx}"
                            style="padding: 8px 12px; border-bottom: 1px solid #eee; cursor: pointer;"
                            onclick="seleccionarYAgregarProducto('${opt.value}'); return false;">
                                ${textoResaltado}
                            </a>`;
                    }).join('');
                    contenedorResultados.style.display = 'block';
                    resultados = Array.from(contenedorResultados.querySelectorAll('.resultado-item'));
                    currentIndex = -1;
                } else {
                    contenedorResultados.innerHTML =
                        '<div class="list-group-item text-muted" style="padding: 8px 12px;">No se encontraron productos</div>';
                    contenedorResultados.style.display = 'block';
                    resultados = [];
                    currentIndex = -1;
                }
            }, 300);
        }

        // ============================================
        // NUEVA FUNCIÓN: Selecciona y agrega al carrito
        // ============================================
        // ============================================
        // NUEVA FUNCIÓN: Selecciona y agrega al carrito (para click con mouse)
        // ============================================
        function seleccionarYAgregarProducto(id) {
            const select = document.getElementById('producto-select');
            const buscador = document.getElementById('buscador-producto');
            const resultados = document.getElementById('resultados-busqueda');

            if (!select || !buscador) return;

            // Actualizar el select
            select.value = id;

            // OCULTAR RESULTADOS
            if (resultados) {
                resultados.style.display = 'none';
            }

            // Disparar evento para Livewire
            Livewire.dispatch('set-producto-id', {
                id: id
            });

            // FORZAR ACTUALIZACIÓN DIRECTA
            setTimeout(() => {
                if (typeof Livewire !== 'undefined' && Livewire.first) {
                    Livewire.first().set('productoId', id);
                }
            }, 50);

            // Limpiar el buscador
            if (buscador) {
                buscador.value = '';
            }

            // ESPERAR Y AGREGAR AL CARRITO
            setTimeout(() => {
                const componente = Livewire.first();
                if (componente && componente.get('productoId')) {
                    componente.call('agregarAlCarrito');
                } else {
                }
            }, 100);

            // Limpiar navegación
            currentIndex = -1;
            resultados = [];
            isNavigating = false;
        }

        // ============================================
        // FUNCIONES DE NAVEGACIÓN CON TECLADO
        // ============================================

        function resaltarElemento(index) {
            if (!resultadosDiv || resultados.length === 0) return;

            resultados.forEach(item => {
                item.classList.remove('active');
                item.style.backgroundColor = '';
                item.style.borderLeft = '';
            });

            if (index >= 0 && index < resultados.length) {
                resultados[index].classList.add('active');
                resultados[index].style.backgroundColor = '#e9ecef';
                resultados[index].style.borderLeft = '3px solid #28a745';

                resultados[index].scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        }

        function seleccionarElementoActual() {
            if (currentIndex >= 0 && currentIndex < resultados.length) {
                const elemento = resultados[currentIndex];
                const productoId = elemento.getAttribute('data-id');

                // Ocultar resultados
                if (resultadosDiv) {
                    resultadosDiv.style.display = 'none';
                }

                // Limpiar el buscador
                if (buscador) {
                    buscador.value = '';
                }

                // Llamar a la función que selecciona el producto
                seleccionarProducto(productoId);

                // ESPERAR UN POCO Y LUEGO AGREGAR AL CARRITO
                setTimeout(() => {
                    const componente = Livewire.first();
                    if (componente && componente.get('productoId')) {
                        componente.call('agregarAlCarrito');
                    } else {
                    }
                }, 100);

                // Limpiar variables
                currentIndex = -1;
                resultados = [];
                isNavigating = false;

                return true;
            }
            return false;
        }

        // ============================================
        // FUNCIÓN ORIGINAL (modificada para no perder el foco)
        // ============================================

        function seleccionarProducto(id) {
            const select = document.getElementById('producto-select');
            const buscador = document.getElementById('buscador-producto');
            const resultados = document.getElementById('resultados-busqueda');

            if (!select || !buscador) return;

            // Actualizar el select
            select.value = id;

            // Obtener el texto de la opción seleccionada
            const opcionSeleccionada = Array.from(select.options).find(opt => opt.value === id);

            // OCULTAR RESULTADOS
            if (resultados) {
                resultados.style.display = 'none';
            }

            // Disparar evento para Livewire
            Livewire.dispatch('set-producto-id', {
                id: id
            });

            // FORZAR ACTUALIZACIÓN DIRECTA
            setTimeout(() => {
                if (typeof Livewire !== 'undefined' && Livewire.first) {
                    Livewire.first().set('productoId', id);
                }
            }, 50);

            // Mostrar el nombre del producto seleccionado en el buscador
            if (opcionSeleccionada) {
                buscador.value = opcionSeleccionada.text;
            }

            // Limpiar navegación
            currentIndex = -1;
            resultados = [];
            isNavigating = false;
        }

        // ============================================
        // LISTENER PRINCIPAL DE LIVEWIRE
        // ============================================

        document.addEventListener('livewire:init', function() {

            // Inicializar elementos
            buscador = document.getElementById('buscador-producto');
            resultadosDiv = document.getElementById('resultados-busqueda');

            if (!buscador) {
                return;
            }

            // ========== EVENTO INPUT (búsqueda) ==========
            buscador.addEventListener('input', function(e) {
                const texto = e.target.value;
                filtrarProductos(texto);
                currentIndex = -1;
                isNavigating = false;
                if (navigationTimeout) clearTimeout(navigationTimeout);
            });

            // ========== EVENTO KEYDOWN (navegación con teclado) ==========
            buscador.addEventListener('keydown', function(e) {
                resultadosDiv = document.getElementById('resultados-busqueda');
                const hayResultados = resultadosDiv &&
                    resultadosDiv.style.display !== 'none' &&
                    resultadosDiv.children.length > 0 &&
                    resultadosDiv.querySelectorAll('.resultado-item').length > 0;

                if (hayResultados) {
                    resultados = Array.from(resultadosDiv.querySelectorAll('.resultado-item'));

                    if (resultados.length === 0) return;

                    // FLECHA ABAJO
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        isNavigating = true;
                        if (navigationTimeout) clearTimeout(navigationTimeout);

                        currentIndex = (currentIndex + 1) % resultados.length;
                        resaltarElemento(currentIndex);

                        navigationTimeout = setTimeout(() => {
                            isNavigating = false;
                        }, 1500);
                    }
                    // FLECHA ARRIBA
                    else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        isNavigating = true;
                        if (navigationTimeout) clearTimeout(navigationTimeout);

                        currentIndex = currentIndex <= 0 ? resultados.length - 1 : currentIndex - 1;
                        resaltarElemento(currentIndex);

                        navigationTimeout = setTimeout(() => {
                            isNavigating = false;
                        }, 1500);
                    }
                    // ENTER - Seleccionar producto
                    // ENTER - Agregar el primer producto directamente
                    // ENTER - Seleccionar producto (respeta navegación con flechas)
                    else if (e.key === 'Enter') {
                        e.preventDefault();

                        // Si hay un elemento seleccionado con las flechas, usar ese
                        if (currentIndex >= 0 && currentIndex < resultados.length) {
                            const producto = resultados[currentIndex];
                            const productoId = producto.getAttribute('data-id');

                            // Ocultar resultados
                            if (resultadosDiv) {
                                resultadosDiv.style.display = 'none';
                            }

                            // Limpiar el buscador
                            if (buscador) {
                                buscador.value = '';
                            }

                            // Seleccionar y agregar al carrito
                            seleccionarYAgregarProducto(productoId);
                        }
                        // Si no hay elemento seleccionado pero hay resultados, tomar el primero
                        else if (resultados.length > 0) {
                            const primerProducto = resultados[0];
                            const productoId = primerProducto.getAttribute('data-id');

                            // Ocultar resultados
                            if (resultadosDiv) {
                                resultadosDiv.style.display = 'none';
                            }

                            // Limpiar el buscador
                            if (buscador) {
                                buscador.value = '';
                            }

                            // Seleccionar y agregar al carrito
                            seleccionarYAgregarProducto(productoId);
                        }

                        // Limpiar variables
                        currentIndex = -1;
                        resultados = [];
                        isNavigating = false;
                    }
                    // ESCAPE - Cerrar resultados
                    else if (e.key === 'Escape') {
                        e.preventDefault();
                        if (resultadosDiv) {
                            resultadosDiv.style.display = 'none';
                        }
                        currentIndex = -1;
                        isNavigating = false;
                    }
                } else {
                    // Sin resultados visibles - Enter para agregar producto ya seleccionado
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const componente = Livewire.first();
                        const productoId = componente?.get('productoId');

                        if (productoId && productoId !== '' && productoId !== null && productoId !==
                            undefined) {
                            componente.call('agregarAlCarrito');
                        }
                    }
                }
            });

            // ========== MOSTRAR RESULTADOS AL FOCUS ==========
            buscador.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    setTimeout(() => {
                        const resultadosDivTemp = document.getElementById('resultados-busqueda');
                        if (resultadosDivTemp && resultadosDivTemp.children.length > 0) {
                            resultadosDivTemp.style.display = 'block';
                            resultados = Array.from(resultadosDivTemp.querySelectorAll(
                                '.resultado-item'));
                            currentIndex = -1;
                        }
                    }, 100);
                }
            });

            // ========== LIMPIAR RESULTADOS AL HACER CLICK FUERA ==========
            document.addEventListener('click', function(e) {
                if (buscador && !buscador.contains(e.target)) {
                    const resultadosDivTemp = document.getElementById('resultados-busqueda');
                    if (resultadosDivTemp && !resultadosDivTemp.contains(e.target)) {
                        resultadosDivTemp.style.display = 'none';
                        currentIndex = -1;
                        isNavigating = false;
                    }
                }
            });

            // ========== EVENTO PRODUCTO AGREGADO ==========
            // ========== EVENTO PRODUCTO AGREGADO ==========
            Livewire.on('producto-agregado', function() {
                if (buscador) {
                    buscador.value = '';
                    buscador.placeholder = 'Escriba para buscar producto...';
                    buscador.focus(); // Mantener foco para seguir agregando
                }
                const resultadosDivTemp = document.getElementById('resultados-busqueda');
                if (resultadosDivTemp) {
                    resultadosDivTemp.style.display = 'none';
                }
                currentIndex = -1;
                isNavigating = false;
                resultados = [];
            });

            // La confirmación y el resultado final se gestionan una sola vez en la vista de edición.
        });

        // ============================================
        // FUNCIONES EXISTENTES (sin modificar)
        // ============================================

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            const buscador = document.getElementById('buscador-producto');
            const resultados = document.getElementById('resultados-busqueda');

            if (!buscador || !resultados) return;

            if (!e.target.closest('#buscador-producto') && !e.target.closest('#resultados-busqueda')) {
                resultados.style.display = 'none';
                currentIndex = -1;
                isNavigating = false;
            }
        });

        // Cerrar con la tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const resultados = document.getElementById('resultados-busqueda');
                if (resultados) {
                    resultados.style.display = 'none';
                    currentIndex = -1;
                    isNavigating = false;
                }
            }
        });

        function confirmarEnvioCorreo(compraId) {
            Swal.fire({
                title: '¿Enviar pedido al proveedor?',
                text: 'Se enviará un correo con el detalle del carrito actual',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Enviando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('compras.enviarCorreo', $compra->id ?? $compraIdVariable) }}';
                    form.innerHTML = '@csrf';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function confirmarEnvioWhatsappPdf(compraId) {
            Swal.fire({
                title: '¿Enviar pedido por Whatsapp?',
                text: 'Se generará un PDF y lo prepararemos para enviar',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'green',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar PDF',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generando PDF...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    let url = "{{ route('compras.enviarWhatsappPdf', ':id') }}";
                    url = url.replace(':id', compraId);


                    fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {

                            if (!response.ok) {
                                return response.text().then(text => {
                                    throw new Error(text);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            Swal.close();

                            if (data.success) {
                                window.open(data.url, '_blank');

                                Swal.fire({
                                    title: '¡PDF Generado!',
                                    html: `
                                        <p>✅ WhatsApp se abrirá en una nueva pestaña</p>
                                        <p>📎 <strong>Para adjuntar el PDF:</strong></p>
                                        <ol style="text-align: left;">
                                            <li>Escribe el mensaje en WhatsApp</li>
                                            <li>Haz clic en el ícono 📎 (adjuntar)</li>
                                            <li>Selecciona "Documento"</li>
                                            <li>Descarga y adjunta este PDF:</li>
                                        </ol>
                                        <a href="${data.pdf_url}" target="_blank" class="btn btn-success" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0;">
                                            📥 DESCARGAR PDF
                                        </a>
                                    `,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    confirmButtonText: 'Entendido'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message || 'No se pudo preparar el mensaje',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();

                            Swal.fire({
                                title: 'Error',
                                text: error.message || 'Error al conectar con el servidor',
                                icon: 'error'
                            });
                        });
                }
            });
        }
    </script>
@endpush
