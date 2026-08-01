<div>

    <div class="row">
        {{-- COLUMNA IZQUIERDA: FORMULARIO DE COTIZACIÓN --}}
        <div class="col-md-5">
            <div class="card card-primary card-outline mb-3 app-operation-card">
                <div class="card-body py-3">
                    <label class="form-label mb-1">Sucursal de la cotización</label>
                    <div class="form-control bg-light"><i class="fas fa-store mr-2 text-primary"></i>{{ auth()->user()->sucursal->nombre }}</div>
                    <small class="text-muted">No editable. El stock se validará en esta sucursal recién al convertir la cotización en venta.</small>
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
                                    <button class="btn btn-outline-success" type="button" wire:click="abrirModalCliente"
                                        title="Agregar cliente rápido">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                @endcan
                                @if ($cliente_id)
                                    <button class="btn btn-outline-danger" type="button" wire:click="limpiarCliente"
                                        title="Limpiar cliente">
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
                            <i class="fas fa-user-check"></i>
                            <strong>{{ $cliente->nombre }}</strong>
                            @if ($cliente->nit)
                                <span class="badge badge-info">NIT: {{ $cliente->nit }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Búsqueda de productos --}}
            <div class="card card-success card-outline app-operation-card">
                <div class="card-body">
                    <div class="form-group mb-2 product-search-wrapper">
                        <label>Producto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" wire:model.live="busqueda_producto" id="buscador-producto" class="form-control"
                                placeholder="Buscar por código, nombre o marca..." autocomplete="off">
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
                                @foreach ($productos_filtrados as $producto)
                                    @php
                                        $stock = $this->obtenerStockDisponible($producto->id);
                                    @endphp
                                    <a href="#" class="list-group-item list-group-item-action resultado-item" data-id="{{ $producto->id }}"
                                        data-nombre="{{ $producto->nombre }}"
                                        wire:click.prevent="seleccionarProducto({{ $producto->id }})">
                                        <strong>{{ $producto->codigo }} - {{ $producto->nombre }}</strong>
                                        <small class="text-muted d-block">
                                            Precio: Bs {{ number_format($producto->precio_venta, 2) }}
                                            @if ($stock > 0)
                                                <span class="badge badge-success float-right">Stock:
                                                    {{ $stock }}</span>
                                            @else
                                                <span class="badge badge-warning float-right">Sin stock · se puede cotizar</span>
                                            @endif
                                        </small>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="form-group mb-0">
                        <label>Cantidad</label>
                        <input type="number" wire:model="cantidad" class="form-control" min="1" step="1" inputmode="numeric">
                        @error('cantidad')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        <small class="text-muted">Stock actual: {{ $stockProductoSeleccionado }}. La cotización permite cantidades sin existencia.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: CARRITO DE COTIZACIÓN --}}
        <div class="col-md-7">
            <div class="card {{ count($carrito) > 0 ? 'card-warning' : 'card-secondary' }} card-outline app-operation-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i> Productos Cotizados
                    </h3>
                    @if (count($carrito) > 0)
                        <div class="card-tools">
                            <button class="btn btn-danger btn-sm" wire:click="vaciarCarrito"
                                onclick="confirmarVaciarCarrito(event)">
                                <i class="fas fa-trash-alt"></i> Vaciar
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
                                        <th width="80">Cant.</th>
                                        <th width="100">Precio Unit.</th>
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
                                                @if ($item['lote_codigo'])
                                                    <br><small class="badge badge-info">Lote:
                                                        {{ $item['lote_codigo'] }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center" style="width: 100px;">
                                                @php
                                                    $stockActual = $this->obtenerStockDisponible($item['producto_id']);
                                                    $cantidadEnCarrito = $item['cantidad'];
                                                    $tieneStockSuficiente = $cantidadEnCarrito <= $stockActual;
                                                @endphp
                                                <input type="number"
                                                    value="{{ $item['cantidad'] }}"
                                                    class="form-control form-control-sm text-center"
                                                    min="1" step="1" inputmode="numeric" wire:change="actualizarCantidadCarrito({{ $index }}, $event.target.value)">
                                                <small class="{{ $tieneStockSuficiente ? 'text-success' : 'text-warning' }}">Stock actual: {{ $stockActual }}</small>
                                                @if (!$tieneStockSuficiente)
                                                    <small class="d-block text-warning"><i class="fas fa-exclamation-triangle"></i> Abastecer antes de convertir a venta.</small>
                                                @endif
                                            </td>
                                            <td class="text-right" style="width: 120px;">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Bs</span>
                                                    </div>
                                                    <input type="number" value="{{ $item['precio_unitario'] }}"
                                                        @can('ventas.modificar-precio')
                                                            wire:change="actualizarPrecioUnitario({{ $index }}, $event.target.value)"
                                                        @else
                                                            readonly
                                                        @endcan
                                                        class="form-control form-control-sm text-right" step="0.01"
                                                        min="0.01">
                                                </div>
                                            </td>
                                            <td class="text-right"><strong>Bs
                                                    {{ number_format($item['subtotal'], 2) }}</strong></td>
                                            <td class="text-center">
                                                <button class="btn btn-danger btn-sm"
                                                    wire:click="eliminarDelCarrito({{ $index }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light quote-total-footer">
                                    <tr>
                                        <th colspan="3" class="text-right">Subtotal:</th>
                                        <th class="text-right">Bs {{ number_format($subtotalCotizacion, 2) }}</th>
                                        <th></th>
                                    </tr>
                                    @if($descuentoCotizacion > 0)
                                        <tr class="text-success">
                                            <th colspan="3" class="text-right">Rebaja:</th>
                                            <th class="text-right">- Bs {{ number_format($descuentoCotizacion, 2) }}</th>
                                            <th></th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th colspan="3" class="text-right align-middle">TOTAL FINAL:</th>
                                        <th class="text-right">
                                            @can('cotizaciones.aplicar-descuento')
                                                <div class="input-group input-group-sm total-edit-control">
                                                    <div class="input-group-prepend"><span class="input-group-text">Bs</span></div>
                                                    <input type="number" value="{{ number_format($nuevoTotalCotizacion, 2, '.', '') }}"
                                                        wire:change="actualizarTotalCotizacion($event.target.value)"
                                                        class="form-control text-right font-weight-bold" min="0.01" max="{{ $subtotalCotizacion }}" step="0.01" inputmode="decimal">
                                                </div>
                                                <small class="text-muted d-block mt-1">Edite el total para aplicar una rebaja.</small>
                                            @else
                                                <strong>Bs {{ number_format($totalCotizacion, 2) }}</strong>
                                            @endcan
                                        </th>
                                        <th class="text-center">
                                            @can('cotizaciones.aplicar-descuento')
                                                @if($descuentoCotizacion > 0)
                                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="quitarDescuentoCotizacion" title="Quitar rebaja"><i class="fas fa-undo"></i></button>
                                                @endif
                                            @endcan
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> No hay productos en la cotización. Busque y agregue
                            productos.
                        </div>
                    @endif
                </div>
            </div>

            {{-- DATOS ADICIONALES DE LA COTIZACIÓN --}}
            @if (count($carrito) > 0)
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i> Datos de la Cotización
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Válida hasta</label>
                                    <input type="date" wire:model="valida_hasta" class="form-control">
                                    <small class="text-muted">Fecha hasta la cual es válida esta cotización</small>
                                </div>
                            </div>
                            <div class="col-md-12">
                                {{-- Datos adicionales de la cotización --}}
                                @if (count($carrito) > 0)
                                    <div class="card card-secondary mt-3">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-clipboard-list"></i> Datos Adicionales de la
                                                Cotización
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
                                                        <label>Forma de Pago</label>
                                                        <select wire:model="forma_pago" class="form-control">
                                                            <option value="contado">Al Contado</option>
                                                            <option value="transferencia">Transferencia</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Lugar de Entrega</label>
                                                        <input type="text" wire:model="lugar_entrega"
                                                            class="form-control"
                                                            placeholder="Dirección completa de entrega">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Plazo de Entrega (días)</label>
                                                        <input type="number" wire:model="plazo_entrega"
                                                            class="form-control" min="1" max="30">
                                                        <small class="text-muted">Tiempo estimado de entrega en días
                                                            hábiles</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Validez Económica (horas)</label>
                                                        <input type="number" wire:model="validez_economica"
                                                            class="form-control" min="1" max="72">
                                                        <small class="text-muted">Tiempo durante el cual la cotización
                                                            es válida en horas</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label>Observaciones Adicionales</label>
                                                        <textarea wire:model="observaciones_adicionales" class="form-control" rows="2"
                                                            placeholder="Notas adicionales sobre la cotización..."></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <button class="btn btn-lg btn-block btn-warning" wire:click="confirmarCotizacion">
                            <i class="fas fa-save"></i>
                            {{ $cotizacion && $cotizacion->estado == 'activa' ? 'Actualizar Cotización' : 'Guardar Cotización' }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL: Cliente Rápido --}}
    @if ($mostrar_modal_cliente)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus"></i> Agregar Cliente Rápido
                        </h5>
                        <button type="button" class="close" wire:click="cerrarModalCliente">
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
                        <button type="button" class="btn btn-warning" wire:click="guardarClienteRapido">Guardar
                            Cliente</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        // Verificar que SweetAlert2 está disponible

        function confirmarVaciarCarrito(event) {
            event.preventDefault();
            Swal.fire({
                title: '¿Vaciar carrito?',
                text: 'Se eliminarán todos los productos de la cotización',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('vaciarCarrito');
                }
            });
        }

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
                    resultados[index].style.borderLeft = '3px solid #ffc107';

                    // Scroll al elemento seleccionado
                    resultados[index].scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth'
                    });
                }
            }

            // Función para seleccionar el elemento actual y agregarlo al carrito
            // Función para seleccionar el elemento actual (SOLO SELECCIONAR, NO AGREGAR)
            function seleccionarElementoActual() {
                if (currentIndex >= 0 && currentIndex < resultados.length) {
                    const elemento = resultados[currentIndex];
                    const productoId = elemento.getAttribute('data-id');
                    const productoNombre = elemento.getAttribute('data-nombre');


                    // Ocultar resultados
                    if (resultadosDiv) {
                        resultadosDiv.style.display = 'none';
                    }

                    // Llamar al método de Livewire para seleccionar el producto (SOLO SELECCIONAR)
                    @this.call('seleccionarProducto', productoId, productoNombre);

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

            // ========== EVENTOS DE COTIZACIÓN ==========
            if (typeof Swal === 'undefined') {
                return;
            }

            // Evento para confirmar cotización
            Livewire.on('mostrar-confirmacion-cotizacion', (data) => {

                const total = data.total;
                const cliente_id = data.cliente_id;
                const valida_hasta = data.valida_hasta;

                let html = `
                    <div style="text-align: left">
                        <p><strong>Total:</strong> Bs ${Number(total).toFixed(2)}</p>
                        <p><strong>Cliente:</strong> ${cliente_id ? 'Seleccionado' : 'Sin cliente'}</p>
                        <p><strong>Válida hasta:</strong> ${valida_hasta}</p>
                    </div>
                `;

                Swal.fire({
                    title: '¿Confirmar cotización?',
                    html: html,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, guardar cotización',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('guardarCotizacion');
                    }
                });
            });

            // Evento cuando la cotización se guarda
            Livewire.on('cotizacion-guardada', (data) => {

                Swal.fire({
                    title: '¡Cotización guardada!',
                    html: `
                        <p>Cotización #${data.cotizacionId} guardada exitosamente.</p>
                        <div class="mt-3">
                            <a href="${data.imprimirUrl}" target="_blank" class="btn btn-warning">
                                <i class="fas fa-print"></i> Imprimir Cotización
                            </a>
                            <button id="btnConvertirVenta" class="btn btn-success ml-2">
                                <i class="fas fa-exchange-alt"></i> Convertir a Venta
                            </button>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Cerrar',
                    didOpen: () => {
                        const btnConvertir = document.getElementById('btnConvertirVenta');
                        if (btnConvertir) {
                            btnConvertir.addEventListener('click', () => {
                                window.location.href =
                                    `/admin/ventas/create?cotizacion_id=${data.cotizacionId}`;
                            });
                        }
                    }
                });
            });

            Livewire.on('cotizacion-guardada', (data) => {

                let botonesHtml = `
                    <div class="mt-3">
                        <a href="${data.imprimirUrl}" target="_blank" class="btn btn-warning">
                            <i class="fas fa-print"></i> Imprimir Cotización
                        </a>
                `;

                // 🔄 CAMBIO: Solo mostrar botón de convertir si NO tiene productos sin stock
                if (!data.tieneSinStock) {
                    botonesHtml += `
                    <button id="btnConvertirVenta" class="btn btn-success ml-2">
                        <i class="fas fa-exchange-alt"></i> Convertir a Venta
                    </button>
                `;
                        } else {
                            botonesHtml += `
                    <button class="btn btn-secondary ml-2" disabled title="No se puede convertir porque hay productos sin stock">
                        <i class="fas fa-exchange-alt"></i> Convertir a Venta (Stock insuficiente)
                    </button>
                    `;
                }

                botonesHtml += `</div>`;

                Swal.fire({
                    title: '¡Cotización guardada!',
                    html: `<p>Cotización #${data.cotizacionId} guardada exitosamente.</p>${botonesHtml}`,
                    icon: 'success',
                    confirmButtonText: 'Cerrar',
                    didOpen: () => {
                        const btnConvertir = document.getElementById('btnConvertirVenta');
                        if (btnConvertir) {
                            btnConvertir.addEventListener('click', () => {
                                // 🔄 CAMBIO: Antes de redirigir, verificar stock nuevamente
                                window.location.href =
                                    `/admin/ventas/create?cotizacion_id=${data.cotizacionId}`;
                            });
                        }
                    }
                });
            });
        });
    </script>
@endpush
