<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\InventarioSucuralLote;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Lote;
use App\Services\CotizacionStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class ItemsCotizacion extends Component
{
    public $cotizacion;
    public $sucursal_id;
    public $sucursales;
    public $cliente_id;
    public $clientes;
    public $productoId;
    public $cantidad = 1;
    public $productos;
    public $carrito = [];
    public $subtotalCotizacion = 0;
    public $descuentoCotizacion = 0;
    public $totalCotizacion = 0;
    public $nuevoTotalCotizacion = 0;
    public $valida_hasta;
    public $observaciones;

    public $busqueda_producto = '';
    public $productos_filtrados = [];

    public $busqueda_cliente = '';
    public $clientes_filtrados = [];
    public $mostrar_resultados_clientes = false;

    public $nuevo_cliente_nombre = '';
    public $nuevo_cliente_nit = '';
    public $nuevo_cliente_telefono = '';
    public $nuevo_cliente_email = '';
    public $mostrar_modal_cliente = false;

    public $incluye_impuesto = 'con_impuesto';
    public $forma_pago = 'contado';
    public $lugar_entrega = '';
    public $plazo_entrega = 5;
    public $validez_economica = 48;
    public $observaciones_adicionales = '';

    public $stockProductoSeleccionado = 0;

    protected $listeners = [
        'confirmarCotizacion' => 'confirmarCotizacion',
        'guardarCotizacion' => 'guardarCotizacion',
        'set-producto-id' => 'setProductoId',
        'convertirAVenta' => 'convertirAVenta'
    ];

    public function mount($cotizacion = null)
    {
        $user = Auth::user();
        abort_unless($user && $user->tieneSucursalOperativa(), 403, 'Su usuario debe tener una sucursal activa asignada.');

        $this->sucursal_id = (int) $user->sucursal_id;
        $this->sucursales = collect([$user->sucursal]);
        $this->clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        $this->valida_hasta = Carbon::now()->addDays(3)->format('Y-m-d');

        if ($cotizacion) {
            $this->cotizacion = $cotizacion;
            if (!$user->puedeOperarSucursal((int) $cotizacion->sucursal_id)) {
                abort(403, 'La cotización pertenece a otra sucursal.');
            }
            $this->cargarDetallesCotizacion();
        } else {
            $this->cotizacion = null;
            $this->cargarProductos();
        }
    }

    public function cargarProductos()
    {
        $this->productos = Producto::query()->orderBy('nombre')->get();
    }

    private function cargarCotizacion($cotizacion)
    {
        if ($cotizacion->cliente_id) {
            $this->cliente_id = $cotizacion->cliente_id;
            $cliente = Cliente::find($cotizacion->cliente_id);
            if ($cliente) {
                $this->busqueda_cliente = $cliente->nombre;
            }
        }

        $this->carrito = [];
        foreach ($cotizacion->detalles as $detalle) {
            $this->carrito[] = [
                'id' => uniqid(),
                'producto_id' => $detalle->producto_id,
                'producto_nombre' => $detalle->producto->nombre,
                'producto_codigo' => $detalle->producto->codigo,
                'lote_id' => $detalle->lote_id,
                'lote_codigo' => $detalle->lote ? $detalle->lote->codigo_lote : null,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal' => $detalle->subtotal,
                'multi_lote' => false,
                'lotes_usados' => null,
                'sin_stock' => $this->obtenerStockDisponible($detalle->producto_id) < (int) $detalle->cantidad
            ];
        }

        $this->descuentoCotizacion = round((float) ($cotizacion->descuento ?? 0), 2);
        $this->calcularTotal();
    }

    public function updatedBusquedaProducto()
    {
        if (strlen($this->busqueda_producto) >= 2) {
            $this->productos_filtrados = Producto::query()
                ->where(function($query) {
                    $query->where('nombre', 'LIKE', "%{$this->busqueda_producto}%")
                        ->orWhere('codigo', 'LIKE', "%{$this->busqueda_producto}%")
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
        $producto = Producto::query()->find($id);
        if (!$producto) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'El producto ya no existe en el catálogo.');
            return;
        }

        $this->productoId = $producto->id;
        $this->busqueda_producto = $producto->nombre;
        $this->productos_filtrados = [];
        $this->stockProductoSeleccionado = $this->obtenerStockDisponible($producto->id);
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

    public function abrirModalCliente()
    {
        if (!Auth::user()->can('clientes.store')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar clientes.');
            return;
        }

        $this->mostrar_modal_cliente = true;
        $this->nuevo_cliente_nombre = '';
        $this->nuevo_cliente_nit = '';
        $this->nuevo_cliente_telefono = '';
        $this->nuevo_cliente_email = '';
    }

    public function cerrarModalCliente()
    {
        $this->mostrar_modal_cliente = false;
    }

    public function guardarClienteRapido()
    {
        if (!Auth::user()->can('clientes.store')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar clientes.');
            return;
        }

        $this->validate([
            'nuevo_cliente_nombre' => 'required|string|max:150',
            'nuevo_cliente_nit' => 'nullable|string|max:50|unique:clientes,nit',
            'nuevo_cliente_telefono' => 'nullable|string|max:30',
            'nuevo_cliente_email' => 'nullable|email:rfc|max:150',
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

    public function agregarAlCarrito()
    {
        $this->validate([
            'productoId' => 'required|integer|exists:productos,id',
            'sucursal_id' => 'required|integer|exists:sucursals,id',
            'cantidad' => 'required|integer|min:1|max:1000000',
        ], [
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ]);

        $cantidad = (int) $this->cantidad;
        if (!$this->productoId || !$this->sucursal_id || $cantidad <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Seleccione producto, sucursal y una cantidad válida.');
            return;
        }
        if (!Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede cotizar para esta sucursal.');
            return;
        }

        $producto = Producto::query()->find($this->productoId);
        if (!$producto) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'El producto ya no existe en el catálogo.');
            return;
        }

        $precio = round((float) $producto->precio_venta, 2);
        if ($precio <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El producto no tiene un precio de venta válido.');
            return;
        }

        $stockDisponible = $this->obtenerStockDisponible($producto->id);
        $encontrado = false;
        foreach ($this->carrito as &$item) {
            if ((int) $item['producto_id'] === (int) $producto->id) {
                $item['cantidad'] += $cantidad;
                $item['subtotal'] = round($item['cantidad'] * $item['precio_unitario'], 2);
                $item['sin_stock'] = $stockDisponible < $item['cantidad'];
                $encontrado = true;
                break;
            }
        }
        unset($item);

        if (!$encontrado) {
            $this->carrito[] = [
                'id' => uniqid('', true),
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'producto_codigo' => $producto->codigo,
                'lote_id' => null,
                'lote_codigo' => null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => round($cantidad * $precio, 2),
                'sin_stock' => $stockDisponible < $cantidad,
                'multi_lote' => false,
                'lotes_usados' => null,
            ];
        }

        $this->calcularTotal();
        $nombre = $producto->nombre;
        $this->reset(['productoId', 'busqueda_producto']);
        $this->stockProductoSeleccionado = 0;
        $this->cantidad = 1;
        $this->productos_filtrados = [];
        $this->dispatch('producto-agregado');
        $this->dispatch('mostrar-alerta', icono: $stockDisponible < $cantidad ? 'warning' : 'success',
            mensaje: "{$nombre} agregado. La cotización no reserva inventario.");
    }

    private function obtenerStockDisponible($productoId)
    {
        if (!$this->sucursal_id) return 0;

        return (int) InventarioSucuralLote::query()
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->where('inventario_sucural_lotes.sucursal_id', $this->sucursal_id)
            ->where('lotes.producto_id', $productoId)
            ->where('lotes.estado', true)
            ->where('lotes.cantidad_actual', '>', 0)
            ->where(function ($query) {
                $query->whereNull('lotes.fecha_vencimiento')->orWhereDate('lotes.fecha_vencimiento', '>=', today());
            })
            ->sum('inventario_sucural_lotes.cantidad_en_sucursal');
    }

    public function actualizarCantidadCarrito($index, $nuevaCantidad)
    {
        if (!filter_var($nuevaCantidad, FILTER_VALIDATE_INT) || (int) $nuevaCantidad < 1 || (int) $nuevaCantidad > 1000000) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'La cantidad debe ser un entero mayor a cero.');
            return;
        }

        $cantidad = (int) $nuevaCantidad;
        if (!isset($this->carrito[$index])) return;
        if ($cantidad <= 0) {
            $this->eliminarDelCarrito($index);
            return;
        }
        $this->carrito[$index]['cantidad'] = $cantidad;
        $this->carrito[$index]['subtotal'] = round($cantidad * (float) $this->carrito[$index]['precio_unitario'], 2);
        $this->carrito[$index]['sin_stock'] = $this->obtenerStockDisponible($this->carrito[$index]['producto_id']) < $cantidad;
        $this->calcularTotal();
    }

    public function actualizarPrecioUnitario($index, $nuevoPrecio)
    {
        if (!Auth::user()->can('ventas.modificar-precio')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para modificar precios de cotización.');
            return;
        }
        if (!isset($this->carrito[$index])) return;
        $precio = round((float) $nuevoPrecio, 2);
        if ($precio <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El precio debe ser mayor a cero.');
            return;
        }
        $this->carrito[$index]['precio_unitario'] = $precio;
        $this->carrito[$index]['subtotal'] = round($this->carrito[$index]['cantidad'] * $precio, 2);
        $this->calcularTotal();
    }

    public function eliminarDelCarrito($index)
    {
        unset($this->carrito[$index]);
        $this->carrito = array_values($this->carrito);
        $this->calcularTotal();
    }

    public function vaciarCarrito()
    {
        $this->carrito = [];
        $this->calcularTotal();
    }

    public function calcularTotal()
    {
        $this->subtotalCotizacion = round((float) collect($this->carrito)->sum('subtotal'), 2);
        $this->descuentoCotizacion = round(min($this->subtotalCotizacion, max(0, (float) $this->descuentoCotizacion)), 2);
        $this->totalCotizacion = round($this->subtotalCotizacion - $this->descuentoCotizacion, 2);
        $this->nuevoTotalCotizacion = $this->totalCotizacion;
    }

    public function actualizarTotalCotizacion($nuevoTotal): void
    {
        if (!Auth::user()->can('cotizaciones.aplicar-descuento')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para aplicar rebajas en cotizaciones.');
            $this->nuevoTotalCotizacion = $this->totalCotizacion;
            return;
        }

        $subtotal = round((float) collect($this->carrito)->sum('subtotal'), 2);
        $total = round((float) $nuevoTotal, 2);

        if ($subtotal <= 0 || $total <= 0 || $total > $subtotal) {
            $this->nuevoTotalCotizacion = $this->totalCotizacion;
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'El total final debe ser mayor a cero y no puede superar el subtotal.');
            return;
        }

        $this->subtotalCotizacion = $subtotal;
        $this->descuentoCotizacion = round($subtotal - $total, 2);
        $this->totalCotizacion = $total;
        $this->nuevoTotalCotizacion = $total;
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Total de cotización actualizado. Rebaja: Bs ' . number_format($this->descuentoCotizacion, 2));
    }

    public function quitarDescuentoCotizacion(): void
    {
        if (!Auth::user()->can('cotizaciones.aplicar-descuento')) {
            return;
        }

        $this->descuentoCotizacion = 0;
        $this->calcularTotal();
    }

    public function updatedSucursalId(): void
    {
        $sucursalAsignada = (int) Auth::user()->sucursal_id;
        if ((int) $this->sucursal_id !== $sucursalAsignada) {
            $this->sucursal_id = $sucursalAsignada;
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La sucursal de la cotización se obtiene de su usuario y no puede cambiarse.');
        }
    }

    public function confirmarCotizacion()
    {
        $permiso = $this->cotizacion ? 'cotizaciones.update' : 'cotizaciones.store';
        if (!Auth::user()->can($permiso)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para guardar esta cotización.');
            return;
        }
        if (empty($this->carrito) || !$this->sucursal_id) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Agregue productos para continuar.');
            return;
        }
        if (!Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede operar en esta sucursal.');
            return;
        }
        if (!$this->valida_hasta || Carbon::parse($this->valida_hasta)->lt(today())) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'La fecha de validez no puede estar en el pasado.');
            return;
        }
        if ($this->descuentoCotizacion > 0 && !Auth::user()->can('cotizaciones.aplicar-descuento')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para aplicar rebajas en cotizaciones.');
            return;
        }
        $this->dispatch('mostrar-confirmacion-cotizacion', total: $this->totalCotizacion,
            cliente_id: $this->cliente_id, valida_hasta: $this->valida_hasta);
    }

    public function guardarCotizacion()
    {
        try {
            $cotizacion = DB::transaction(function () {
                $user = Auth::user();
                $permiso = $this->cotizacion ? 'cotizaciones.update' : 'cotizaciones.store';
                if (!$user || !$user->tieneSucursalOperativa() || !$user->can($permiso)) {
                    throw new \RuntimeException('No tiene permiso o una sucursal activa para guardar esta cotización.');
                }
                $sucursalId = (int) $user->sucursal_id;
                if ((int) $this->sucursal_id !== $sucursalId) {
                    throw new \RuntimeException('La sucursal de la cotización no coincide con la sucursal de su usuario.');
                }
                $this->sucursal_id = $sucursalId;
                if (empty($this->carrito)) {
                    throw new \RuntimeException('La cotización no contiene datos válidos.');
                }
                if (!$user->puedeOperarSucursal($sucursalId)) {
                    throw new \RuntimeException('No puede cotizar para esta sucursal.');
                }
                if (!$this->valida_hasta || Carbon::parse($this->valida_hasta)->lt(today())) {
                    throw new \RuntimeException('La fecha de validez no puede estar en el pasado.');
                }

                $itemsValidados = [];
                $subtotalServidor = 0.0;
                foreach ($this->carrito as $item) {
                    $producto = Producto::query()->find($item['producto_id'] ?? null);
                    $cantidad = (int) ($item['cantidad'] ?? 0);
                    $precio = round((float) ($item['precio_unitario'] ?? 0), 2);
                    if (!$producto || $cantidad <= 0 || $precio <= 0) {
                        throw new \RuntimeException('La cotización contiene un producto, cantidad o precio inválido.');
                    }
                    if (!Auth::user()->can('ventas.modificar-precio')
                        && abs($precio - round((float) $producto->precio_venta, 2)) > 0.01) {
                        throw new \RuntimeException('No tiene permiso para modificar el precio de ' . $producto->nombre . '.');
                    }
                    $subtotal = round($cantidad * $precio, 2);
                    $subtotalServidor += $subtotal;
                    $itemsValidados[] = compact('producto', 'cantidad', 'precio', 'subtotal');
                }
                $subtotalServidor = round($subtotalServidor, 2);
                $descuentoServidor = round(min($subtotalServidor, max(0, (float) $this->descuentoCotizacion)), 2);
                if ($descuentoServidor > 0 && !$user->can('cotizaciones.aplicar-descuento')) {
                    throw new \RuntimeException('No tiene permiso para aplicar rebajas en cotizaciones.');
                }
                $totalServidor = round($subtotalServidor - $descuentoServidor, 2);
                if ($totalServidor <= 0) {
                    throw new \RuntimeException('El total final de la cotización debe ser mayor a cero.');
                }

                $observacionesJson = json_encode([
                    'notas' => $this->observaciones,
                    'incluye_impuesto' => $this->incluye_impuesto,
                    'forma_pago' => $this->forma_pago,
                    'lugar_entrega' => $this->lugar_entrega,
                    'plazo_entrega' => $this->plazo_entrega,
                    'observaciones_adicionales' => $this->observaciones_adicionales,
                    'fecha_creacion' => now()->format('d/m/Y H:i'),
                    'vendedor' => Auth::user()->name,
                    'aviso_inventario' => 'La cotización no reserva inventario; el stock se valida al convertirla en venta.',
                ], JSON_UNESCAPED_UNICODE);

                if ($this->cotizacion) {
                    $cotizacion = Cotizacion::query()->lockForUpdate()->findOrFail($this->cotizacion->id);
                    if (!Auth::user()->puedeOperarSucursal((int) $cotizacion->sucursal_id)) {
                        throw new \RuntimeException('La cotización pertenece a otra sucursal.');
                    }
                    if ($cotizacion->estado !== 'activa') {
                        throw new \RuntimeException('Solo se pueden editar cotizaciones activas.');
                    }
                    $cotizacion->update([
                        'sucursal_id' => $this->sucursal_id,
                        'cliente_id' => $this->cliente_id,
                        'valida_hasta' => $this->valida_hasta,
                        'subtotal' => $subtotalServidor,
                        'descuento' => $descuentoServidor,
                        'total' => $totalServidor,
                        'observaciones' => $observacionesJson,
                    ]);
                    $cotizacion->detalles()->delete();
                } else {
                    $cotizacion = Cotizacion::create([
                        'codigo' => Cotizacion::generarCodigo(),
                        'sucursal_id' => $this->sucursal_id,
                        'user_id' => Auth::id(),
                        'cliente_id' => $this->cliente_id,
                        'fecha' => today(),
                        'valida_hasta' => $this->valida_hasta,
                        'subtotal' => $subtotalServidor,
                        'descuento' => $descuentoServidor,
                        'total' => $totalServidor,
                        'observaciones' => $observacionesJson,
                        'estado' => 'activa',
                    ]);
                }

                foreach ($itemsValidados as $item) {
                    DetalleCotizacion::create([
                        'cotizacion_id' => $cotizacion->id,
                        'producto_id' => $item['producto']->id,
                        'lote_id' => null,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio'],
                        'subtotal' => $item['subtotal'],
                    ]);
                }
                $this->subtotalCotizacion = $subtotalServidor;
                $this->descuentoCotizacion = $descuentoServidor;
                $this->totalCotizacion = $totalServidor;
                $this->nuevoTotalCotizacion = $totalServidor;
                return $cotizacion;
            }, 3);

            $tieneSinStock = collect($this->carrito)->contains(fn ($item) => !empty($item['sin_stock']));
            $this->dispatch('cotizacion-guardada', cotizacionId: $cotizacion->id,
                imprimirUrl: route('cotizaciones.imprimir', $cotizacion->id), tieneSinStock: $tieneSinStock);
            $this->carrito = [];
            $this->subtotalCotizacion = 0;
            $this->descuentoCotizacion = 0;
            $this->totalCotizacion = 0;
            $this->nuevoTotalCotizacion = 0;
            $this->cotizacion = $cotizacion;
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se guardó la cotización: ' . $e->getMessage());
        }
    }

    public function cargarDetallesCotizacion()
    {
        $this->cotizacion->load('detalles.producto', 'detalles.lote', 'cliente');
        $this->carrito = [];
        foreach ($this->cotizacion->detalles as $detalle) {
            $this->carrito[] = [
                'id' => $detalle->id,
                'producto_id' => $detalle->producto_id,
                'producto_nombre' => $detalle->producto->nombre,
                'producto_codigo' => $detalle->producto->codigo,
                'lote_id' => $detalle->lote_id,
                'lote_codigo' => $detalle->lote ? $detalle->lote->codigo_lote : null,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal' => $detalle->subtotal,
                'sin_stock' => $this->obtenerStockDisponible($detalle->producto_id) < (int) $detalle->cantidad,
            ];
        }
        $this->subtotalCotizacion = round((float) $this->cotizacion->subtotal, 2);
        $this->descuentoCotizacion = round((float) $this->cotizacion->descuento, 2);
        $this->totalCotizacion = round((float) $this->cotizacion->total, 2);
        $this->nuevoTotalCotizacion = $this->totalCotizacion;
        $this->cliente_id = $this->cotizacion->cliente_id;
        $this->valida_hasta = $this->cotizacion->valida_hasta ? Carbon::parse($this->cotizacion->valida_hasta)->format('Y-m-d') : $this->valida_hasta;

        $observacionesData = json_decode($this->cotizacion->observaciones, true);
        if ($observacionesData) {
            $this->observaciones = $observacionesData['notas'] ?? '';
            $this->incluye_impuesto = $observacionesData['incluye_impuesto'] ?? 'con_impuesto';
            $this->forma_pago = $observacionesData['forma_pago'] ?? 'contado';
            $this->lugar_entrega = $observacionesData['lugar_entrega'] ?? '';
            $this->plazo_entrega = $observacionesData['plazo_entrega'] ?? 5;
            $this->validez_economica = $observacionesData['validez_economica'] ?? 48;
            $this->observaciones_adicionales = $observacionesData['observaciones_adicionales'] ?? '';
        } else {
            $this->observaciones = $this->cotizacion->observaciones;
        }

        if ($this->cliente_id) {
            $cliente = Cliente::find($this->cliente_id);
            if ($cliente) {
                $this->busqueda_cliente = $cliente->nombre;
            }
        }
    }

    public function convertirAVenta($cotizacionId)
    {
        $cotizacion = Cotizacion::find($cotizacionId);
        if (!$cotizacion || $cotizacion->estado !== 'activa' || ($cotizacion->valida_hasta && $cotizacion->valida_hasta->isPast())) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cotización no está activa o ya venció.');
            return;
        }
        if (!Auth::user()->can('cotizaciones.convertir') || !Auth::user()->puedeOperarSucursal((int) $cotizacion->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para convertir esta cotización.');
            return;
        }

        $faltantes = app(CotizacionStockService::class)->faltantes($cotizacion);
        if ($faltantes !== []) {
            $detalle = collect($faltantes)
                ->map(fn (array $item) => "{$item['codigo']} {$item['nombre']}: faltan {$item['cantidad_faltante']}")
                ->implode(' | ');
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Debe abastecer los productos antes de convertir: ' . $detalle);
            return;
        }

        return redirect()->route('ventas.create', ['cotizacion_id' => $cotizacionId]);
    }

    public function actualizarSubtotal($index)
    {
        if (isset($this->carrito[$index])) {
            $this->carrito[$index]['subtotal'] = $this->carrito[$index]['cantidad'] * $this->carrito[$index]['precio_unitario'];
            $this->calcularTotal();
        }
    }

    public function render()
    {
        return view('livewire.admin.ventas.items-cotizacion');
    }
}
