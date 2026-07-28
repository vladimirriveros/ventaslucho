<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\MovimientoCaja;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\Caja;
use App\Models\Cotizacion;
use App\Models\Pago;
use App\Models\Banca;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ItemsVenta extends Component
{
    public $venta;
    public $sucursal_id;
    public $sucursales;
    public $cliente_id;
    public $clientes;
    public $productoId;
    public $cantidad = 1;
    public $productos;
    public $carrito = [];
    public $subtotalVenta = 0;
    public $totalVenta = 0;
    public $cotizacion_id = null;
    public $metodo_pago = 'efectivo';
    public $tipo_venta = 'contado';
    public $efectivo_recibido = null;
    public $cambio = 0;
    public $monto_efectivo_mixto = 0;
    public $monto_qr_mixto = 0;
    public bool $borrador_restaurado = false;
    public bool $borrador_inicializado = false;

    // Para búsqueda de productos
    public $busqueda_producto = '';
    public $productos_filtrados = [];

    // Para búsqueda de clientes
    public $busqueda_cliente = '';
    public $clientes_filtrados = [];
    public $mostrar_resultados_clientes = false;

    // Cliente nuevo rápido
    public $nuevo_cliente_nombre = '';
    public $nuevo_cliente_nit = '';
    public $nuevo_cliente_telefono = '';
    public $nuevo_cliente_email = '';
    public $mostrar_modal_cliente = false;

    // Propiedades para descuento
    public $descuento_porcentaje = 0;
    public $descuento_monto = 0;
    public $nuevo_total = 0; // Nueva propiedad para el total modificado
    public $mostrar_modal_descuento = false;

    // Propiedades para editar cliente
public $editando_cliente = false;
public $cliente_edit_id = null;
public $cliente_edit_nombre = '';
public $cliente_edit_nit = '';
public $cliente_edit_telefono = '';
public $cliente_edit_email = '';
public $mostrar_modal_editar_cliente = false;

    public $bancas = [];
    public $banca_id = null;
    public $mostrar_modal_bancas = false;
    public $banca_seleccionada = null;

    // Propiedades para observaciones adicionales
public $incluye_impuesto = 'con_impuesto'; // 'con_impuesto', 'sin_impuesto'
public $forma_pago = 'contado'; // 'contado', 'transferencia'
public $lugar_entrega = '';
public $plazo_entrega = 5; // días
public $validez_economica = 48; // horas
public $observaciones_adicionales = '';

    protected $listeners = [
        'confirmarVenta' => 'confirmarVenta',
        'procesarVenta' => 'procesarVenta',
        'set-producto-id' => 'setProductoId'
    ];

    public function mount($venta = null)
    {
        $user = Auth::user();
        abort_unless($user && $user->tieneSucursalOperativa(), 403, 'Su usuario debe tener una sucursal activa asignada.');

        $this->sucursal_id = (int) $user->sucursal_id;
        $this->sucursales = collect([$user->sucursal]);
        $this->clientes = Cliente::where('activo', true)->orderBy('nombre')->get();

        if ($venta) {
            abort_unless((int) $venta->sucursal_id === (int) $user->sucursal_id, 403, 'La venta pertenece a otra sucursal.');
            $this->venta = $venta;
            $this->cargarDetallesVenta();
        } else {
            $this->venta = null;
            $this->cargarProductos();
        }

        $cotizacionId = request()->integer('cotizacion_id');
        if ($cotizacionId && !$venta) {
            $cotizacion = Cotizacion::with(['detalles.producto', 'cliente'])->find($cotizacionId);
            if (!$cotizacion || $cotizacion->estado !== 'activa' || ($cotizacion->valida_hasta && $cotizacion->valida_hasta->isPast())) {
                $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cotización no está activa o ya venció.');
            } elseif (!$user->puedeOperarSucursal((int) $cotizacion->sucursal_id)) {
                $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cotización pertenece a otra sucursal.');
            } else {
                $this->cotizacion_id = $cotizacion->id;
                $this->cargarCotizacion($cotizacion);
            }
        }

        $this->cargarBancas();

        if (!$venta && !$cotizacionId) {
            $this->restaurarBorrador();
        }
        $this->borrador_inicializado = true;
    }

    public function actualizarPrecioUnitario($index, $nuevoPrecio)
    {
        if (!Auth::user()->can('ventas.modificar-precio')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para modificar precios.');
            return;
        }
        if (!isset($this->carrito[$index])) {
            return;
        }

        $nuevoPrecio = round((float) $nuevoPrecio, 2);
        if ($nuevoPrecio <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El precio debe ser mayor a cero.');
            return;
        }

        $this->carrito[$index]['precio_unitario'] = $nuevoPrecio;
        $this->carrito[$index]['subtotal'] = round($this->carrito[$index]['cantidad'] * $nuevoPrecio, 2);
        $this->carrito[$index]['precio_modificado'] = true;

        if (!empty($this->carrito[$index]['multi_lote']) && !empty($this->carrito[$index]['lotes_usados'])) {
            foreach ($this->carrito[$index]['lotes_usados'] as &$lote) {
                $lote['precio_unitario'] = $nuevoPrecio;
                $lote['subtotal'] = round($lote['cantidad'] * $nuevoPrecio, 2);
            }
            unset($lote);
        }

        $this->calcularTotal();
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Precio actualizado a Bs ' . number_format($nuevoPrecio, 2));
    }

/**
 * Calcular precio promedio ponderado cuando se usan múltiples lotes
 */
private function calcularPrecioPromedioPonderado($itemsAgregados)
{
    $totalCantidad = 0;
    $totalValor = 0;

    foreach ($itemsAgregados as $item) {
        $totalCantidad += $item['cantidad'];
        $totalValor += $item['subtotal'];
    }

    return $totalCantidad > 0 ? $totalValor / $totalCantidad : 0;
}

    public function abrirModalDescuento()
    {
        if (!Auth::user()->can('ventas.aplicar-descuento')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para aplicar descuentos.');
            return;
        }
        $this->descuento_porcentaje = $this->subtotalVenta > 0 ? round($this->descuento_monto / $this->subtotalVenta * 100, 2) : 0;
        $this->nuevo_total = $this->totalVenta;
        $this->mostrar_modal_descuento = true;
    }

    public function actualizarCantidadCarrito($index, $nuevaCantidad)
    {
        if ($this->venta && $this->venta->estado != 'pendiente') {
            return;
        }

        if (!is_numeric($nuevaCantidad) || (float) $nuevaCantidad !== (float) (int) $nuevaCantidad) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'La cantidad debe ser un número entero.');
            return;
        }
        $nuevaCantidad = (int) $nuevaCantidad;
        if ($nuevaCantidad <= 0) {
            $this->eliminarDelCarrito($index);
            return;
        }

        $item = $this->carrito[$index];
        $producto = Producto::find($item['producto_id']);

        // Obtener lotes disponibles ordenados FIFO
        $lotesDisponibles = $this->obtenerLotesDisponibles($producto->id);
        $stockTotal = $lotesDisponibles->sum('stock_disponible');

        // Validar que no exceda el stock total
        if ($nuevaCantidad > $stockTotal) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => "⚠️ Stock insuficiente. Solo hay {$stockTotal} unidades disponibles."
            ]);

            if ($stockTotal > 0) {
                $this->carrito[$index]['cantidad'] = $stockTotal;
                $this->calcularTotal();
            } else {
                $this->eliminarDelCarrito($index);
            }
            return;
        }

        // Si el precio fue modificado manualmente, mantenerlo
        $precioModificado = isset($item['precio_modificado']) && $item['precio_modificado'] === true;
        $precioManual = $precioModificado ? $item['precio_unitario'] : null;

        // Recalcular distribución de lotes con FIFO para la nueva cantidad
        $cantidadRestante = $nuevaCantidad;
        $nuevosLotes = [];
        $totalCantidadLotes = 0;
        $totalValorLotes = 0;

        foreach ($lotesDisponibles as $loteInfo) {
            if ($cantidadRestante <= 0) break;

            $cantidadATomar = min($cantidadRestante, $loteInfo->stock_disponible);

            if ($cantidadATomar > 0) {
                $subtotalLote = $cantidadATomar * $loteInfo->precio_venta;
                $nuevosLotes[] = [
                    'lote_id' => $loteInfo->lote_id,
                    'lote_codigo' => $loteInfo->codigo_lote,
                    'cantidad' => $cantidadATomar,
                    'precio_unitario' => $loteInfo->precio_venta,
                    'subtotal' => $subtotalLote,
                    'created_at' => $loteInfo->created_at,
                ];
                $totalCantidadLotes += $cantidadATomar;
                $totalValorLotes += $subtotalLote;
                $cantidadRestante -= $cantidadATomar;
            }
        }

        $usarCPP = count($nuevosLotes) > 1;

        if ($usarCPP) {
            // Calcular precio promedio ponderado (CPP)
            $precioPromedio = $totalValorLotes / $totalCantidadLotes;

            // Si el precio fue modificado manualmente, usar ese precio en lugar del CPP
        if ($precioModificado && $precioManual) {
            $precioPromedio = round((float) $precioManual, 2);
            foreach ($nuevosLotes as &$lote) {
                $lote['precio_unitario'] = $precioPromedio;
                $lote['subtotal'] = round($lote['cantidad'] * $precioPromedio, 2);
            }
            unset($lote);
            $totalValorLotes = round((float) collect($nuevosLotes)->sum('subtotal'), 2);
        }


            $this->carrito[$index] = array_merge($this->carrito[$index], [
                'cantidad' => $totalCantidadLotes,
                'precio_unitario' => $precioPromedio,
                'subtotal' => $totalValorLotes,
                'lotes_usados' => $nuevosLotes,
                'lote_codigo' => 'MÚLTIPLES LOTES (CPP)',
                'multi_lote' => true,
                'lote_id' => null,
                'precio_modificado' => $precioModificado, // Mantener la bandera
            ]);

            if (!$precioModificado){
                $this->dispatch('mostrar-alerta', [
                'icono' => 'info',
                'mensaje' => "📊 Precio actualizado con CPP: Bs " . number_format($precioPromedio, 2)
            ]);
            } else {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'info',
                    'mensaje' => "✅ Cantidad actualizada. Precio manual mantenido: Bs " . number_format($precioPromedio, 2)
                ]);
            }

        } else {
            $loteUnico = $nuevosLotes[0];
            $precioUnitario = $loteUnico['precio_unitario'];

            // Si el precio fue modificado manualmente, usar ese precio
            if ($precioModificado && $precioManual) {
                $precioUnitario = $precioManual;
                $loteUnico['precio_unitario'] = $precioManual;
                $loteUnico['subtotal'] = $loteUnico['cantidad'] * $precioManual;
            }

            $this->carrito[$index] = array_merge($this->carrito[$index], [
                'lote_id' => $loteUnico['lote_id'],
                'lote_codigo' => $loteUnico['lote_codigo'],
                'cantidad' => $loteUnico['cantidad'],
                'precio_unitario' => $loteUnico['precio_unitario'],
                'subtotal' => $loteUnico['subtotal'],
                'multi_lote' => false,
                'lotes_usados' => null,
                'precio_modificado' => $precioModificado, // Mantener la bandera
            ]);

            // $this->dispatch('mostrar-alerta', [
            //     'icono' => 'success',
            //     'mensaje' => "✅ Cantidad actualizada: {$loteUnico['cantidad']} unidades"
            // ]);

            if (!$precioModificado) {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'success',
                    'mensaje' => "✅ Cantidad actualizada: {$loteUnico['cantidad']} unidades"
                ]);
            } else {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'success',
                    'mensaje' => "✅ Cantidad actualizada a {$loteUnico['cantidad']} unidades. Precio manual mantenido: Bs " . number_format($precioUnitario, 2)
                ]);
            }
        }

        $this->calcularTotal();

        // Autocompletar efectivo recibido si es contado en efectivo
        if ($this->tipo_venta == 'contado' && $this->metodo_pago == 'efectivo') {
            $this->efectivo_recibido = $this->totalVenta;
            $this->calcularCambio();
        }
    }

    public function agregarAlCarrito()
    {
        if (!Auth::user()->can('ventas.create')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar ventas.');
            return;
        }
        if (!$this->sucursal_id || !Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Su usuario no puede vender en esta sucursal.');
            return;
        }

        if (!$this->productoId) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'warning',
                'mensaje' => '⚠️ Debe seleccionar un producto'
            ]);
            return;
        }

        $producto = Producto::query()->where('estado', true)->find($this->productoId);

        if (!$producto) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => '❌ Producto no encontrado'
            ]);
            return;
        }

        // El inventario se maneja en unidades enteras.
        if (!is_numeric($this->cantidad) || (float) $this->cantidad !== (float) (int) $this->cantidad || (int) $this->cantidad < 1) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'warning',
                'mensaje' => 'La cantidad debe ser un número entero mayor a cero.'
            ]);
            return;
        }
        $this->cantidad = (int) $this->cantidad;

        // Obtener lotes disponibles ordenados FIFO
        $lotesDisponibles = $this->obtenerLotesDisponibles($producto->id);


        if ($lotesDisponibles->isEmpty()) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => '❌ No hay stock disponible para este producto en la sucursal'
            ]);
            return;
        }

        // Calcular stock total disponible
        $stockTotal = $lotesDisponibles->sum('stock_disponible');

        // Validar que no exceda el stock total
        if ($this->cantidad > $stockTotal) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => "⚠️ Stock insuficiente. Solo hay {$stockTotal} unidades disponibles."
            ]);
            return;
        }

        // Verificar si ya existe el producto en el carrito
        $itemExistente = null;
        $itemIndex = null;

        foreach ($this->carrito as $index => $item) {
            if ($item['producto_id'] == $producto->id) {
                $itemExistente = $item;
                $itemIndex = $index;
                break;
            }
        }

        if ($itemExistente) {
            // Si ya existe, verificar que la nueva cantidad total no exceda el stock
            $nuevaCantidadTotal = $itemExistente['cantidad'] + $this->cantidad;

            if ($nuevaCantidadTotal > $stockTotal) {
                $disponible = $stockTotal - $itemExistente['cantidad'];
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'error',
                    'mensaje' => "⚠️ Solo puede agregar {$disponible} unidades más. Stock máximo: {$stockTotal}"
                ]);
                return;
            }

            // Actualizar la cantidad
            $this->actualizarCantidadCarrito($itemIndex, $nuevaCantidadTotal);
        } else {
            // Si no existe, crear nuevo item
            $cantidadRestante = $this->cantidad;
            $lotesParaVenta = [];
            $totalCantidadLotes = 0;
            $totalValorLotes = 0;

            // Recorrer lotes en orden FIFO
            foreach ($lotesDisponibles as $loteInfo) {
                if ($cantidadRestante <= 0) break;

                $cantidadATomar = min($cantidadRestante, $loteInfo->stock_disponible);


                if ($cantidadATomar > 0) {
                    $subtotalLote = $cantidadATomar * $loteInfo->precio_venta;
                    $lotesParaVenta[] = [
                        'lote_id' => $loteInfo->lote_id,
                        'lote_codigo' => $loteInfo->codigo_lote,
                        'cantidad' => $cantidadATomar,
                        'precio_unitario' => $loteInfo->precio_venta,
                        'subtotal' => $subtotalLote,
                        'created_at' => $loteInfo->created_at,
                    ];

                    $totalCantidadLotes += $cantidadATomar;
                    $totalValorLotes += $subtotalLote;
                    $cantidadRestante -= $cantidadATomar;
                }
            }

            // Calcular si se usan múltiples lotes
            $usarCPP = count($lotesParaVenta) > 1;

            if ($usarCPP) {
                // Calcular precio promedio ponderado (CPP)
                $precioPromedio = $totalValorLotes / $totalCantidadLotes;


                $this->carrito[] = [
                    'id' => uniqid(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'lote_id' => null,
                    'lote_codigo' => 'MÚLTIPLES LOTES (CPP)',
                    'cantidad' => $totalCantidadLotes,
                    'precio_unitario' => $precioPromedio,
                    'subtotal' => $totalValorLotes,
                    'created_at' => null,
                    'multi_lote' => true,
                    'lotes_usados' => $lotesParaVenta,
                    'precio_modificado' => false, // Agregar esta línea
                ];

                $this->dispatch('mostrar-alerta', [
                    'icono' => 'info',
                    'mensaje' => "📊 Precio Promedio Ponderado aplicado: Bs " . number_format($precioPromedio, 2) . " (basado en " . count($lotesParaVenta) . " lotes)"
                ]);

            } else {
                // Un solo lote
                $loteUnico = $lotesParaVenta[0];

                $this->carrito[] = [
                    'id' => uniqid(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'lote_id' => $loteUnico['lote_id'],
                    'lote_codigo' => $loteUnico['lote_codigo'],
                    'cantidad' => $loteUnico['cantidad'],
                    'precio_unitario' => $loteUnico['precio_unitario'],
                    'subtotal' => $loteUnico['subtotal'],
                    'created_at' => $loteUnico['created_at'],
                    'multi_lote' => false,
                    'lotes_usados' => null,
                    'precio_modificado' => false, // Agregar esta línea
                ];

                $this->dispatch('mostrar-alerta', [
                    'icono' => 'success',
                    'mensaje' => "✅ Agregado: {$loteUnico['cantidad']} unidad(es) de {$producto->nombre} - Lote: {$loteUnico['lote_codigo']} (Bs {$loteUnico['precio_unitario']})"
                ]);
            }

        }

        $this->calcularTotal();
        $this->reset(['productoId', 'busqueda_producto']);
        $this->cantidad = 1;
        $this->productos_filtrados = [];

        // Al final, después de calcularTotal()
        if ($this->tipo_venta == 'contado' && $this->metodo_pago == 'efectivo') {
            $this->efectivo_recibido = $this->totalVenta;
            $this->calcularCambio();
        }

        $this->dispatch('producto-agregado');
    }

    public function aplicarDescuento()
    {
        if (!Auth::user()->can('ventas.aplicar-descuento')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para aplicar descuentos.');
            return;
        }
        if ($this->subtotalVenta <= 0) {
            return;
        }

        $nuevoTotal = round((float) $this->nuevo_total, 2);
        if ($nuevoTotal < 0 || $nuevoTotal > $this->subtotalVenta) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El total con descuento no es válido.');
            return;
        }

        $this->descuento_monto = round($this->subtotalVenta - $nuevoTotal, 2);
        $this->descuento_porcentaje = round($this->descuento_monto / $this->subtotalVenta * 100, 2);
        $this->totalVenta = $nuevoTotal;
        $this->mostrar_modal_descuento = false;
        $this->calcularCambio();
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Descuento aplicado: Bs ' . number_format($this->descuento_monto, 2));
    }

    // Método para cargar bancas
    public function cargarBancas()
    {
        $this->bancas = \App\Models\Banca::activas()
            ->ordenadas()
            ->get();
    }

    // Ajusta los campos requeridos por cada forma de pago.
    public function updatedMetodoPago(): void
    {
        $requiereBanca = in_array($this->metodo_pago, ['qr', 'transferencia', 'tarjeta', 'mixto'], true);
        if (!$requiereBanca) {
            $this->banca_id = null;
            $this->banca_seleccionada = null;
            $this->mostrar_modal_bancas = false;
        } elseif (!$this->banca_id) {
            $this->mostrar_modal_bancas = true;
        }

        if ($this->metodo_pago === 'efectivo' && $this->tipo_venta === 'contado') {
            $this->efectivo_recibido = $this->totalVenta;
            $this->monto_efectivo_mixto = 0;
            $this->monto_qr_mixto = 0;
        } elseif ($this->metodo_pago === 'mixto' && $this->tipo_venta === 'contado') {
            $this->monto_efectivo_mixto = round($this->totalVenta / 2, 2);
            $this->monto_qr_mixto = round($this->totalVenta - $this->monto_efectivo_mixto, 2);
            $this->efectivo_recibido = $this->monto_efectivo_mixto;
        } else {
            $this->efectivo_recibido = null;
            $this->monto_efectivo_mixto = 0;
            $this->monto_qr_mixto = 0;
        }

        $this->calcularCambio();
        $this->guardarBorrador();
    }

    public function updatedMontoEfectivoMixto(): void
    {
        $this->monto_efectivo_mixto = round(min($this->totalVenta, max(0, (float) $this->monto_efectivo_mixto)), 2);
        $this->monto_qr_mixto = round($this->totalVenta - $this->monto_efectivo_mixto, 2);
        if ((float) $this->efectivo_recibido < $this->monto_efectivo_mixto) {
            $this->efectivo_recibido = $this->monto_efectivo_mixto;
        }
        $this->calcularCambio();
        $this->guardarBorrador();
    }

    // Seleccionar la cuenta activa que recibirá QR/transferencia/tarjeta.
    public function seleccionarBanca($id): void
    {
        $banca = Banca::query()->where('activa', true)->find($id);
        if (!$banca) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cuenta seleccionada ya no está disponible.');
            return;
        }

        $this->banca_id = (int) $banca->id;
        $this->banca_seleccionada = $banca;
        $this->mostrar_modal_bancas = false;
        $this->guardarBorrador();
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Cuenta seleccionada: ' . $banca->nombre);
    }

    // Limpiar banca seleccionada
    public function limpiarBanca(): void
    {
        $this->banca_id = null;
        $this->banca_seleccionada = null;
        $this->mostrar_modal_bancas = false;
        $this->guardarBorrador();
    }

    public function cargarProductos()
    {
        $productosIds = $this->obtenerIdsProductosConStock();

        if (empty($productosIds)) {
            $this->productos = collect();
            return;
        }

        $this->productos = Producto::where('estado', true)
            ->whereIn('id', $productosIds)
            ->orderBy('nombre')
            ->get();
    }


    public function cargarDetallesVenta()
    {
        $this->venta->load('detalles.producto', 'detalles.lote', 'cliente');
        $this->carrito = [];
        foreach ($this->venta->detalles as $detalle) {
            $this->carrito[] = [
                'id' => $detalle->id,
                'producto_id' => $detalle->producto_id,
                'producto_nombre' => $detalle->producto->nombre,
                'producto_codigo' => $detalle->producto->codigo,
                'lote_id' => $detalle->lote_id,
                'lote_codigo' => $detalle->lote->codigo_lote,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal' => $detalle->subtotal,
                'fecha_vencimiento' => $detalle->lote->fecha_vencimiento,
            ];
        }
        $this->totalVenta = $this->venta->total;
        $this->cliente_id = $this->venta->cliente_id;
        $this->tipo_venta = $this->venta->tipo;
    }

    private function cargarCotizacion($cotizacion)
    {
        $this->cliente_id = $cotizacion->cliente_id;
        $this->busqueda_cliente = $cotizacion->cliente?->nombre ?? '';
        $this->carrito = [];
        $faltantes = [];

        foreach ($cotizacion->detalles as $detalle) {
            $lotes = $this->obtenerLotesDisponibles($detalle->producto_id);
            $disponible = (int) $lotes->sum('stock_disponible');
            $requerido = (int) $detalle->cantidad;

            if ($disponible < $requerido) {
                $faltantes[] = "{$detalle->producto->nombre}: requiere {$requerido}, disponible {$disponible}";
                continue;
            }

            $restante = $requerido;
            $lotesUsados = [];
            foreach ($lotes as $lote) {
                if ($restante <= 0) break;
                $cantidadLote = min($restante, (int) $lote->stock_disponible);
                $lotesUsados[] = [
                    'lote_id' => $lote->lote_id,
                    'lote_codigo' => $lote->codigo_lote,
                    'cantidad' => $cantidadLote,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'subtotal' => round($cantidadLote * (float) $detalle->precio_unitario, 2),
                ];
                $restante -= $cantidadLote;
            }

            $primero = $lotesUsados[0];
            $this->carrito[] = [
                'id' => uniqid('', true),
                'producto_id' => $detalle->producto_id,
                'producto_nombre' => $detalle->producto->nombre,
                'producto_codigo' => $detalle->producto->codigo,
                'lote_id' => $primero['lote_id'],
                'lote_codigo' => $primero['lote_codigo'],
                'cantidad' => $requerido,
                'precio_unitario' => (float) $detalle->precio_unitario,
                'subtotal' => round($requerido * (float) $detalle->precio_unitario, 2),
                'created_at' => null,
                'multi_lote' => count($lotesUsados) > 1,
                'lotes_usados' => count($lotesUsados) > 1 ? $lotesUsados : null,
                'precio_modificado' => false,
            ];
        }

        if ($faltantes) {
            $this->carrito = [];
            $this->calcularTotal();
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se puede convertir la cotización: ' . implode(' | ', $faltantes));
            return;
        }

        $this->descuento_monto = min((float) $cotizacion->descuento, (float) $cotizacion->subtotal);
        $this->calcularTotal();
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Cotización cargada. El stock se reasignó por vencimiento y antigüedad; se descontará al confirmar.');
    }

    private function obtenerStockDisponibleLote($loteId)
    {
        if (!$this->sucursal_id) {
            return 0;
        }

        $inventario = InventarioSucuralLote::where('lote_id', $loteId)
            ->where('sucursal_id', $this->sucursal_id)
            ->first();

        return $inventario ? $inventario->cantidad_en_sucursal : 0;
    }

    public function updatedBusquedaProducto()
    {
        if (strlen($this->busqueda_producto) >= 2) {
            // Primero obtener IDs de productos con stock en la sucursal
            $productosConStock = $this->obtenerIdsProductosConStock();

            $this->productos_filtrados = Producto::where('estado', true)
                ->whereIn('id', $productosConStock)
                ->where(function($query) {
                    $query->where('nombre', 'LIKE', "%{$this->busqueda_producto}%")
                        ->orWhere('codigo', 'LIKE', "%{$this->busqueda_producto}%")
                        // ->orWhere('marca', 'LIKE', "%{$this->busqueda_producto}%");
                        ->orWhereHas('marca', function($q) {
                        $q->where('nombre', 'LIKE', "%{$this->busqueda_producto}%");
                    });
                })
                ->orderBy('nombre')
                ->limit(10)
                ->get();
        } else {
            $this->productos_filtrados = [];
        }
    }

    public function updatedBusquedaCliente()
    {
        if (strlen($this->busqueda_cliente) >= 2) {
            $this->clientes_filtrados = Cliente::where('activo', true)
                ->where(function($query) {
                    $query->where('nombre', 'LIKE', "%{$this->busqueda_cliente}%")
                        ->orWhere('nit', 'LIKE', "%{$this->busqueda_cliente}%")
                        ->orWhere('telefono', 'LIKE', "%{$this->busqueda_cliente}%");
                })
                ->orderBy('nombre')
                ->limit(10)
                ->get();
            $this->mostrar_resultados_clientes = true;
        } else {
            $this->clientes_filtrados = [];
            $this->mostrar_resultados_clientes = false;
        }
    }

    public function setProductoId($id)
    {
        $this->productoId = $id;
        $this->busqueda_producto = '';
        $this->productos_filtrados = [];
    }

    public function seleccionarProducto($id, $nombre = null)
    {
        $producto = Producto::query()->where('estado', true)->find($id);
        if (!$producto) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'El producto ya no está disponible.');
            return;
        }

        $this->productoId = $producto->id;
        $this->busqueda_producto = $producto->nombre;
        $this->productos_filtrados = [];
    }

    public function seleccionarCliente($id, $nombre = null)
    {
        $cliente = Cliente::query()->where('activo', true)->find($id);
        if (!$cliente) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'El cliente ya no está disponible.');
            return;
        }

        $this->cliente_id = $cliente->id;
        $this->busqueda_cliente = $cliente->nombre;
        $this->mostrar_resultados_clientes = false;
    }

    public function limpiarCliente()
    {
        $this->cliente_id = null;
        $this->busqueda_cliente = '';
    }

    private function limpiarItemsDuplicados()
{
    $itemsPorProducto = [];

    foreach ($this->carrito as $item) {
        $productoId = $item['producto_id'];

        if (!isset($itemsPorProducto[$productoId])) {
            $itemsPorProducto[$productoId] = $item;
        } else {
            // Si ya existe, sumar cantidades (esto no debería pasar con la nueva lógica)
            $itemsPorProducto[$productoId]['cantidad'] += $item['cantidad'];
            $itemsPorProducto[$productoId]['subtotal'] += $item['subtotal'];
            if (!$itemsPorProducto[$productoId]['multi_lote'] && !$item['multi_lote']) {
                // Si ambos son de un solo lote pero diferentes, calcular precio promedio
                $itemsPorProducto[$productoId]['precio_unitario'] = $itemsPorProducto[$productoId]['subtotal'] / $itemsPorProducto[$productoId]['cantidad'];
                $itemsPorProducto[$productoId]['multi_lote'] = true;
                $itemsPorProducto[$productoId]['lote_codigo'] = 'MÚLTIPLES LOTES (CPP)';
            }
        }
    }

    $this->carrito = array_values($itemsPorProducto);
}

    public function abrirModalCliente()
{
    if (!Auth::user()->can('clientes.store')) {
        $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para crear clientes.');
        return;
    }

    if (!empty($this->busqueda_cliente)) {

        if (is_numeric($this->busqueda_cliente)) {
            // Es número → va al NIT
            $this->nuevo_cliente_nit = $this->busqueda_cliente;
            $this->nuevo_cliente_nombre = '';
        } else {
            // Es texto → va al nombre
            $this->nuevo_cliente_nombre = $this->busqueda_cliente;
            $this->nuevo_cliente_nit = '';
        }

    } else {
        $this->nuevo_cliente_nombre = '';
        $this->nuevo_cliente_nit = '';
    }

    $this->nuevo_cliente_telefono = '';
    $this->nuevo_cliente_email = '';
    $this->mostrar_modal_cliente = true;
}

    public function cerrarModalCliente()
    {
        $this->mostrar_modal_cliente = false;
    }

    public function guardarClienteRapido()
    {
        if (!Auth::user()->can('clientes.store')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para crear clientes.');
            return;
        }

        $this->validate([
            'nuevo_cliente_nombre' => 'required|string|max:150',
            'nuevo_cliente_nit' => 'nullable|string|max:50|unique:clientes,nit',
            'nuevo_cliente_telefono' => 'nullable|string|max:30',
            'nuevo_cliente_email' => 'nullable|email|max:150',
        ]);

        $cliente = Cliente::create([
            'nombre' => strtoupper($this->nuevo_cliente_nombre),
            'nit' => $this->nuevo_cliente_nit ?: null,
            'telefono' => $this->nuevo_cliente_telefono,
            'email' => $this->nuevo_cliente_email,
            'tipo' => 'regular',
            'activo' => true,
        ]);

        $this->clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        $this->cliente_id = $cliente->id;
        $this->busqueda_cliente = $cliente->nombre;
        $this->mostrar_modal_cliente = false;

        $this->dispatch('mostrar-alerta', [
            'icono' => 'success',
            'mensaje' => 'Cliente creado exitosamente'
        ]);
    }

private function obtenerCantidadEnCarrito($productoId)
{
    $cantidad = 0;
    foreach ($this->carrito as $item) {
        if ($item['producto_id'] == $productoId) {
            $cantidad += $item['cantidad'];
        }
    }
    return $cantidad;
}


    public function eliminarDelCarrito($index)
    {
        if ($this->venta && $this->venta->estado != 'pendiente') {
            return;
        }

        unset($this->carrito[$index]);
        $this->carrito = array_values($this->carrito);
        $this->calcularTotal();
    }

    public function vaciarCarrito()
    {
        if ($this->venta && $this->venta->estado != 'pendiente') {
            return;
        }

        $this->carrito = [];
        $this->calcularTotal();
    }

    public function descartarBorrador(): void
    {
        if ($this->venta) {
            return;
        }

        session()->forget($this->claveBorrador());
        $this->reset([
            'carrito', 'subtotalVenta', 'totalVenta', 'descuento_monto', 'descuento_porcentaje',
            'cliente_id', 'busqueda_cliente', 'productoId', 'busqueda_producto', 'productos_filtrados',
            'cotizacion_id', 'efectivo_recibido', 'cambio', 'banca_id', 'banca_seleccionada',
            'monto_efectivo_mixto', 'monto_qr_mixto', 'lugar_entrega', 'observaciones_adicionales',
        ]);
        $this->cantidad = 1;
        $this->metodo_pago = 'efectivo';
        $this->tipo_venta = 'contado';
        $this->plazo_entrega = 5;
        $this->validez_economica = 48;
        $this->borrador_restaurado = false;
        $this->dispatch('mostrar-alerta', icono: 'info', mensaje: 'El borrador de venta fue descartado.');
    }

    public function calcularTotal()
    {
        $this->subtotalVenta = round((float) collect($this->carrito)->sum('subtotal'), 2);
        $this->descuento_monto = min(max(0, (float) $this->descuento_monto), $this->subtotalVenta);
        $this->totalVenta = round($this->subtotalVenta - $this->descuento_monto, 2);
        $this->nuevo_total = $this->totalVenta;
        $this->descuento_porcentaje = $this->subtotalVenta > 0
            ? round($this->descuento_monto / $this->subtotalVenta * 100, 2)
            : 0;
        if ($this->metodo_pago === 'mixto') {
            $this->monto_efectivo_mixto = round(min($this->totalVenta, max(0, (float) $this->monto_efectivo_mixto)), 2);
            $this->monto_qr_mixto = round($this->totalVenta - $this->monto_efectivo_mixto, 2);
        }
        $this->calcularCambio();
        $this->guardarBorrador();
    }

    public function calcularCambio()
    {
        $efectivoRequerido = $this->metodo_pago === 'mixto'
            ? round((float) $this->monto_efectivo_mixto, 2)
            : ($this->metodo_pago === 'efectivo' ? round((float) $this->totalVenta, 2) : 0);

        if ($efectivoRequerido > 0 && $this->efectivo_recibido !== null && $this->efectivo_recibido !== '') {
            $this->cambio = max(0, round((float) $this->efectivo_recibido - $efectivoRequerido, 2));
            // $raw = (float)$this->efectivo_recibido - (float)$this->totalVenta;
            // $cambioFloor = floor($raw * 10) / 10;
            // $this->cambio = max(0, $cambioFloor);
        } else {
            $this->cambio = 0;
        }
    }

    public function updatedEfectivoRecibido(): void
    {
        $this->calcularCambio();
        $this->guardarBorrador();
    }

    public function updatedTipoVenta(): void
    {
        $this->banca_id = null;
        $this->banca_seleccionada = null;
        $this->mostrar_modal_bancas = false;
        $this->monto_efectivo_mixto = 0;
        $this->monto_qr_mixto = 0;

        if ($this->tipo_venta === 'contado') {
            $this->metodo_pago = 'efectivo';
            $this->efectivo_recibido = $this->totalVenta;
        } else {
            $this->efectivo_recibido = null;
        }

        $this->calcularCambio();
        $this->guardarBorrador();
    }

    public function confirmarVenta()
    {
        if (!Auth::user()->can('ventas.create')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar ventas.');
            return;
        }

        $this->calcularTotal();
        if (empty($this->carrito) || !$this->sucursal_id) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Agregue productos para continuar.');
            return;
        }
        if (!Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede operar en esta sucursal.');
            return;
        }
        if ($this->descuento_monto > 0 && !Auth::user()->can('ventas.aplicar-descuento')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para aplicar descuentos.');
            return;
        }
        if (collect($this->carrito)->contains(fn ($item) => !empty($item['precio_modificado'])) && !Auth::user()->can('ventas.modificar-precio')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para modificar precios.');
            return;
        }
        if (!in_array($this->tipo_venta, ['contado', 'credito'], true) || !in_array($this->metodo_pago, ['efectivo', 'qr', 'transferencia', 'tarjeta', 'mixto'], true)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Tipo de venta o método de pago no válido.');
            return;
        }
        if (!Caja::hayCajaAbierta($this->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Debe abrir la caja antes de registrar ventas.');
            return;
        }
        if ($this->tipo_venta === 'contado' && $this->metodo_pago === 'efectivo' && (float) $this->efectivo_recibido < $this->totalVenta) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El efectivo recibido es menor al total.');
            return;
        }
        if ($this->tipo_venta === 'contado' && $this->metodo_pago === 'mixto') {
            $efectivo = round((float) $this->monto_efectivo_mixto, 2);
            $qr = round((float) $this->monto_qr_mixto, 2);
            if ($efectivo <= 0 || $qr <= 0 || abs(($efectivo + $qr) - $this->totalVenta) > 0.01) {
                $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'En pago mixto, efectivo y QR deben ser mayores a cero y sumar exactamente el total.');
                return;
            }
            if (round((float) $this->efectivo_recibido, 2) < $efectivo) {
                $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El efectivo recibido es menor a la parte pagada en efectivo.');
                return;
            }
        }
        if ($this->tipo_venta === 'credito' && !$this->cliente_id) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Las ventas a crédito requieren un cliente.');
            return;
        }
        if ($this->tipo_venta === 'contado' && in_array($this->metodo_pago, ['qr', 'transferencia', 'tarjeta', 'mixto'], true) && !$this->banca_id) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Seleccione una cuenta bancaria activa.');
            return;
        }
        if ($this->tipo_venta === 'contado' && in_array($this->metodo_pago, ['qr', 'mixto'], true)) {
            $bancaQr = Banca::query()->where('activa', true)->find($this->banca_id);
            if (!$bancaQr || !$bancaQr->qr_code) {
                $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'La cuenta seleccionada debe tener una imagen QR registrada.');
                return;
            }
            $this->banca_seleccionada = $bancaQr;
        }

        $this->dispatch('mostrar-confirmacion-venta', total: $this->totalVenta, tipo: $this->tipo_venta,
            metodo_pago: $this->metodo_pago, cambio: $this->cambio, cliente_id: $this->cliente_id);
    }

    private function obtenerLotesDisponibles($productoId)
    {
        return InventarioSucuralLote::query()
            ->where('sucursal_id', $this->sucursal_id)
            ->where('cantidad_en_sucursal', '>', 0)
            ->whereHas('lote', function ($query) use ($productoId) {
                $query->where('producto_id', $productoId)
                    ->where('estado', true)
                    ->where('cantidad_actual', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('fecha_vencimiento')->orWhereDate('fecha_vencimiento', '>=', today());
                    });
            })
            ->with('lote.producto')
            ->get()
            ->sortBy(function ($inventario) {
                $vence = $inventario->lote->fecha_vencimiento
                    ? $inventario->lote->fecha_vencimiento->format('Y-m-d') : '9999-12-31';
                return $vence . '|' . optional($inventario->lote->fecha_entrada)->format('Y-m-d') . '|' . str_pad($inventario->lote_id, 12, '0', STR_PAD_LEFT);
            })
            ->map(fn ($inventario) => (object) [
                'lote_id' => $inventario->lote_id,
                'codigo_lote' => $inventario->lote->codigo_lote,
                'created_at' => $inventario->lote->fecha_vencimiento ?? $inventario->lote->fecha_entrada,
                'precio_venta' => $inventario->lote->precio_venta ?? $inventario->lote->producto->precio_venta ?? 0,
                'stock_disponible' => min((int) $inventario->cantidad_en_sucursal, (int) $inventario->lote->cantidad_actual),
            ])->values();
    }

    /**
 * Obtener el stock máximo disponible para un producto
 */
    public function obtenerStockMaximoProducto($productoId)
    {
        $lotesDisponibles = $this->obtenerLotesDisponibles($productoId);
        return $lotesDisponibles->sum('stock_disponible');
    }

    public function procesarVenta($data = null)
    {
        try {
            $venta = DB::transaction(function () {
                $this->calcularTotal();
                $user = Auth::user();
                if (!$user || !$user->tieneSucursalOperativa()) {
                    throw new \RuntimeException('Su usuario no tiene una sucursal activa asignada.');
                }
                $sucursalId = (int) $user->sucursal_id;
                if ((int) $this->sucursal_id !== $sucursalId) {
                    throw new \RuntimeException('La sucursal de la venta no coincide con la sucursal de su usuario.');
                }
                $this->sucursal_id = $sucursalId;

                if (!$user->can('ventas.create') || !$user->puedeOperarSucursal($sucursalId)) {
                    throw new \RuntimeException('Operación de venta no autorizada.');
                }
                if (!in_array($this->tipo_venta, ['contado', 'credito'], true)
                    || !in_array($this->metodo_pago, ['efectivo', 'qr', 'transferencia', 'tarjeta', 'mixto'], true)) {
                    throw new \RuntimeException('Tipo de venta o método de pago no válido.');
                }
                if (empty($this->carrito) || $this->totalVenta <= 0) {
                    throw new \RuntimeException('La venta no contiene productos válidos.');
                }
                if ($this->tipo_venta === 'contado' && $this->metodo_pago === 'efectivo'
                    && round((float) $this->efectivo_recibido, 2) < $this->totalVenta) {
                    throw new \RuntimeException('El efectivo recibido es menor al total de la venta.');
                }
                if ($this->tipo_venta === 'contado' && $this->metodo_pago === 'mixto') {
                    $efectivoMixto = round((float) $this->monto_efectivo_mixto, 2);
                    $qrMixto = round((float) $this->monto_qr_mixto, 2);
                    if ($efectivoMixto <= 0 || $qrMixto <= 0 || abs(($efectivoMixto + $qrMixto) - $this->totalVenta) > 0.01) {
                        throw new \RuntimeException('La distribución del pago mixto no coincide con el total.');
                    }
                    if (round((float) $this->efectivo_recibido, 2) < $efectivoMixto) {
                        throw new \RuntimeException('El efectivo recibido es menor a la parte en efectivo.');
                    }
                }
                if ($this->tipo_venta === 'credito' && !$this->cliente_id) {
                    throw new \RuntimeException('Las ventas a crédito requieren un cliente.');
                }
                $this->validarTotalesCarrito();
                if ($this->descuento_monto > 0 && !$user->can('ventas.aplicar-descuento')) {
                    throw new \RuntimeException('No tiene permiso para aplicar descuentos.');
                }
                if (collect($this->carrito)->contains(fn ($item) => !empty($item['precio_modificado'])) && !$user->can('ventas.modificar-precio')) {
                    throw new \RuntimeException('No tiene permiso para modificar precios.');
                }

                $sucursal = Sucursal::query()->where('activa', true)->lockForUpdate()->find($this->sucursal_id);
                if (!$sucursal) {
                    throw new \RuntimeException('La sucursal asignada al usuario no está activa.');
                }

                $caja = Caja::query()->where('sucursal_id', $sucursal->id)
                    ->where('estado', 'abierta')->lockForUpdate()->latest('fecha_apertura')->first();
                if (!$caja) {
                    throw new \RuntimeException('La caja fue cerrada antes de confirmar la venta.');
                }

                $cotizacion = null;
                if ($this->cotizacion_id) {
                    $cotizacion = Cotizacion::query()->lockForUpdate()->findOrFail($this->cotizacion_id);
                    if ($cotizacion->estado !== 'activa' || ($cotizacion->valida_hasta && $cotizacion->valida_hasta->isPast())) {
                        throw new \RuntimeException('La cotización ya no está disponible.');
                    }
                    if ((int) $cotizacion->sucursal_id !== (int) $this->sucursal_id) {
                        throw new \RuntimeException('La cotización pertenece a otra sucursal.');
                    }
                    if ($cotizacion->venta()->where('estado', '!=', 'anulada')->exists()) {
                        throw new \RuntimeException('La cotización ya fue convertida en otra venta.');
                    }
                }

                $cliente = $this->cliente_id
                    ? Cliente::query()->lockForUpdate()->findOrFail($this->cliente_id)
                    : null;
                if ($this->tipo_venta === 'credito') {
                    if (!$cliente) {
                        throw new \RuntimeException('Las ventas a crédito requieren un cliente.');
                    }
                    $nuevoSaldo = (float) $cliente->saldo_pendiente + $this->totalVenta;
                    if ($cliente->tipo === 'credito' && (float) $cliente->limite_credito > 0 && $nuevoSaldo > (float) $cliente->limite_credito) {
                        throw new \RuntimeException('La venta excede el límite de crédito del cliente.');
                    }
                }

                $banca = null;
                if ($this->tipo_venta === 'contado' && in_array($this->metodo_pago, ['qr', 'transferencia', 'tarjeta', 'mixto'], true)) {
                    $banca = Banca::query()->where('activa', true)->lockForUpdate()->find($this->banca_id);
                    if (!$banca) {
                        throw new \RuntimeException('La cuenta bancaria seleccionada no está activa.');
                    }
                    if (in_array($this->metodo_pago, ['qr', 'mixto'], true) && !$banca->qr_code) {
                        throw new \RuntimeException('La cuenta bancaria seleccionada no tiene una imagen QR registrada.');
                    }
                }

                $stockBloqueado = $this->bloquearYValidarStockVenta();
                $venta = Venta::create([
                    'codigo' => Venta::generarCodigo(),
                    'sucursal_id' => $this->sucursal_id,
                    'user_id' => $user->id,
                    'caja_id' => $caja->id,
                    'cliente_id' => $this->cliente_id,
                    'cotizacion_id' => $cotizacion?->id,
                    'fecha' => today(),
                    'tipo' => $this->tipo_venta,
                    'subtotal' => $this->subtotalVenta,
                    'descuento' => $this->descuento_monto,
                    'total' => $this->totalVenta,
                    'pagado' => $this->tipo_venta === 'contado' ? $this->totalVenta : 0,
                    'pendiente' => $this->tipo_venta === 'credito' ? $this->totalVenta : 0,
                    'estado' => $this->tipo_venta === 'contado' ? 'pagada' : 'pendiente',
                    'observaciones' => json_encode([
                        'incluye_impuesto' => $this->incluye_impuesto,
                        'forma_pago' => $this->forma_pago,
                        'lugar_entrega' => $this->lugar_entrega,
                        'plazo_entrega' => $this->plazo_entrega,
                        'validez_economica' => $this->validez_economica,
                        'observaciones_adicionales' => $this->observaciones_adicionales,
                        'fecha_venta' => now()->format('d/m/Y H:i'),
                        'vendedor' => $user->name,
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $inventario = app(InventarioService::class);
                foreach ($this->carrito as $item) {
                    $lotes = !empty($item['multi_lote']) ? ($item['lotes_usados'] ?? []) : [[
                        'lote_id' => $item['lote_id'], 'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'], 'subtotal' => $item['subtotal'],
                    ]];
                    foreach ($lotes as $loteInfo) {
                        $lote = $stockBloqueado[$loteInfo['lote_id']]['lote'];
                        $venta->detalles()->create([
                            'producto_id' => $item['producto_id'],
                            'lote_id' => $loteInfo['lote_id'],
                            'cantidad' => (int) $loteInfo['cantidad'],
                            'precio_unitario' => round((float) $loteInfo['precio_unitario'], 2),
                            'costo_unitario' => round((float) $lote->precio_compra, 2),
                            'subtotal' => round((float) $loteInfo['subtotal'], 2),
                        ]);
                        $inventario->disminuir((int) $loteInfo['lote_id'], (int) $this->sucursal_id,
                            (int) $loteInfo['cantidad'], $user->id, Venta::class, $venta->id, "Venta {$venta->codigo}");
                    }
                }

                if ($this->tipo_venta === 'contado') {
                    $partesPago = $this->metodo_pago === 'mixto'
                        ? [
                            ['metodo' => 'efectivo', 'monto' => round((float) $this->monto_efectivo_mixto, 2), 'banca' => null],
                            ['metodo' => 'qr', 'monto' => round((float) $this->monto_qr_mixto, 2), 'banca' => $banca],
                        ]
                        : [['metodo' => $this->metodo_pago, 'monto' => $this->totalVenta, 'banca' => $banca]];

                    foreach ($partesPago as $parte) {
                        Pago::create([
                            'venta_id' => $venta->id,
                            'user_id' => $user->id,
                            'caja_id' => $caja->id,
                            'banca_id' => $parte['banca']?->id,
                            'fecha' => today(),
                            'monto' => $parte['monto'],
                            'metodo_pago' => $parte['metodo'],
                            'referencia' => $parte['banca'] ? 'Pago a ' . $parte['banca']->nombre : null,
                            'observaciones' => $this->metodo_pago === 'mixto' ? 'Parte de pago mixto' : 'Pago completo al registrar la venta',
                        ]);

                        if ($parte['banca']) {
                            $parte['banca']->registrarMovimiento('carga', $parte['monto'], $user->id, $caja->id,
                                "Venta {$venta->codigo}", 'Cobro de venta');
                        }

                        MovimientoCaja::create([
                            'caja_id' => $caja->id,
                            'venta_id' => $venta->id,
                            'user_id' => $user->id,
                            'tipo' => 'ingreso',
                            'monto' => $parte['monto'],
                            'metodo_pago' => $parte['metodo'],
                            'concepto' => $this->metodo_pago === 'mixto' ? "Venta {$venta->codigo} (pago mixto)" : "Venta {$venta->codigo}",
                            'fecha' => now(),
                        ]);
                    }

                    if (collect($partesPago)->contains(fn ($parte) => $parte['metodo'] === 'efectivo')) {
                        $caja->monto_esperado = $caja->calcularEfectivoEsperado();
                        $caja->save();
                    }
                } elseif ($cliente) {
                    $cliente->saldo_pendiente = $cliente->ventas()->where('estado', '!=', 'anulada')->sum('pendiente');
                    $cliente->save();
                }

                if ($cotizacion) {
                    $cotizacion->update(['estado' => 'convertida']);
                }
                return $venta;
            }, 3);

            session()->forget($this->claveBorrador());

            $this->dispatch('venta-finalizada', ventaId: $venta->id,
                notaUrl: route('ventas.nota-pdf', $venta->id),
                descargarUrl: route('ventas.descargar-nota', $venta->id),
                cambio: $this->cambio, efectivo_recibido: $this->efectivo_recibido);

            $this->reset(['carrito', 'subtotalVenta', 'totalVenta', 'descuento_monto', 'descuento_porcentaje',
                'cotizacion_id', 'efectivo_recibido', 'cambio', 'busqueda_cliente', 'cliente_id',
                'lugar_entrega', 'observaciones_adicionales', 'banca_id', 'banca_seleccionada',
                'monto_efectivo_mixto', 'monto_qr_mixto']);
            $this->plazo_entrega = 5;
            $this->validez_economica = 48;
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se registró la venta: ' . $e->getMessage());
        }
    }

    private function validarTotalesCarrito(): void
    {
        $subtotalCalculado = 0.0;

        foreach ($this->carrito as $item) {
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = round((float) ($item['precio_unitario'] ?? 0), 2);
            if ($cantidad <= 0 || $precio <= 0) {
                throw new \RuntimeException('La venta contiene cantidades o precios no válidos.');
            }

            $lotes = !empty($item['multi_lote']) ? ($item['lotes_usados'] ?? []) : [[
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $item['subtotal'] ?? 0,
            ]];
            if (empty($lotes)) {
                throw new \RuntimeException('La distribución por lotes está incompleta.');
            }

            $cantidadLotes = 0;
            $subtotalLotes = 0.0;
            foreach ($lotes as $lote) {
                $cantidadLote = (int) ($lote['cantidad'] ?? 0);
                $precioLote = round((float) ($lote['precio_unitario'] ?? 0), 2);
                $subtotalLote = round((float) ($lote['subtotal'] ?? 0), 2);
                if ($cantidadLote <= 0 || $precioLote <= 0 || abs($subtotalLote - round($cantidadLote * $precioLote, 2)) > 0.01) {
                    throw new \RuntimeException('Los importes del carrito fueron alterados o están desactualizados.');
                }
                $cantidadLotes += $cantidadLote;
                $subtotalLotes += $subtotalLote;
            }

            if ($cantidadLotes !== $cantidad || abs(round($subtotalLotes, 2) - round((float) $item['subtotal'], 2)) > 0.01) {
                throw new \RuntimeException('La distribución de cantidades entre lotes no coincide con el producto.');
            }
            $subtotalCalculado += $subtotalLotes;
        }

        if (abs(round($subtotalCalculado, 2) - round((float) $this->subtotalVenta, 2)) > 0.01
            || abs(round($this->subtotalVenta - $this->descuento_monto, 2) - round((float) $this->totalVenta, 2)) > 0.01) {
            throw new \RuntimeException('Los totales de la venta no son consistentes.');
        }
    }

    private function bloquearYValidarStockVenta(): array
    {
        $requerimientos = [];

        foreach ($this->carrito as $item) {
            $lotes = !empty($item['multi_lote']) ? ($item['lotes_usados'] ?? []) : [[
                'lote_id' => $item['lote_id'] ?? null,
                'cantidad' => $item['cantidad'] ?? 0,
                'lote_codigo' => $item['lote_codigo'] ?? '',
            ]];

            foreach ($lotes as $loteInfo) {
                $loteId = (int) ($loteInfo['lote_id'] ?? 0);
                $cantidadRaw = $loteInfo['cantidad'] ?? 0;
                $cantidad = (int) $cantidadRaw;

                if ($loteId <= 0 || !is_numeric($cantidadRaw) || (float) $cantidadRaw !== (float) $cantidad || $cantidad <= 0) {
                    throw new \Exception('La venta contiene un lote o una cantidad inválida.');
                }

                if (!isset($requerimientos[$loteId])) {
                    $requerimientos[$loteId] = [
                        'cantidad' => 0,
                        'producto_id' => (int) ($item['producto_id'] ?? 0),
                        'producto_nombre' => $item['producto_nombre'] ?? 'Producto',
                    ];
                }

                if ($requerimientos[$loteId]['producto_id'] !== (int) ($item['producto_id'] ?? 0)) {
                    throw new \Exception('Un lote fue asociado a productos distintos en la misma venta.');
                }

                $requerimientos[$loteId]['cantidad'] += $cantidad;
            }
        }

        ksort($requerimientos);
        $bloqueados = [];

        foreach ($requerimientos as $loteId => $requerimiento) {
            $lote = Lote::query()->lockForUpdate()->find($loteId);
            if (!$lote || (int) $lote->producto_id !== $requerimiento['producto_id']) {
                throw new \Exception('El lote seleccionado no corresponde a ' . $requerimiento['producto_nombre'] . '.');
            }

            if (!$lote->estado || ($lote->fecha_vencimiento && $lote->fecha_vencimiento->lt(today()))) {
                throw new \RuntimeException('El lote ' . $lote->codigo_lote . ' está inactivo o vencido.');
            }

            $inventario = InventarioSucuralLote::query()
                ->where('lote_id', $loteId)
                ->where('sucursal_id', $this->sucursal_id)
                ->lockForUpdate()
                ->first();

            $cantidad = $requerimiento['cantidad'];
            if (!$inventario || $inventario->cantidad_en_sucursal < $cantidad || $lote->cantidad_actual < $cantidad) {
                $disponible = $inventario?->cantidad_en_sucursal ?? 0;
                throw new \Exception(
                    'Stock insuficiente para ' . $requerimiento['producto_nombre'] .
                    ' (lote ' . $lote->codigo_lote . '). Disponible: ' . $disponible .
                    ', requerido: ' . $cantidad
                );
            }

            $bloqueados[$loteId] = [
                'lote' => $lote,
                'inventario' => $inventario,
            ];
        }

        return $bloqueados;
    }

    public function render()
    {
        return view('livewire.admin.ventas.items-venta');
    }

    public function updatedSucursalId(): void
    {
        $sucursalAsignada = (int) Auth::user()->sucursal_id;
        if ((int) $this->sucursal_id !== $sucursalAsignada) {
            $this->sucursal_id = $sucursalAsignada;
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La sucursal de la venta se obtiene de su usuario y no puede cambiarse.');
        }
    }

    private function obtenerIdsProductosConStock()
    {
        $query = InventarioSucuralLote::where('cantidad_en_sucursal', '>', 0)
            ->whereHas('lote', function($query) {
                $query->where('estado', true)
                    ->where('cantidad_actual', '>', 0)
                    ->where(function ($subquery) {
                        $subquery->whereNull('fecha_vencimiento')
                            ->orWhereDate('fecha_vencimiento', '>=', today());
                    });
            });

        if ($this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }

        return $query->with('lote.producto')
            ->get()
            ->pluck('lote.producto.id')
            ->unique()
            ->values()
            ->toArray();
    }


    public function cerrarModalDescuento()
    {
        $this->mostrar_modal_descuento = false;
    }

    public function updatedDescuentoPorcentaje()
    {
        $porcentaje = min(100, max(0, (float) $this->descuento_porcentaje));
        $this->descuento_porcentaje = $porcentaje;
        $this->descuento_monto = round($this->subtotalVenta * $porcentaje / 100, 2);
        $this->nuevo_total = round($this->subtotalVenta - $this->descuento_monto, 2);
    }

    public function updatedDescuentoMonto()
    {
        $this->descuento_monto = min($this->subtotalVenta, max(0, (float) $this->descuento_monto));
        $this->descuento_porcentaje = $this->subtotalVenta > 0 ? round($this->descuento_monto / $this->subtotalVenta * 100, 2) : 0;
        $this->nuevo_total = round($this->subtotalVenta - $this->descuento_monto, 2);
    }

    public function updatedNuevoTotal()
    {
        $this->nuevo_total = min($this->subtotalVenta, max(0, (float) $this->nuevo_total));
        $this->descuento_monto = round($this->subtotalVenta - $this->nuevo_total, 2);
        $this->descuento_porcentaje = $this->subtotalVenta > 0 ? round($this->descuento_monto / $this->subtotalVenta * 100, 2) : 0;
    }

    //EDITAR CLIENTE
    public function editarCliente()
    {
        if (!Auth::user()->can('clientes.update')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para editar clientes.');
            return;
        }

        if (!$this->cliente_id) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'warning',
                'mensaje' => 'No hay cliente seleccionado para editar'
            ]);
            return;
        }

        $cliente = Cliente::find($this->cliente_id);
        if (!$cliente) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => 'Cliente no encontrado'
            ]);
            return;
        }

        $this->cliente_edit_id = $cliente->id;
        $this->cliente_edit_nombre = $cliente->nombre;
        $this->cliente_edit_nit = $cliente->nit;
        $this->cliente_edit_telefono = $cliente->telefono;
        $this->cliente_edit_email = $cliente->email;
        $this->mostrar_modal_editar_cliente = true;
    }

    public function cerrarModalEditarCliente()
    {
        $this->mostrar_modal_editar_cliente = false;
        $this->reset(['cliente_edit_id', 'cliente_edit_nombre', 'cliente_edit_nit', 'cliente_edit_telefono', 'cliente_edit_email']);
    }

    public function actualizarCliente()
    {
        if (!Auth::user()->can('clientes.update')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para editar clientes.');
            return;
        }

        $this->validate([
            'cliente_edit_nombre' => 'required|string|max:150',
            'cliente_edit_nit' => 'nullable|string|max:50|unique:clientes,nit,' . $this->cliente_edit_id,
            'cliente_edit_telefono' => 'nullable|string|max:30',
            'cliente_edit_email' => 'nullable|email|max:150',
        ]);

        $cliente = Cliente::find($this->cliente_edit_id);
        if (!$cliente) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => 'Cliente no encontrado'
            ]);
            return;
        }

        $cliente->update([
            'nombre' => strtoupper($this->cliente_edit_nombre),
            'nit' => $this->cliente_edit_nit ?: null,
            'telefono' => $this->cliente_edit_telefono,
            'email' => $this->cliente_edit_email,
        ]);

        // Actualizar la búsqueda del cliente
        $this->busqueda_cliente = $cliente->nombre;

        $this->cerrarModalEditarCliente();

        $this->dispatch('mostrar-alerta', [
            'icono' => 'success',
            'mensaje' => 'Cliente actualizado exitosamente'
        ]);
    }

    public function seleccionarProductoYAgregar($id, $nombre = null)
{
    $producto = Producto::query()->where('estado', true)->find($id);
    if (!$producto) {
        $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'El producto ya no está disponible.');
        return;
    }

    // Primero selecciona el producto usando datos confiables del servidor.
    $this->productoId = $producto->id;
    $this->busqueda_producto = $producto->nombre;
    $this->productos_filtrados = [];

    // Luego lo agrega al carrito
    $this->agregarAlCarrito();
}

    public function updatedTotalVenta()
    {
        $this->calcularCambio();

        // Autocompletar efectivo recibido si es contado en efectivo
        if ($this->tipo_venta == 'contado' && $this->metodo_pago == 'efectivo') {
            $this->efectivo_recibido = $this->totalVenta;
            $this->calcularCambio();
        }
    }

    public function verificarCajaAbierta()
    {
        if (!$this->sucursal_id) {
            return false;
        }

        return Caja::getCajaAbierta($this->sucursal_id) !== null;
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'cliente_id', 'busqueda_cliente', 'tipo_venta', 'efectivo_recibido',
            'descuento_monto', 'descuento_porcentaje', 'lugar_entrega',
            'observaciones_adicionales', 'incluye_impuesto', 'forma_pago',
            'plazo_entrega', 'validez_economica',
        ], true)) {
            $this->guardarBorrador();
        }
    }

    private function claveBorrador(): string
    {
        return 'venta_borrador_usuario_' . Auth::id();
    }

    private function guardarBorrador(): void
    {
        if (!$this->borrador_inicializado || $this->venta) {
            return;
        }

        if (empty($this->carrito) && !$this->cliente_id && !$this->busqueda_cliente) {
            session()->forget($this->claveBorrador());
            return;
        }

        session()->put($this->claveBorrador(), [
            'sucursal_id' => $this->sucursal_id,
            'cliente_id' => $this->cliente_id,
            'busqueda_cliente' => $this->busqueda_cliente,
            'carrito' => $this->carrito,
            'subtotalVenta' => $this->subtotalVenta,
            'totalVenta' => $this->totalVenta,
            'descuento_monto' => $this->descuento_monto,
            'descuento_porcentaje' => $this->descuento_porcentaje,
            'metodo_pago' => $this->metodo_pago,
            'tipo_venta' => $this->tipo_venta,
            'efectivo_recibido' => $this->efectivo_recibido,
            'monto_efectivo_mixto' => $this->monto_efectivo_mixto,
            'monto_qr_mixto' => $this->monto_qr_mixto,
            'banca_id' => $this->banca_id,
            'lugar_entrega' => $this->lugar_entrega,
            'observaciones_adicionales' => $this->observaciones_adicionales,
            'incluye_impuesto' => $this->incluye_impuesto,
            'forma_pago' => $this->forma_pago,
            'plazo_entrega' => $this->plazo_entrega,
            'validez_economica' => $this->validez_economica,
            'guardado_en' => now()->toIso8601String(),
        ]);
    }

    private function restaurarBorrador(): void
    {
        $datos = session()->get($this->claveBorrador());
        if (!is_array($datos) || empty($datos['carrito'])) {
            return;
        }

        $sucursalId = (int) ($datos['sucursal_id'] ?? 0);
        if (!$sucursalId || $sucursalId !== (int) Auth::user()->sucursal_id || !Auth::user()->puedeOperarSucursal($sucursalId)) {
            session()->forget($this->claveBorrador());
            return;
        }

        foreach ([
            'cliente_id', 'busqueda_cliente', 'carrito', 'subtotalVenta',
            'totalVenta', 'descuento_monto', 'descuento_porcentaje', 'metodo_pago',
            'tipo_venta', 'efectivo_recibido', 'monto_efectivo_mixto', 'monto_qr_mixto',
            'banca_id', 'lugar_entrega', 'observaciones_adicionales', 'incluye_impuesto',
            'forma_pago', 'plazo_entrega', 'validez_economica',
        ] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $this->{$campo} = $datos[$campo];
            }
        }

        $this->sucursal_id = (int) Auth::user()->sucursal_id;

        if ($this->banca_id) {
            $this->banca_seleccionada = Banca::query()->where('activa', true)->find($this->banca_id);
            if (!$this->banca_seleccionada) {
                $this->banca_id = null;
            }
        }

        $this->cargarProductos();
        $this->calcularTotal();
        $this->borrador_restaurado = true;
    }
}
