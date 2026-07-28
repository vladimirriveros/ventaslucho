<div>
    <div class="row">
        {{-- FORMULARIO AGREGAR PRODUCTO --}}
        <div class="col-md-4">
            <div class="card card-primary card-outline app-operation-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-minus-circle"></i> Agregar producto a la salida
                    </h3>
                </div>
                <div class="card-body">
                    @if ($salida->estado == 'Pendiente')
                        {{-- SELECCIONAR PRODUCTO CON BUSCADOR MEJORADO --}}
                        <div class="form-group">
                            <label for="nombre"> Producto <b style="color: red">(*)</b></label>

                            <div class="row">
                                <div class="col-12 col-md-9 product-search-wrapper">
                                    {{-- INPUT DE BÚSQUEDA --}}
                                    <input type="text" class="form-control" id="buscador-producto"
                                        placeholder="Escriba para buscar producto..."
                                        onkeyup="filtrarProductos(this.value)"
                                        onfocus="if(this.value.length>=2) filtrarProductos(this.value)"
                                        autocomplete="off"
                                        value="{{ $productoSeleccionadoNombre ?? '' }}">

                                    {{-- SELECT OCULTO PARA LIVEWIRE --}}
                                    <select wire:model.live="productoId" class="form-control" required
                                        id="producto-select" style="display: none;">
                                        <option value="">Seleccione un producto</option>
                                        @foreach ($productos as $producto)
                                            <option value="{{ $producto->id }}"
                                                data-nombre="{{ $producto->codigo }} {{ $producto->nombre }} {{ $producto->marca->nombre ?? '' }}"
                                                data-codigo="{{ $producto->codigo }}"
                                                data-marca="{{ $producto->marca ?? 'Sin marca' }}">
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
                                    <button class="btn btn-primary btn-block" wire:click="agregarItems"
                                        @if (
                                            !$productoId ||
                                                ($salida->motivo != 'Venta' && !$loteSeleccionado) ||
                                                ($salida->motivo == 'Venta' && empty($lotesDisponibles))) disabled @endif>
                                        <i class="fas fa-cart-plus"></i> AGREGAR
                                    </button>
                                </div>
                            </div>

                            @error('productoId')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- SELECCIONAR LOTE (si hay producto seleccionado) --}}
                        {{-- SELECCIONAR LOTE (solo mostrar si NO es tienda o si hay más de un lote) --}}
                        {{-- SELECCIONAR LOTE (solo si NO es Venta) --}}
                        @if ($salida->motivo != 'Venta')
                            @if ($productoId)
                                <div class="form-group">
                                    <label>Lote disponible <b style="color:red">(*)</b></label>
                                    <select wire:model.live="loteSeleccionado" class="form-control">
                                        <option value="">Seleccione lote</option>
                                        @foreach ($lotesDisponibles as $lote)
                                            <option value="{{ $lote->lote_id }}"
                                                {{ $lote->lote_ya_en_salida ? 'disabled' : '' }}>
                                                {{ $lote->codigo_lote }} -
                                                Vence:
                                                {{ $lote->fecha_vencimiento ? date('d/m/Y', strtotime($lote->fecha_vencimiento)) : 'N/A' }}
                                                -
                                                <strong>Disp: {{ $lote->stock_disponible }}</strong>
                                                @if ($lote->lote_ya_en_salida)
                                                    (Ya en salida)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('loteSeleccionado')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif
                        @else
                            {{-- Para Venta, mostrar información de lotes disponibles --}}
                            @if ($productoId && count($lotesDisponibles) > 0)
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Modo FIFO activado:</strong> Los productos se tomarán automáticamente de los
                                    lotes más antiguos.
                                    <br>
                                    <small>Lotes disponibles:
                                        @foreach ($lotesDisponibles as $lote)
                                            <span class="badge badge-info">{{ $lote->codigo_lote }}
                                                ({{ $lote->stock_disponible }} unid.)
                                            </span>
                                        @endforeach
                                    </small>
                                </div>
                            @endif
                        @endif

                        {{-- CANTIDAD --}}
                        <div class="form-group">
                            <label>Cantidad <b style="color:red">(*)</b></label>
                            <input type="number" wire:model="cantidad" class="form-control" min="1"
                                max="{{ $loteSeleccionado ? collect($lotesDisponibles)->firstWhere('lote_id', $loteSeleccionado)?->stock_disponible ?? 999 : 999 }}"
                                step="1">
                            @error('cantidad')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            {{-- Mostrar el máximo disponible cuando hay lote seleccionado --}}
                            @if ($loteSeleccionado && $lotesDisponibles->isNotEmpty())
                                @php
                                    $loteActual = collect($lotesDisponibles)->firstWhere('lote_id', $loteSeleccionado);
                                @endphp
                                @if ($loteActual)
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Stock disponible en este lote:
                                        <strong>{{ $loteActual->stock_disponible }}</strong> unidades
                                    </small>
                                    @if ($cantidad > $loteActual->stock_disponible)
                                        <div class="text-danger small mt-1">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            La cantidad ingresada ({{ $cantidad }}) excede el stock disponible
                                            ({{ $loteActual->stock_disponible }})
                                        </div>
                                    @endif
                                @endif
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Esta salida no está pendiente. Estado: {{ $salida->estado }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- LISTA DE PRODUCTOS DE LA SALIDA --}}
        <div class="col-md-8">
            <div class="card {{ $salida->detalles->count() > 0 ? 'card-success' : 'card-info' }} card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        @if ($salida->detalles->count() > 0)
                            <i class="fas fa-check-circle"></i> Productos en salida
                        @else
                            <i class="fas fa-shopping-cart"></i> Carrito de salida
                        @endif
                    </h3>
                    {{-- BOTÓN VACIAR CARRITO --}}
                    @if ($salida->detalles->count() > 0 && $salida->estado == 'Pendiente')
                        <div class="card-tools">
                            <button class="btn btn-danger btn-sm" onclick="confirmarVaciarCarrito()">
                                <i class="fas fa-trash-alt"></i> Vaciar Carrito
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if ($salida->detalles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="bg-primary text-white">
                                    <tr class="text-center">
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Lote</th>
                                        <th>F. Vencimiento</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($salida->detalles as $detalle)
                                        @php
                                            $inventarioLote = App\Models\InventarioSucuralLote::where(
                                                'lote_id',
                                                $detalle->lote_id,
                                            )
                                                ->where('sucursal_id', $salida->sucursal_id)
                                                ->first();
                                            $stockLote = $inventarioLote ? $inventarioLote->cantidad_en_sucursal : 0;
                                            $maxPermitido = $stockLote;
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $detalle->producto->nombre }}</td>
                                            <td>
                                                {{ $detalle->lote->codigo_lote }}
                                                <small class="badge badge-info">Lote ID:
                                                    {{ $detalle->lote_id }}</small>
                                            </td>
                                            <td class="text-center">
                                                {{ $detalle->lote->fecha_vencimiento ? date('d/m/Y', strtotime($detalle->lote->fecha_vencimiento)) : 'N/A' }}
                                            </td>
                                            <td class="text-center" style="width: 100px;">
                                                @if ($salida->estado == 'Pendiente')
                                                    <input type="number" value="{{ $detalle->cantidad }}"
                                                        wire:change="actualizarCantidadDetalle({{ $detalle->id }}, $event.target.value)"
                                                        class="form-control form-control-sm text-center" min="1"
                                                        max="{{ $maxPermitido }}" style="width: 80px;">
                                                @else
                                                    {{ $detalle->cantidad }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                Bs {{ number_format($detalle->precio_unitario, 2) }}
                                                @if ($salida->motivo == 'Venta')
                                                    <small class="badge badge-success">V</small>
                                                @else
                                                    <small class="badge badge-info">C</small>
                                                @endif
                                            </td>
                                            <td class="text-center">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                                            <td class="text-center">
                                                @if ($salida->estado == 'Pendiente')
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="borrarItem({{ $detalle->id }})">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="6" class="text-right">TOTAL:</th>
                                        <th class="text-center">Bs {{ number_format($salida->total, 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No hay productos en esta salida. Use el formulario de la izquierda para agregar.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Después de seleccionar producto, mostrar warning si ya está en salida --}}
    @if ($productoId && $salida->detalles->contains('producto_id', $productoId))
        <div class="alert alert-warning mt-2 p-2">
            <small>
                <i class="fas fa-exclamation-triangle"></i>
                Este producto ya está en la salida. Use la tabla para ajustar cantidades.
            </small>
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
        let productoSeleccionadoNombre = '';

        // ============================================
        // FUNCIÓN DE BÚSQUEDA (MODIFICADA)
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
                            match => `<strong style="background-color: #ffc107; color: #000;">${match}</strong>`
                        );

                        return `<a href="#" class="list-group-item list-group-item-action resultado-item"
                               data-id="${opt.value}"
                               data-nombre="${opt.getAttribute('data-nombre') || opt.text}"
                               data-index="${idx}"
                               style="padding: 8px 12px; border-bottom: 1px solid #eee; cursor: pointer;"
                               onclick="seleccionarProducto('${opt.value}', '${opt.text.replace(/'/g, "\\'")}'); return false;">
                                ${textoResaltado}
                            </a>`;
                    }).join('');
                    contenedorResultados.style.display = 'block';
                    resultados = Array.from(contenedorResultados.querySelectorAll('.resultado-item'));
                    currentIndex = -1;
                } else {
                    contenedorResultados.innerHTML = '<div class="list-group-item text-muted" style="padding: 8px 12px;">No se encontraron productos</div>';
                    contenedorResultados.style.display = 'block';
                    resultados = [];
                    currentIndex = -1;
                }
            }, 300);
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
                const productoNombre = elemento.getAttribute('data-nombre') || elemento.textContent;

                // Ocultar resultados
                if (resultadosDiv) {
                    resultadosDiv.style.display = 'none';
                }

                // Llamar a la función original seleccionarProducto
                seleccionarProducto(productoId, productoNombre);

                // Limpiar variables
                currentIndex = -1;
                resultados = [];
                isNavigating = false;

                return true;
            }
            return false;
        }

        // ============================================
        // FUNCIÓN ORIGINAL (modificada)
        // ============================================

        function seleccionarProducto(id, nombre) {
            const select = document.getElementById('producto-select');
            const buscador = document.getElementById('buscador-producto');
            const resultados = document.getElementById('resultados-busqueda');

            if (!select || !buscador) return;

            // Actualizar el select
            select.value = id;

            // Guardar el nombre del producto seleccionado
            productoSeleccionadoNombre = nombre;

            // Mostrar el nombre en el buscador
            buscador.value = nombre;

            // OCULTAR RESULTADOS
            if (resultados) {
                resultados.style.display = 'none';
            }

            // Disparar evento para Livewire
            Livewire.dispatch('set-producto-id', {
                id: id
            });

            // Forzar actualización directa
            setTimeout(() => {
                if (typeof Livewire !== 'undefined' && Livewire.first()) {
                    Livewire.first().set('productoId', id);
                }
            }, 50);

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
                    else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (currentIndex >= 0 && currentIndex < resultados.length) {
                            seleccionarElementoActual();
                        } else if (resultados.length > 0) {
                            currentIndex = 0;
                            seleccionarElementoActual();
                        }
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

                        if (productoId && productoId !== '' && productoId !== null && productoId !== undefined) {
                            componente.call('agregarItems');
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
                            resultados = Array.from(resultadosDivTemp.querySelectorAll('.resultado-item'));
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
            Livewire.on('producto-agregado', function() {
                const buscadorInput = document.getElementById('buscador-producto');
                const productoSelect = document.getElementById('producto-select');

                if (buscadorInput) {
                    buscadorInput.value = '';
                    buscadorInput.placeholder = 'Escriba para buscar producto...';
                    buscadorInput.focus();
                }

                if (productoSelect) {
                    productoSelect.value = '';
                }

                const resultadosDivTemp = document.getElementById('resultados-busqueda');
                if (resultadosDivTemp) {
                    resultadosDivTemp.style.display = 'none';
                }

                productoSeleccionadoNombre = '';
                currentIndex = -1;
                isNavigating = false;
                resultados = [];

                if (typeof Livewire !== 'undefined' && Livewire.first()) {
                    Livewire.first().set('productoId', null);
                }
            });

            // Evento para limpiar buscador
            Livewire.on('limpiar-buscador', function() {
                const buscadorInput = document.getElementById('buscador-producto');
                const productoSelect = document.getElementById('producto-select');

                if (buscadorInput) {
                    buscadorInput.value = '';
                }

                if (productoSelect) {
                    productoSelect.value = '';
                }

                productoSeleccionadoNombre = '';
                currentIndex = -1;
                isNavigating = false;
            });

            // Evento para producto eliminado
            Livewire.on('producto-eliminado', function() {
                const buscadorInput = document.getElementById('buscador-producto');
                if (buscadorInput) {
                    buscadorInput.focus();
                }
            });
        });

        // ============================================
        // FUNCIÓN PARA VACIAR CARRITO (original)
        // ============================================

        function confirmarVaciarCarrito() {
            Swal.fire({
                title: '¿Vaciar todo el carrito?',
                text: 'Esta acción eliminará todos los productos de la salida. ¿Estás seguro?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, vaciar todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (typeof Livewire !== 'undefined' && Livewire.first()) {
                        Livewire.first().call('vaciarCarrito');
                    }
                }
            });
        }

        // ============================================
        // FUNCIONES ADICIONALES (originales)
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

        // Validación de cantidad en tiempo real
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[wire\\:model="cantidad"]')) {
                let cantidad = parseInt(e.target.value) || 0;
                let maxAttr = e.target.getAttribute('max');

                if (maxAttr && cantidad > parseInt(maxAttr)) {
                    e.target.classList.add('is-invalid');

                    let parent = e.target.closest('.form-group');
                    let existingMsg = parent.querySelector('.text-danger.inline-feedback');

                    if (!existingMsg) {
                        let msg = document.createElement('small');
                        msg.className = 'text-danger d-block inline-feedback';
                        msg.innerHTML =
                            '<i class="fas fa-exclamation-circle"></i> La cantidad máxima permitida es ' + maxAttr;
                        parent.appendChild(msg);
                    }
                } else {
                    e.target.classList.remove('is-invalid');
                    let parent = e.target.closest('.form-group');
                    let existingMsg = parent.querySelector('.text-danger.inline-feedback');
                    if (existingMsg) {
                        existingMsg.remove();
                    }
                }
            }
        });
    </script>
@endpush
