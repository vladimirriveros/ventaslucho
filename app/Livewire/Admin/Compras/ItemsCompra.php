<?php

namespace App\Livewire\Admin\Compras;

use App\Models\Compra;
use App\Models\HistorialPrecio;
use App\Models\InventarioSucuralLote;
use App\Models\Producto;
use App\Models\Lote;
use App\Models\Sucursal;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ItemsCompra extends Component
{
    public Compra $compra;
    public $productoId;
    public $cantidad = 1;
    public $precioCompra;
    public $fechaVencimiento;
    public $codigoLote;
    public $productos;
    public $totalCompra = 0;

    // La sucursal se deriva del usuario autenticado; nunca del formulario.
    public $sucursal_id;
    public $sucursalNombre;

    // Carrito temporal
    public $carrito = [];

    // Productos sugeridos
    public $sugeridos = [];
    public $productos_a_comprar = [];
    public $productos_sugeridos = '';

    // Modal de edición
    public $mostrarModalEdicion = false;
    public $itemEditarIndex = null;

    protected $listeners = [
        'procesar-finalizacion' => 'procesarFinalizacion',
        'confirmarYFinalizar' => 'confirmarYFinalizar',
    ];
    /**
     * Reglas de validación para agregar productos al carrito
     */
    protected function rulesParaAgregar()
    {
        return [
            'productoId' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1|max:1000000',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    protected $messages = [
        'productoId.required' => 'Debe seleccionar un producto.',
        'productoId.exists' => 'El producto seleccionado ya no está disponible.',
        'cantidad.required' => 'La cantidad es obligatoria.',
        'cantidad.min' => 'La cantidad debe ser al menos 1.',
    ];



    public function mount(Compra $compra, $productos_sugeridos = '')
    {
        $this->productos_a_comprar = [];
        $this->compra = $compra;
        $this->productos = Producto::with(['categoria', 'marca'])->where('estado', true)->orderBy('nombre')->get();
        $this->productos_sugeridos = $productos_sugeridos;

        abort_unless(
            $this->sincronizarSucursalOperativa(),
            403,
            'Su usuario debe tener una sucursal activa asignada y la compra debe pertenecer a esa sucursal.'
        );

        // 🔴 VERIFICAR SI LA COMPRA YA ESTÁ RECIBIDA
        if ($this->compra->estado == 'Recibido') {
            // Si ya está recibida, no cargar carrito
            $this->carrito = [];
            $this->totalCompra = $this->compra->detalles->sum('subtotal');
        } elseif ($this->compra->detalles()->count() > 0) {
            // Si tiene detalles pero no está recibida (estado intermedio)
            $this->carrito = [];
            $this->totalCompra = $this->compra->detalles->sum('subtotal');
        } else {
            // Compra nueva, cargar carrito de sesión
            $this->cargarCarritoDesdeSesion();
            $this->cargarProductosSugeridosDesdeUrl();
        }
    }

    /**
     * Cargar carrito desde la sesión
     */
    public function cargarCarritoDesdeSesion()
    {
        $carritoKey = 'carrito_compra_' . $this->compra->id;
        $this->carrito = session($carritoKey, []);
        $this->calcularTotal();
    }

    /**
     * Guardar carrito en sesión
     */
    public function guardarCarritoEnSesion()
    {
        $carritoKey = 'carrito_compra_' . $this->compra->id;
        session([$carritoKey => $this->carrito]);
    }

    /**
     * Limpiar carrito de sesión
     */
    public function limpiarCarritoSesion()
    {
        $carritoKey = 'carrito_compra_' . $this->compra->id;
        session()->forget($carritoKey);
        $this->carrito = [];
        $this->calcularTotal();
    }

    /**
     * Calcular total del carrito
     */
    public function calcularTotal()
    {
        $this->totalCompra = collect($this->carrito)->sum('subtotal');
    }

    /**
     * Cargar productos sugeridos desde la URL
     */
    public function cargarProductosSugeridosDesdeUrl()
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        $ids_productos = '';

        if (!empty($this->productos_sugeridos)) {
            $ids_productos = $this->productos_sugeridos;
        } elseif (request()->has('productos')) {
            $ids_productos = request('productos', '');
        }

        if (!empty($ids_productos)) {
            $this->sugeridos = explode(',', $ids_productos);
            $this->productos_a_comprar = []; // Reinicializar como array

            foreach ($this->sugeridos as $productoId) {
                if (empty($productoId)) continue;

                // Cargar producto con categoría
                $producto = Producto::with(['categoria', 'marca'])->find($productoId);
                if ($producto) {
                    $producto_existe_carrito = collect($this->carrito)->contains('producto_id', $producto->id);
                    $producto_existe_db = $this->compra->detalles()
                        ->where('producto_id', $producto->id)
                        ->exists();

                    if (!$producto_existe_carrito && !$producto_existe_db) {
                        $this->productos_a_comprar[] = [
                            'id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'codigo' => $producto->codigo,
                            'marca' => $producto->marca ?? 'Sin marca',
                            'precio_compra' => $producto->precio_compra ?? 0,
                            'codigo_lote' => $this->generateCodigoLote(
                                $producto->nombre,
                                $producto->id,
                                $producto->categoria
                            ),
                            'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
                            'cantidad_sugerida' => $this->calcularCantidadSugerida($producto)
                        ];
                    }
                }
            }

            if (count($this->productos_a_comprar) > 0) {
                $this->dispatch('mostrar-alerta', [
                    'mensaje' => count($this->productos_a_comprar) . ' productos con stock bajo están listos para agregar al carrito.',
                    'icono' => 'info'
                ]);
            }
        } else {
            $this->productos_a_comprar = []; // Asegurar que sea array aunque no haya productos
        }
    }

    /**
     * Calcular cantidad sugerida basada en stock mínimo
     */
    private function calcularCantidadSugerida($producto)
    {
        if (isset($producto->stock_minimo) && $producto->stock_minimo > 0) {
            return $producto->stock_minimo * 2;
        }
        return 10;
    }

    /**
     * Generar código de lote automático
     */
    private function generateCodigoLote($productoNombre, $productoId, $categoriaObjeto)
    {
        $catNombre = $categoriaObjeto->nombre ?? 'GEN';
        $cat = strtoupper(substr($catNombre, 0, 2));
        $prod = strtoupper(substr($productoNombre, 0, 2));
        $fecha = now()->format('ymd');
        $compraId = str_pad($this->compra->id, 3, '0', STR_PAD_LEFT);
        $aleatorio = rand(10, 99);

        return "{$cat}{$prod}-{$fecha}-{$compraId}-{$aleatorio}";
    }

    /**
     * Verificar si un lote ya existe para un producto
     */
    private function verificarLoteExistente($productoId, $codigoLote, $proveedorId, $excluirCarritoId = null)
    {
        // Verificar en el carrito actual primero
        foreach ($this->carrito as $item) {
            if ($excluirCarritoId && isset($item['id']) && $item['id'] === $excluirCarritoId) {
                continue;
            }

            if ($item['producto_id'] == $productoId && $item['codigo_lote'] === $codigoLote) {
                return 'mismo_carrito';
            }
        }

        // Verificar en la base de datos
        $loteExistente = Lote::where('producto_id', $productoId)
            ->where('codigo_lote', $codigoLote)
            ->first();

        if ($loteExistente) {
            if ($loteExistente->proveedor_id == $proveedorId) {
                return 'mismo_proveedor';
            } else {
                return 'otro_proveedor';
            }
        }

        return false;
    }

    public function agregarAlCarrito()
    {
        if (!Auth::user()->can('compras.store')) {
            $this->dispatch('mostrar-alerta', mensaje: 'No tiene permiso para modificar esta compra.', icono: 'error');
            return;
        }
        if (!$this->sincronizarSucursalOperativa()) {
            $this->dispatch('mostrar-alerta', mensaje: 'La compra no pertenece a la sucursal activa asignada a su usuario.', icono: 'error');
            return;
        }

        $this->validate($this->rulesParaAgregar());

        // Verificación doble: estado y detalles persistidos.
        if ($this->compra->estado == 'Recibido' || $this->compra->detalles()->count() > 0) {
            $this->dispatch('mostrar-alerta',
                mensaje: 'Esta compra ya fue finalizada. No se pueden agregar más productos.',
                icono: 'warning'
            );
            return;
        }

        if (empty($this->productoId)) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'Debe seleccionar un producto',
                'icono' => 'warning'
            ]);
            return;
        }

        $producto = Producto::with(['categoria', 'marca'])->find($this->productoId);

        if (!$producto) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'Producto no encontrado',
                'icono' => 'error'
            ]);
            return;
        }

        // 🔴 VALIDACIÓN DE STOCK MÁXIMO
        $stockActual = $this->obtenerStockActualProducto($producto->id);
        $cantidadActualEnCarrito = $this->obtenerCantidadEnCarrito($producto->id);
        $cantidadTotal = $stockActual + $cantidadActualEnCarrito + $this->cantidad;

        if ($producto->stock_maximo > 0 && $cantidadTotal > $producto->stock_maximo) {
            $disponible = $producto->stock_maximo - ($stockActual + $cantidadActualEnCarrito);
            $this->dispatch('mostrar-alerta-stock', [
                'mensaje' => "⚠️ No se puede agregar {$this->cantidad} unidades de '{$producto->nombre}'.<br><br>" .
                            "📦 Stock actual: {$stockActual} unidades<br>" .
                            "🛒 En carrito: {$cantidadActualEnCarrito} unidades<br>" .
                            "📊 Stock máximo: {$producto->stock_maximo} unidades<br>" .
                            "✅ Solo puedes agregar {$disponible} unidades más.",
                'icono' => 'warning'
            ]);
            return;
        }

        // GENERAR LOTE ÚNICO
        $codigoLote = $this->generarLoteUnico($producto);

        // 🔴 VALIDACIÓN: Verificar si el lote ya existe en BD para OTRO proveedor
        $loteEnBD = Lote::where('codigo_lote', $codigoLote)
            ->where('proveedor_id', '!=', $this->compra->proveedor_id)
            ->first();

        if ($loteEnBD) {
            $proveedorLote = $loteEnBD->proveedor;
            $mensaje = "El código de lote '{$codigoLote}' ya existe para OTRO proveedor: '{$proveedorLote->nombre}'. ";
            $mensaje .= "No puede usar el mismo código de lote para diferentes proveedores.";

            $this->dispatch('mostrar-alerta', [
                'mensaje' => $mensaje,
                'icono' => 'error'
            ]);
            return;
        }

        // 🔴 VALIDACIÓN: Buscar si el mismo producto con el mismo lote YA ESTÁ EN EL CARRITO
        $itemExistente = null;
        $itemIndex = null;

        foreach ($this->carrito as $index => $item) {
            if ($item['producto_id'] == $producto->id && $item['codigo_lote'] === $codigoLote) {
                $itemExistente = $item;
                $itemIndex = $index;
                break;
            }
        }

        if ($itemExistente) {
            // Aumentar la cantidad del item existente - VOLVER A VALIDAR STOCK MÁXIMO
            $nuevaCantidad = $itemExistente['cantidad'] + $this->cantidad;

            // Validar nuevamente con la nueva cantidad total
            $stockActual = $this->obtenerStockActualProducto($producto->id);
            $otrasCantidadesEnCarrito = $this->obtenerCantidadEnCarrito($producto->id) - $itemExistente['cantidad'];
            $cantidadTotal = $stockActual + $otrasCantidadesEnCarrito + $nuevaCantidad;

            if ($producto->stock_maximo > 0 && $cantidadTotal > $producto->stock_maximo) {
                $disponible = $producto->stock_maximo - ($stockActual + $otrasCantidadesEnCarrito);
                $this->dispatch('mostrar-alerta-stock', [
                    'mensaje' => "⚠️ No se puede aumentar la cantidad.<br><br>" .
                                "📦 Stock actual: {$stockActual} unidades<br>" .
                                "🛒 En carrito (sin este producto): {$otrasCantidadesEnCarrito} unidades<br>" .
                                "📊 Stock máximo: {$producto->stock_maximo} unidades<br>" .
                                "✅ Solo puedes tener {$disponible} unidades en total de este producto.",
                    'icono' => 'warning'
                ]);
                return;
            }

            $this->carrito[$itemIndex]['cantidad'] = $nuevaCantidad;
            $this->carrito[$itemIndex]['subtotal'] = $nuevaCantidad * $itemExistente['precio_unitario'];

            $this->guardarCarritoEnSesion();
            $this->calcularTotal();

            $this->dispatch('mostrar-alerta', [
                'mensaje' => "Producto ya existente. Cantidad actualizada a {$nuevaCantidad}",
                'icono' => 'info'
            ]);

            $this->reset('productoId');
            $this->cantidad = 1;
            $this->dispatch('producto-agregado');
            return;
        }

        // Si no existe, agregar nuevo item al carrito con el precio actual del producto
        $this->carrito[] = [
            'id' => uniqid(),
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_codigo' => $producto->codigo,
            'marca' => $producto->marca ?? 'Sin marca',
            'cantidad' => $this->cantidad,
            'precio_unitario' => (float)($producto->precio_compra ?? 0),
            'subtotal' => (float)($this->cantidad * ($producto->precio_compra ?? 0)),
            'codigo_lote' => $codigoLote,
            'fecha_vencimiento' => null,
        ];

        $this->guardarCarritoEnSesion();
        $this->calcularTotal();

        // Resetear
        $this->reset('productoId');
        $this->cantidad = 1;

        $this->dispatch('producto-agregado');
    }

    /**
     * Agregar producto al carrito directamente (sin validación de lote)
     */
    private function agregarAlCarritoDirecto()
    {
        $producto = Producto::find($this->productoId);

        $this->carrito[] = [
            'id' => uniqid(),
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_codigo' => $producto->codigo,
            'marca' => $producto->marca ?? 'Sin marca',
            'cantidad' => (int)$this->cantidad,
            'precio_unitario' => (float)$this->precioCompra,
            'subtotal' => (float)($this->cantidad * $this->precioCompra),
            'codigo_lote' => $this->codigoLote,
            'fecha_vencimiento' => $this->fechaVencimiento,
        ];

        $this->guardarCarritoEnSesion();
        $this->calcularTotal();

        $this->reset(['productoId', 'precioCompra', 'codigoLote', 'fechaVencimiento']);
        $this->cantidad = 1;

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'success',
        //     'mensaje' => 'Producto agregado al carrito temporalmente',
        // ]);
    }

    /**
     * Actualizar precio unitario del carrito
     */
    public function actualizarPrecioUnitario($carritoId, $nuevoPrecio)
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        foreach ($this->carrito as &$item) {
            if ($item['id'] === $carritoId) {
                $item['precio_unitario'] = floatval($nuevoPrecio);
                $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
                break;
            }
        }

        $this->guardarCarritoEnSesion();
        $this->calcularTotal();

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'success',
        //     'mensaje' => 'Precio actualizado',
        // ]);
    }

    /**
     * Limpiar fecha de vencimiento
     */
    public function limpiarFechaVencimiento($carritoId)
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        foreach ($this->carrito as &$item) {
            if ($item['id'] === $carritoId) {
                $item['fecha_vencimiento'] = null;
                break;
            }
        }

        $this->guardarCarritoEnSesion();

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'info',
        //     'mensaje' => 'Fecha de vencimiento eliminada',
        // ]);
    }

    /**
     * Abrir modal de edición
     */
    public function abrirModalEdicion($index)
    {
        $this->itemEditarIndex = $index;
        $this->mostrarModalEdicion = true;
    }

    /**
     * Cerrar modal de edición
     */
    public function cerrarModalEdicion()
    {
        $this->mostrarModalEdicion = false;
        $this->itemEditarIndex = null;
    }

    /**
     * Guardar edición del carrito
     */
    public function guardarEdicionCarrito()
    {
        if ($this->itemEditarIndex !== null && isset($this->carrito[$this->itemEditarIndex])) {
            $item = &$this->carrito[$this->itemEditarIndex];
            $productoId = $item['producto_id'];
            $codigoLote = $item['codigo_lote'];
            $itemId = $item['id'];

            // VERIFICAR SI EL NUEVO LOTE YA EXISTE
            $resultado = $this->verificarLoteExistente(
                $productoId,
                $codigoLote,
                $this->compra->proveedor_id,
                $itemId
            );

            if ($resultado === 'mismo_carrito') {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'warning',
                    'mensaje' => 'Este lote ya está en el carrito.',
                ]);
                return;
            }

            if ($resultado === 'otro_proveedor') {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'error',
                    'mensaje' => 'Este lote ya existe para OTRO proveedor. No puede usar este código.',
                ]);
                return;
            }

            if ($resultado === 'mismo_proveedor') {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'warning',
                    'mensaje' => 'Este lote ya existe para el mismo proveedor. Verifique que sea correcto.',
                ]);
            }

            $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];

            $this->guardarCarritoEnSesion();
            $this->calcularTotal();

            $this->cerrarModalEdicion();

            $this->dispatch('mostrar-alerta', [
                'icono' => 'success',
                'mensaje' => 'Producto actualizado correctamente',
            ]);
        }
    }

    /**
     * Agregar producto sugerido al carrito
     */
    public function agregarProductoSugeridoAlCarrito($index)
    {
        if (!$this->sincronizarSucursalOperativa()) {
            $this->dispatch('mostrar-alerta', mensaje: 'La compra no pertenece a la sucursal activa asignada a su usuario.', icono: 'error');
            return;
        }

        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        if (!isset($this->productos_a_comprar[$index])) {
            return;
        }

        $producto_data = $this->productos_a_comprar[$index];

        // 🔴 VALIDACIÓN DE STOCK MÁXIMO PARA PRODUCTO SUGERIDO
        $producto = Producto::find($producto_data['id']);
        if ($producto) {
            $stockActual = $this->obtenerStockActualProducto($producto->id);
            $cantidadEnCarrito = $this->obtenerCantidadEnCarrito($producto->id);
            $cantidadTotal = $stockActual + $cantidadEnCarrito + $producto_data['cantidad_sugerida'];

            if ($producto->stock_maximo > 0 && $cantidadTotal > $producto->stock_maximo) {
                $disponible = $producto->stock_maximo - ($stockActual + $cantidadEnCarrito);
                $this->dispatch('mostrar-alerta-stock', [
                    'mensaje' => "⚠️ No se puede agregar '{$producto->nombre}'. " .
                                "Stock actual: {$stockActual} | " .
                                "En carrito: {$cantidadEnCarrito} | " .
                                "Stock máximo: {$producto->stock_maximo}. " .
                                "Solo puedes agregar {$disponible} unidades más.",
                    'icono' => 'warning'
                ]);
                return;
            }
        }

        // VERIFICAR SI EL LOTE YA EXISTE
        $resultado = $this->verificarLoteExistente(
            $producto_data['id'],
            $producto_data['codigo_lote'],
            $this->compra->proveedor_id
        );

        if ($resultado === 'mismo_carrito') {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'Este producto ya está en el carrito.',
                'icono' => 'warning'
            ]);
            return;
        }

        if ($resultado === 'otro_proveedor') {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'El lote ' . $producto_data['codigo_lote'] . ' ya existe para OTRO proveedor. No puede usar este código.',
                'icono' => 'error'
            ]);
            return;
        }

        if ($resultado === 'mismo_proveedor') {
            $this->dispatch('confirmar-lote-duplicado', [
                'producto_id' => $producto_data['id'],
                'cantidad' => $producto_data['cantidad_sugerida'],
                'precio_compra' => $producto_data['precio_compra'],
                'codigo_lote' => $producto_data['codigo_lote'],
                'fecha_vencimiento' => $producto_data['fecha_vencimiento'],
                'marca' => $producto_data['marca'],
                'mensaje' => 'El lote ' . $producto_data['codigo_lote'] . ' ya existe para el MISMO proveedor. ¿Desea continuar?'
            ]);
            return;
        }

        // Si no existe, proceder
        $this->productoId = $producto_data['id'];
        $this->precioCompra = $producto_data['precio_compra'];
        $this->codigoLote = $producto_data['codigo_lote'];
        $this->fechaVencimiento = $producto_data['fecha_vencimiento'];
        $this->cantidad = $producto_data['cantidad_sugerida'];

        $this->agregarAlCarritoDirecto();

        unset($this->productos_a_comprar[$index]);
        $this->productos_a_comprar = array_values($this->productos_a_comprar);
    }

    /**
     * Agregar todos los productos sugeridos al carrito
     */
    public function agregarTodosLosProductosSugeridosAlCarrito()
    {
        if (!$this->sincronizarSucursalOperativa()) {
            $this->dispatch('mostrar-alerta', mensaje: 'La compra no pertenece a la sucursal activa asignada a su usuario.', icono: 'error');
            return;
        }

        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        if (empty($this->productos_a_comprar) || !is_array($this->productos_a_comprar)) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'No hay productos pendientes para agregar.',
                'icono' => 'info'
            ]);
            return;
        }

        $contador = 0;
        $errores = [];
        $erroresStockMaximo = [];

        foreach ($this->productos_a_comprar as $index => $producto_data) {
            // 🔴 VALIDACIÓN DE STOCK MÁXIMO ANTES DE AGREGAR CADA PRODUCTO
            $producto = Producto::find($producto_data['id']);
            if ($producto) {
                $stockActual = $this->obtenerStockActualProducto($producto->id);
                $cantidadEnCarrito = $this->obtenerCantidadEnCarrito($producto->id);
                $cantidadTotal = $stockActual + $cantidadEnCarrito + $producto_data['cantidad_sugerida'];

                if ($producto->stock_maximo > 0 && $cantidadTotal > $producto->stock_maximo) {
                    $erroresStockMaximo[] = "{$producto_data['nombre']} (solo se pueden agregar " .
                                            ($producto->stock_maximo - ($stockActual + $cantidadEnCarrito)) . " de {$producto_data['cantidad_sugerida']} sugeridas)";
                    continue;
                }
            }

            // Verificar cada producto antes de agregarlo
            $resultado = $this->verificarLoteExistente(
                $producto_data['id'],
                $producto_data['codigo_lote'],
                $this->compra->proveedor_id
            );

            if ($resultado === 'otro_proveedor') {
                $errores[] = $producto_data['nombre'] . ' (lote de otro proveedor)';
                continue;
            }

            $this->productoId = $producto_data['id'];
            $this->precioCompra = $producto_data['precio_compra'];
            $this->codigoLote = $producto_data['codigo_lote'];
            $this->fechaVencimiento = $producto_data['fecha_vencimiento'];
            $this->cantidad = $producto_data['cantidad_sugerida'];

            $this->agregarAlCarritoDirecto();
            $contador++;
        }

        // Limpiar productos procesados
        $this->productos_a_comprar = [];

        $mensaje = '';
        if ($contador > 0) {
            $mensaje = "✅ Se agregaron {$contador} productos al carrito.";
        }
        if (!empty($errores)) {
            $mensaje .= "\n❌ No se agregaron: " . implode(', ', $errores);
        }
        if (!empty($erroresStockMaximo)) {
            $mensaje .= "\n⚠️ Stock máximo excedido: " . implode(', ', $erroresStockMaximo);
        }

        if ($contador > 0) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => $mensaje,
                'icono' => 'success'
            ]);
        } else {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => "No se pudo agregar ningún producto. " . $mensaje,
                'icono' => 'warning'
            ]);
        }
    }

    /**
     * Eliminar producto sugerido de la lista
     */
    public function eliminarProductoSugerido($index)
    {
        if (isset($this->productos_a_comprar[$index])) {
            unset($this->productos_a_comprar[$index]);
            $this->productos_a_comprar = array_values($this->productos_a_comprar);

            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'Producto eliminado de la lista',
                'icono' => 'info'
            ]);
        }
    }

    /**
     * Obtener productos a comprar como array seguro
     */
    public function getProductosAComprarProperty()
    {
        return is_array($this->productos_a_comprar) ? $this->productos_a_comprar : [];
    }

    /**
     * Eliminar producto del carrito
     */
    public function eliminarDelCarrito($carritoId)
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        $this->carrito = array_filter($this->carrito, function($item) use ($carritoId) {
            return $item['id'] !== $carritoId;
        });

        $this->carrito = array_values($this->carrito);
        $this->guardarCarritoEnSesion();
        $this->calcularTotal();

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'success',
        //     'mensaje' => 'Producto eliminado del carrito',
        // ]);
    }

    /**
     * Actualizar cantidad del carrito
     */
    public function actualizarCantidadCarrito($carritoId, $nuevaCantidad)
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        foreach ($this->carrito as &$item) {
            if ($item['id'] === $carritoId) {
                // 🔴 VALIDAR STOCK MÁXIMO AL ACTUALIZAR CANTIDAD
                $producto = Producto::query()->lockForUpdate()->find($item['producto_id']);
                if ($producto && $producto->stock_maximo > 0) {
                    $stockActual = $this->obtenerStockActualProducto($producto->id);
                    $otrasCantidadesEnCarrito = $this->obtenerCantidadEnCarrito($producto->id) - $item['cantidad'];
                    $cantidadTotal = $stockActual + $otrasCantidadesEnCarrito + intval($nuevaCantidad);

                    if ($cantidadTotal > $producto->stock_maximo) {
                        $disponible = $producto->stock_maximo - ($stockActual + $otrasCantidadesEnCarrito);
                        $this->dispatch('mostrar-alerta-stock', [
                            'mensaje' => "⚠️ No se puede establecer {$nuevaCantidad} unidades.<br><br>" .
                                        "📦 Stock actual: {$stockActual} unidades<br>" .
                                        "📊 Stock máximo: {$producto->stock_maximo} unidades<br>" .
                                        "✅ Solo puedes tener {$disponible} unidades en total.",
                            'icono' => 'warning'
                        ]);
                        return;
                    }
                }

                $item['cantidad'] = intval($nuevaCantidad);
                $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
                break;
            }
        }

        $this->guardarCarritoEnSesion();
        $this->calcularTotal();
    }


    /**
     * Limpiar carrito
     */
    public function limpiarCarrito()
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        $this->carrito = [];
        $this->guardarCarritoEnSesion();
        $this->calcularTotal();

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'info',
        //     'mensaje' => 'Carrito limpiado',
        // ]);
    }


    /**
     * Actualizar marca del producto en el carrito
     */
    public function actualizarMarcaCarrito($carritoId, $nuevaMarca)
    {
        if ($this->compra->detalles()->count() > 0) {
            return;
        }

        foreach ($this->carrito as &$item) {
            if ($item['id'] === $carritoId) {
                $item['marca'] = $nuevaMarca;
                break;
            }
        }

        $this->guardarCarritoEnSesion();

        // $this->dispatch('mostrar-alerta', [
        //     'icono' => 'success',
        //     'mensaje' => 'Marca actualizada',
        // ]);
    }

    /**
     * Renderizar la vista
     */
    public function render()
    {
        if ($this->compra->detalles()->count() > 0) {
            $this->compra->load('detalles.producto', 'detalles.lote');
            $this->totalCompra = $this->compra->detalles->sum('subtotal');
        }

        return view('livewire.admin.compras.items-compra');
    }

    /**
     * Método público para recibir el evento de procesar finalización
     */
    // ItemsCompra.php
    public function procesarFinalizacion()
    {
        try {
            $this->procesarFinalizacionCompra();
            return true;
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', mensaje: 'Error: ' . $e->getMessage(), icono: 'error');
            return false;
        }
    }

    private function procesarFinalizacionCompra(): void
    {
        if (!Auth::user()->can('compras.finalizarCompra')) {
            throw new \RuntimeException('No tiene permiso para recibir compras.');
        }

        $usuario = Auth::user();
        $sucursalId = $this->sincronizarSucursalOperativa();
        if (!$sucursalId) {
            throw new \RuntimeException('La compra no pertenece a la sucursal activa asignada a su usuario.');
        }

        $items = $this->normalizarCarritoParaFinalizar();
        $compra = DB::transaction(function () use ($sucursalId, $items) {
            Sucursal::query()->whereKey($sucursalId)->where('activa', true)->lockForUpdate()->firstOrFail();
            DB::table('business_sequences')->where('clave', 'lotes_mutex')->lockForUpdate()->first();
            $compra = Compra::query()->lockForUpdate()->findOrFail($this->compra->id);

            if ((int) $compra->sucursal_id !== $sucursalId) {
                throw new \RuntimeException('La compra pertenece a otra sucursal.');
            }

            if ($compra->estado === 'Recibido' || $compra->detalles()->exists()) {
                throw new \RuntimeException('Esta compra ya fue recibida anteriormente.');
            }
            if ($compra->sucursal_id && (int) $compra->sucursal_id !== $sucursalId) {
                throw new \RuntimeException('La sucursal de destino de la compra no puede cambiarse.');
            }

            $cantidadesPorProducto = collect($items)->groupBy('producto_id')
                ->map(fn ($grupo) => (int) $grupo->sum('cantidad'));

            foreach ($cantidadesPorProducto as $productoId => $cantidadEntrante) {
                $producto = Producto::query()->lockForUpdate()->findOrFail($productoId);
                $inventarios = InventarioSucuralLote::query()
                    ->where('sucursal_id', $sucursalId)
                    ->whereHas('lote', fn ($query) => $query->where('producto_id', $productoId))
                    ->lockForUpdate()
                    ->get();
                $stockActual = (int) $inventarios->sum('cantidad_en_sucursal');

                if ((int) $producto->stock_maximo > 0 && $stockActual + $cantidadEntrante > (int) $producto->stock_maximo) {
                    throw new \RuntimeException(
                        "La recepción de {$producto->nombre} excede su stock máximo en la sucursal. " .
                        "Actual: {$stockActual}; entrada: {$cantidadEntrante}; máximo: {$producto->stock_maximo}."
                    );
                }
            }

            $inventarioService = app(InventarioService::class);
            foreach ($items as $item) {
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);

                if (Lote::query()->where('codigo_lote', $item['codigo_lote'])->lockForUpdate()->exists()) {
                    throw new \RuntimeException("El código de lote {$item['codigo_lote']} ya está registrado. Use un código único.");
                }

                $precioVenta = (float) $producto->porcentaje_ganancia > 0
                    ? round($item['precio_unitario'] * (1 + ((float) $producto->porcentaje_ganancia / 100)), 2)
                    : round((float) ($producto->precio_venta ?? 0), 2);

                $lote = Lote::create([
                    'producto_id' => $producto->id,
                    'proveedor_id' => $compra->proveedor_id,
                    'codigo_lote' => $item['codigo_lote'],
                    'fecha_entrada' => now()->toDateString(),
                    'fecha_vencimiento' => $item['fecha_vencimiento'],
                    'cantidad_inicial' => $item['cantidad'],
                    'cantidad_actual' => 0,
                    'precio_compra' => $item['precio_unitario'],
                    'precio_venta' => $precioVenta,
                    'estado' => true,
                ]);

                $compra->detalles()->create([
                    'producto_id' => $producto->id,
                    'lote_id' => $lote->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                ]);

                $precioAnterior = (float) ($producto->precio_compra ?? 0);
                if (abs($precioAnterior - $item['precio_unitario']) > 0.001) {
                    HistorialPrecio::create([
                        'producto_id' => $producto->id,
                        'compra_id' => $compra->id,
                        'user_id' => Auth::id(),
                        'precio_anterior' => $precioAnterior,
                        'precio_nuevo' => $item['precio_unitario'],
                        'motivo' => 'Actualización por compra',
                        'observaciones' => "Compra #{$compra->id} - Lote: {$item['codigo_lote']}",
                    ]);
                }

                $producto->update([
                    'precio_compra' => $item['precio_unitario'],
                    'precio_venta' => $precioVenta,
                    'estado' => true,
                ]);

                $inventarioService->aumentar(
                    $lote->id,
                    $sucursalId,
                    $item['cantidad'],
                    Auth::id(),
                    Compra::class,
                    $compra->id,
                    'Recepción de compra #' . $compra->id
                );
            }

            $compra->update([
                'total' => round((float) collect($items)->sum('subtotal'), 2),
                'sucursal_id' => $sucursalId,
                'estado' => 'Recibido',
            ]);

            return $compra;
        }, 3);

        $this->compra = $compra->fresh(['detalles.producto', 'detalles.lote']);
        $this->limpiarCarritoSesion();
        $this->dispatch(
            'compra-finalizada-con-nota',
            compraId: $compra->id,
            notaUrl: route('compras.nota-pdf', $compra->id),
            descargarUrl: route('compras.descargar-nota', $compra->id)
        );
    }

    private function normalizarCarritoParaFinalizar(): array
    {
        if ($this->carrito === []) {
            throw new \RuntimeException('El carrito está vacío.');
        }

        $normalizados = [];
        $codigos = [];
        foreach ($this->carrito as $indice => $item) {
            if (!is_array($item)) {
                throw new \RuntimeException('El carrito contiene información inválida.');
            }

            $productoId = filter_var($item['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $precio = is_numeric($item['precio_unitario'] ?? null) ? round((float) $item['precio_unitario'], 2) : 0;
            $codigo = trim((string) ($item['codigo_lote'] ?? ''));
            $fechaVencimiento = $item['fecha_vencimiento'] ?? null;

            if (!$productoId || !Producto::query()->whereKey($productoId)->exists()) {
                throw new \RuntimeException('Uno de los productos del carrito ya no existe.');
            }
            if (!$cantidad || $cantidad < 1 || $cantidad > 1000000) {
                throw new \RuntimeException('Todas las cantidades deben ser números enteros mayores a cero.');
            }
            if ($precio <= 0 || $precio > 99999999.99) {
                throw new \RuntimeException('Todos los precios de compra deben ser mayores a cero.');
            }
            if ($codigo === '' || mb_strlen($codigo) > 50) {
                throw new \RuntimeException('Cada producto debe tener un código de lote válido de hasta 50 caracteres.');
            }
            if (isset($codigos[mb_strtolower($codigo)])) {
                throw new \RuntimeException("El código de lote {$codigo} está repetido en el carrito.");
            }
            if ($fechaVencimiento) {
                try {
                    $fechaVencimiento = \Carbon\Carbon::parse($fechaVencimiento)->toDateString();
                } catch (\Throwable) {
                    throw new \RuntimeException("La fecha de vencimiento del lote {$codigo} no es válida.");
                }
                if ($fechaVencimiento < now()->toDateString()) {
                    throw new \RuntimeException("El lote {$codigo} ya está vencido y no puede recibirse.");
                }
            }

            $codigos[mb_strtolower($codigo)] = true;
            $normalizados[] = [
                'producto_id' => (int) $productoId,
                'cantidad' => (int) $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => round($precio * (int) $cantidad, 2),
                'codigo_lote' => $codigo,
                'fecha_vencimiento' => $fechaVencimiento ?: null,
            ];
        }

        return $normalizados;
    }

    public function confirmarYFinalizar()
    {
        if (!Auth::user()->can('compras.finalizarCompra')) {
            $this->dispatch('mostrar-alerta', mensaje: 'No tiene permiso para finalizar compras.', icono: 'error');
            return;
        }
        if ($this->compra->estado === 'Recibido' || $this->compra->detalles()->exists()) {
            $this->dispatch('mostrar-alerta', mensaje: 'Esta compra ya fue finalizada anteriormente.', icono: 'info');
            return;
        }
        if ($this->carrito === []) {
            $this->dispatch('mostrar-alerta', mensaje: 'No hay productos en el carrito para finalizar.', icono: 'warning');
            return;
        }
        if (!$this->sincronizarSucursalOperativa()) {
            $this->dispatch('mostrar-alerta', mensaje: 'La compra no pertenece a la sucursal activa asignada a su usuario.', icono: 'warning');
            return;
        }

        try {
            $items = $this->normalizarCarritoParaFinalizar();
            $this->dispatch(
                'mostrar-confirmacion-finalizar',
                total: round((float) collect($items)->sum('subtotal'), 2),
                sucursal_nombre: $this->sucursalNombre,
                cantidad: count($items)
            );
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', mensaje: $e->getMessage(), icono: 'error');
        }
    }

    public function updatedProductoId($value): void
    {
        if ($value) {
            $producto = Producto::find($value);
            $this->precioCompra = $producto?->precio_compra;
        }
    }

    private function generarLoteUnico($producto)
    {
        $categoria = $producto->categoria;
        $prefijoCategoria = strtoupper(substr($categoria->nombre ?? 'GEN', 0, 2));
        $prefijoProducto = strtoupper(substr(preg_replace('/[^A-Z]/', '', $producto->nombre), 0, 2));
        $fecha = now()->format('ymd');
        $compraId = str_pad($this->compra->id, 3, '0', STR_PAD_LEFT);

        $maxIntentos = 50;
        $intento = 0;

        do {
            $secuencia = rand(100, 999);
            $codigoLote = "{$prefijoCategoria}{$prefijoProducto}{$fecha}{$compraId}{$secuencia}";

            // Verificar en BD para OTRO proveedor (sí existe para OTRO proveedor, NO puede usar)
            $existeParaOtroProveedor = Lote::where('codigo_lote', $codigoLote)
                ->where('proveedor_id', '!=', $this->compra->proveedor_id)
                ->exists();

            // Verificar en carrito actual (no puede haber duplicados en el mismo carrito)
            $existeEnCarrito = collect($this->carrito)->contains('codigo_lote', $codigoLote);

            $intento++;

            if ($intento >= $maxIntentos) {
                // Si después de muchos intentos no hay éxito, usar timestamp + aleatorio
                $codigoLote = "{$prefijoCategoria}{$prefijoProducto}{$fecha}{$compraId}" . time() . rand(10, 99);
                break;
            }

        } while ($existeParaOtroProveedor || $existeEnCarrito);

        return $codigoLote;
    }

    private function validarLotesAntesDeFinalizar()
    {
        $errores = [];

        // 🔴 VALIDACIÓN 1: Verificar duplicados en el carrito (mismo producto mismo lote)
        $itemsUnicos = [];
        foreach ($this->carrito as $item) {
            $clave = $item['producto_id'] . '|' . $item['codigo_lote'];
            if (in_array($clave, $itemsUnicos)) {
                $errores[] = "Producto duplicado con mismo lote en carrito: {$item['producto_nombre']} - Lote: {$item['codigo_lote']}";
            }
            $itemsUnicos[] = $clave;
        }

        // 🔴 VALIDACIÓN 2: Verificar lotes de otros proveedores en BD
        foreach ($this->carrito as $item) {
            $loteEnBD = Lote::where('codigo_lote', $item['codigo_lote'])
                ->where('proveedor_id', '!=', $this->compra->proveedor_id)
                ->first();

            if ($loteEnBD) {
                $proveedor = $loteEnBD->proveedor;
                $producto = $loteEnBD->producto;

                $errores[] = "El lote '{$item['codigo_lote']}' ya pertenece a otro proveedor: '{$proveedor->nombre}' " .
                            "(Producto: {$producto->nombre})";
            }
        }

        return $errores;
    }

    private function sincronizarSucursalOperativa(): ?int
    {
        $usuario = Auth::user();
        $sucursal = $usuario?->sucursalOperativa();

        if (! $usuario || ! $sucursal) {
            return null;
        }

        $compra = Compra::query()
            ->withCount('detalles')
            ->select(['id', 'user_id', 'sucursal_id', 'estado'])
            ->find($this->compra->id);

        if (! $compra) {
            return null;
        }

        // Compatibilidad con compras pendientes creadas antes de esta corrección:
        // se vinculan a la sucursal del usuario únicamente si todavía no movieron stock.
        if (! $compra->sucursal_id) {
            $estado = mb_strtolower(trim((string) $compra->estado));
            $puedeVincularse = $compra->detalles_count === 0
                && ! in_array($estado, ['recibido', 'cancelada', 'cancelado'], true);

            if (! $puedeVincularse) {
                return null;
            }

            $compra->update(['sucursal_id' => $sucursal->id]);
            $compra->sucursal_id = $sucursal->id;
        }

        if ((int) $compra->sucursal_id !== (int) $sucursal->id) {
            return null;
        }

        // El valor público de Livewire solo refleja el contexto resuelto en servidor;
        // nunca se utiliza para decidir la sucursal de destino.
        $this->sucursal_id = (int) $sucursal->id;
        $this->sucursalNombre = $sucursal->nombre;
        $this->compra->sucursal_id = (int) $sucursal->id;
        $this->compra->setRelation('sucursal', $sucursal);

        return (int) $sucursal->id;
    }

    private function obtenerStockActualProducto($productoId, $sucursalId = null)
    {
        $sucursalId = $sucursalId ?: $this->sucursal_id;

        return InventarioSucuralLote::query()
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->whereHas('lote', function ($query) use ($productoId) {
                $query->where('producto_id', $productoId);
            })
            ->sum('cantidad_en_sucursal');
    }

    /**
     * Obtener cantidad de un producto en el carrito actual.
     */
    private function obtenerCantidadEnCarrito($productoId)
    {
        $total = 0;
        foreach ($this->carrito as $item) {
            if ($item['producto_id'] == $productoId) {
                $total += $item['cantidad'];
            }
        }

        return $total;
    }
}
