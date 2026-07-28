<?php

namespace App\Livewire\Admin\Salidas;

use App\Models\Salida;
use App\Models\DetalleSalida;
use App\Models\Producto;
use App\Models\InventarioSucuralLote;
use App\Services\InventarioService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ItemsSalida extends Component
{
    public Salida $salida;

    public $productoId;
    public $cantidad = 1;
    public $loteSeleccionado;

    public $productos;
    public $totalSalida;

    public $productoSeleccionadoNombre = '';
    public $lotesDisponibles = [];

    protected $listeners = [
        'finalizarSalida' => 'finalizarSalida',
    ];

    // ============================
    // CARGA INICIAL
    // ============================

    public function mount(Salida $salida)
    {
        abort_unless(Auth::user()->puedeOperarSucursal((int) $salida->sucursal_id), 403);
        $this->salida = $salida;
        $this->cargarProductosConStock();
        $this->cargarDatos();
    }

    public function cargarProductosConStock()
    {
        $productosConStock = InventarioSucuralLote::where('sucursal_id', $this->salida->sucursal_id)
            ->where('cantidad_en_sucursal', '>', 0)
            ->whereHas('lote', function($query) {
                $query->where('estado', true)
                    ->where(function ($subquery) {
                        $subquery->whereNull('fecha_vencimiento')
                            ->orWhereDate('fecha_vencimiento', '>=', today());
                    });
            })
            ->with('lote.producto')
            ->get()
            ->pluck('lote.producto.id')
            ->unique()
            ->values()
            ->toArray();

        $this->productos = Producto::whereIn('id', $productosConStock)
            ->orderBy('codigo')
            ->get();
    }

    public function cargarDatos()
    {
        $this->salida->load('detalles.producto', 'detalles.lote');
        $this->totalSalida = $this->salida->detalles->sum('subtotal');
        $this->reset(['productoId', 'cantidad', 'loteSeleccionado']);
        $this->lotesDisponibles = [];
        $this->cantidad = 1;
    }

    // ============================
    // VALIDACION
    // ============================

    protected function rules()
    {
        $rules = [
            'productoId' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1|max:1000000',
        ];

        if ($this->salida->motivo != 'Venta') {
            $rules['loteSeleccionado'] = 'required';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'productoId.required' => 'Debe seleccionar un producto',
            'loteSeleccionado.required' => 'Debe seleccionar un lote',
            'cantidad.required' => 'Debe ingresar una cantidad',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
        ];
    }

    // ============================
    // CUANDO CAMBIA EL PRODUCTO
    // ============================

    public function updatedProductoId($value)
    {
        $this->loteSeleccionado = null;
        $this->lotesDisponibles = [];

        if (empty($value)) {
            return;
        }

        $producto = Producto::query()->where('estado', true)->find($value);
        if (!$producto) {
            return;
        }

        // Obtener los códigos de lote que YA ESTÁN en la salida
        $lotesEnSalida = DetalleSalida::where('salida_id', $this->salida->id)
            ->whereHas('lote', function($q) use ($value) {
                $q->where('producto_id', $value);
            })
            ->with('lote')
            ->get()
            ->pluck('lote.codigo_lote')
            ->toArray();

        // Obtener todos los lotes del producto con stock en esta sucursal
        $lotesQuery = InventarioSucuralLote::where('sucursal_id', $this->salida->sucursal_id)
            ->where('cantidad_en_sucursal', '>', 0)
            ->whereHas('lote', function($q) use ($value) {
                $q->where('producto_id', $value)
                    ->where('estado', true)
                    ->where(function ($subquery) {
                        $subquery->whereNull('fecha_vencimiento')
                            ->orWhereDate('fecha_vencimiento', '>=', today());
                    });
            })
            ->with('lote')
            ->orderBy('lote_id', 'asc')
            ->get();

        if ($lotesQuery->isEmpty()) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'No hay lotes disponibles para este producto en la sucursal',
                'icono' => 'info'
            ]);
            $this->lotesDisponibles = [];
            return;
        }

        $this->lotesDisponibles = $lotesQuery->map(function($inv) use ($lotesEnSalida) {
            $yaEnSalida = DetalleSalida::where('salida_id', $this->salida->id)
                ->where('lote_id', $inv->lote_id)
                ->sum('cantidad');

            $disponible = $inv->cantidad_en_sucursal - $yaEnSalida;
            $loteYaEnSalida = in_array($inv->lote->codigo_lote, $lotesEnSalida);

            return (object) [
                'lote_id' => $inv->lote_id,
                'codigo_lote' => $inv->lote->codigo_lote,
                'fecha_vencimiento' => $inv->lote->fecha_vencimiento,
                'fecha_vencimiento_timestamp' => $inv->lote->fecha_vencimiento ? strtotime($inv->lote->fecha_vencimiento) : PHP_INT_MAX,
                'stock_total' => $inv->cantidad_en_sucursal,
                'ya_en_salida' => $yaEnSalida,
                'stock_disponible' => max(0, $disponible),
                'precio_venta' => $inv->lote->precio_venta,
                'precio_compra' => $inv->lote->precio_compra,
                'lote_ya_en_salida' => $loteYaEnSalida,
            ];
        })
        ->filter(function($lote) {
            return $lote->stock_disponible > 0 && !$lote->lote_ya_en_salida;
        })
        ->values();

        // Si el motivo es "Venta", ordenar por fecha de vencimiento (FIFO)
        if ($this->salida->motivo == 'Venta') {
            if ($this->lotesDisponibles instanceof \Illuminate\Support\Collection) {
                $this->lotesDisponibles = $this->lotesDisponibles
                    ->sortBy(function($lote) {
                        return $lote->fecha_vencimiento_timestamp;
                    })
                    ->values();
            }

            // Si solo hay un lote disponible, seleccionarlo automáticamente
            if (count($this->lotesDisponibles) === 1) {
                $this->loteSeleccionado = $this->lotesDisponibles[0]->lote_id;
            }
        }

        if (count($this->lotesDisponibles) === 0) {
            $this->dispatch('mostrar-alerta', [
                'mensaje' => 'Todos los lotes disponibles ya están siendo usados en esta salida',
                'icono' => 'info'
            ]);
        }
    }

    // ============================
    // AGREGAR PRODUCTO A SALIDA
    // ============================

    public function agregarItems()
{
    $this->autorizarSalida();
    abort_unless(Auth::user()->can('salidas.edit'), 403);

    // Verificar estado de la salida
    if ($this->salida->estado != 'Pendiente') {
        $this->dispatch('mostrar-alerta', [
            'mensaje' => 'La salida ya fue finalizada o no está pendiente',
            'icono' => 'warning'
        ]);
        return;
    }

    // Validar campos
    $this->validate();

    // Verificar si hay lotes disponibles
    if (empty($this->lotesDisponibles) || count($this->lotesDisponibles) === 0) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => 'No hay lotes disponibles. Seleccione otro producto.'
        ]);
        return;
    }

    $resultado = null;

    // Para motivo "Venta" (tienda) - Lógica FIFO
    if ($this->salida->motivo == 'Venta') {
        $resultado = $this->agregarItemsFIFO();
    } else {
        // Para otros motivos - Lógica normal con selección manual de lote
        $resultado = $this->agregarItemsNormal();
    }

    // SOLO DISPARAR EL EVENTO SI SE AGREGÓ EXITOSAMENTE
    if ($resultado && isset($resultado['success']) && $resultado['success']) {
        $this->dispatch('producto-agregado');

        // Limpiar el buscador después de agregar
        $this->dispatch('limpiar-buscador');
    }
}

    /**
     * Agregar items con lógica FIFO para tienda
     */
    /**
 * Agregar items con lógica FIFO para tienda
 */
/**
 * Agregar items con lógica FIFO para tienda
 */
private function agregarItemsFIFO()
{
    // Verificar si ya hay un lote seleccionado manualmente
    if (!empty($this->loteSeleccionado)) {
        $loteInfo = collect($this->lotesDisponibles)->firstWhere('lote_id', $this->loteSeleccionado);
        if ($loteInfo) {
            return $this->procesarAgregadoItem($loteInfo);
        }
    }

    // Si no hay lote seleccionado, aplicar FIFO
    $cantidadRestante = $this->cantidad;
    $lotesFIFO = $this->lotesDisponibles;
    $itemsAgregados = 0;

    DB::beginTransaction();

    try {
        foreach ($lotesFIFO as $loteInfo) {
            if ($cantidadRestante <= 0) break;

            $cantidadATomar = min($cantidadRestante, $loteInfo->stock_disponible);

            if ($cantidadATomar > 0) {
                // Crear detalle para este lote
                $this->salida->detalles()->create([
                    'producto_id' => $this->productoId,
                    'lote_id' => $loteInfo->lote_id,
                    'cantidad' => $cantidadATomar,
                    'precio_unitario' => $loteInfo->precio_compra ?? 0,
                    'subtotal' => $cantidadATomar * ($loteInfo->precio_compra ?? 0),
                ]);

                $cantidadRestante -= $cantidadATomar;
                $itemsAgregados++;
            }
        }

        if ($cantidadRestante > 0) {
            throw new \RuntimeException('No hay suficiente stock en los lotes disponibles');
        }

        // Actualizar total de la salida
        $this->salida->total = $this->salida->detalles()->sum('subtotal');
        $this->salida->save();

        DB::commit();

        // Recargar datos
        $this->cargarDatos();

        // Recargar lotes disponibles del producto actual
        if ($this->productoId) {
            $this->updatedProductoId($this->productoId);
        }

        $this->dispatch('mostrar-alerta', [
            'icono' => 'success',
            'mensaje' => '✅ Producto agregado a la salida usando FIFO'
        ]);

        // 🔥 IMPORTANTE: Disparar el evento para Alpine.js
        $this->dispatch('producto-agregado');

        return ['success' => true];

    } catch (\Throwable $e) {
        DB::rollBack();
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Agregar items con selección manual de lote
 */
private function agregarItemsNormal()
{
    if (empty($this->loteSeleccionado)) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => 'Debe seleccionar un lote'
        ]);
        return ['success' => false];
    }

    $loteInfo = collect($this->lotesDisponibles)->firstWhere('lote_id', $this->loteSeleccionado);

    if (!$loteInfo) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => 'Lote no válido o no disponible'
        ]);
        return ['success' => false];
    }

    return $this->procesarAgregadoItem($loteInfo);
}

/**
 * Procesar el agregado de un item específico
 */
private function procesarAgregadoItem($loteInfo)
{
    // Validación de cantidad
    if ($this->cantidad > $loteInfo->stock_disponible) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => '❌ CANTIDAD EXCEDIDA: No puedes agregar ' . $this->cantidad .
                        ' unidades. El stock disponible en el lote ' . $loteInfo->codigo_lote .
                        ' es de ' . $loteInfo->stock_disponible . ' unidades.'
        ]);
        return ['success' => false];
    }

    // Validación: Verificar si el MISMO LOTE ya está en la salida
    $mismoLotePorNombre = DetalleSalida::where('salida_id', $this->salida->id)
        ->whereHas('lote', function($query) use ($loteInfo) {
            $query->where('codigo_lote', $loteInfo->codigo_lote);
        })
        ->exists();

    if ($mismoLotePorNombre) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'warning',
            'mensaje' => '❌ El lote "' . $loteInfo->codigo_lote . '" ya está en la salida. No se puede agregar el mismo lote dos veces.'
        ]);
        return ['success' => false];
    }

    DB::beginTransaction();

    try {
        $producto = Producto::findOrFail($this->productoId);

        $precioUnitario = $loteInfo->precio_compra ?? 0;

        if ($precioUnitario <= 0) {
            throw new \RuntimeException('El precio de compra no está configurado para este lote');
        }

        // Crear nuevo detalle
        $this->salida->detalles()->create([
            'producto_id' => $producto->id,
            'lote_id' => $loteInfo->lote_id,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $this->cantidad * $precioUnitario,
        ]);

        // Actualizar total de la salida
        $this->salida->total = $this->salida->detalles()->sum('subtotal');
        $this->salida->save();

        DB::commit();

        // Recargar datos
        $this->cargarDatos();

        // Recargar lotes disponibles del producto actual
        if ($this->productoId) {
            $this->updatedProductoId($this->productoId);
        }

        $this->dispatch('mostrar-alerta', [
            'icono' => 'success',
            'mensaje' => '✅ Producto agregado a la salida (Lote: ' . $loteInfo->codigo_lote . ', Cantidad: ' . $this->cantidad . ')'
        ]);

        return ['success' => true]; // ✅ RETORNAR SUCCESS

    } catch (\Throwable $e) {
        DB::rollBack();
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
        return ['success' => false, 'error' => $e->getMessage()]; // ❌ RETORNAR ERROR
    }
}

    // ============================
    // ACTUALIZAR CANTIDAD EN DETALLE EXISTENTE
    // ============================

    public function actualizarCantidadDetalle($detalleId, $nuevaCantidad)
    {
        $this->autorizarSalida();
        abort_unless(Auth::user()->can('salidas.edit'), 403);

        $cantidad = filter_var($nuevaCantidad, FILTER_VALIDATE_INT);
        if ($cantidad === false || $cantidad < 1) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cantidad debe ser un número entero mayor a cero.');
            return;
        }

        try {
            DB::transaction(function () use ($detalleId, $cantidad) {
                $salida = Salida::query()->lockForUpdate()->findOrFail($this->salida->id);
                if ($salida->estado !== 'Pendiente') {
                    throw new \RuntimeException('La salida ya fue finalizada o cancelada.');
                }

                $detalle = $salida->detalles()->whereKey($detalleId)->lockForUpdate()->firstOrFail();
                $inventario = InventarioSucuralLote::query()
                    ->where('lote_id', $detalle->lote_id)
                    ->where('sucursal_id', $salida->sucursal_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventario) {
                    throw new \RuntimeException('No existe inventario de este lote en la sucursal de la salida.');
                }

                $otrosDetalles = (int) $salida->detalles()
                    ->where('lote_id', $detalle->lote_id)
                    ->whereKeyNot($detalle->id)
                    ->sum('cantidad');
                $stockDisponible = max(0, (int) $inventario->cantidad_en_sucursal - $otrosDetalles);

                if ($cantidad > $stockDisponible) {
                    throw new \RuntimeException("Stock insuficiente. Disponible para este lote: {$stockDisponible}.");
                }

                $detalle->update([
                    'cantidad' => $cantidad,
                    'subtotal' => round($cantidad * (float) $detalle->precio_unitario, 2),
                ]);

                $salida->update(['total' => round((float) $salida->detalles()->sum('subtotal'), 2)]);
                $this->salida = $salida->fresh(['detalles.producto', 'detalles.lote']);
            }, 3);

            $this->cargarDatos();
            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Cantidad actualizada correctamente.');
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: $e->getMessage());
        }
    }

    // ============================
    // ELIMINAR ITEM
    // ============================

    public function borrarItem($detalleId)
    {
        $this->autorizarSalida();
        abort_unless(Auth::user()->can('salidas.edit'), 403);

        try {
            DB::transaction(function () use ($detalleId) {
                $salida = Salida::query()->lockForUpdate()->findOrFail($this->salida->id);
                if ($salida->estado !== 'Pendiente') {
                    throw new \RuntimeException('La salida ya fue finalizada o cancelada.');
                }

                $detalle = $salida->detalles()->whereKey($detalleId)->lockForUpdate()->firstOrFail();
                $detalle->delete();
                $salida->update(['total' => round((float) $salida->detalles()->sum('subtotal'), 2)]);
                $this->salida = $salida->fresh(['detalles.producto', 'detalles.lote']);
            }, 3);

            $this->cargarDatos();
            if ($this->productoId) {
                $this->updatedProductoId($this->productoId);
            }

            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Producto eliminado de la salida.');
            $this->dispatch('producto-eliminado');
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: $e->getMessage());
        }
    }

    // ============================
    // CUANDO CAMBIA EL LOTE SELECCIONADO
    // ============================
    public function updatedLoteSeleccionado($value)
    {
        if (!empty($value) && !empty($this->lotesDisponibles)) {
            $loteInfo = collect($this->lotesDisponibles)->firstWhere('lote_id', $value);
            if ($loteInfo) {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'info',
                    'mensaje' => 'Lote seleccionado: ' . $loteInfo->codigo_lote .
                                ' - Stock disponible: ' . $loteInfo->stock_disponible . ' unidades'
                ]);
            }
        }
    }

    // ============================
    // VERIFICAR STOCK ANTES DE AGREGAR
    // ============================
    public function verificarStockDisponible()
    {
        if (empty($this->lotesDisponibles)) {
            return false;
        }

        // Para FIFO sin lote seleccionado
        if ($this->salida->motivo == 'Venta' && empty($this->loteSeleccionado)) {
            $stockTotal = collect($this->lotesDisponibles)->sum('stock_disponible');
            if ($this->cantidad > $stockTotal) {
                $this->dispatch('mostrar-alerta', [
                    'icono' => 'error',
                    'mensaje' => 'No puedes agregar ' . $this->cantidad . ' unidades. Stock total disponible: ' . $stockTotal . ' unidades.'
                ]);
                return false;
            }
            return true;
        }

        // Para lote específico
        if (!$this->loteSeleccionado) {
            return false;
        }

        $loteInfo = collect($this->lotesDisponibles)->firstWhere('lote_id', $this->loteSeleccionado);

        if (!$loteInfo) {
            return false;
        }

        if ($this->cantidad > $loteInfo->stock_disponible) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'mensaje' => 'No puedes agregar ' . $this->cantidad . ' unidades. Stock disponible en el lote ' .
                            $loteInfo->codigo_lote . ': ' . $loteInfo->stock_disponible . ' unidades.'
            ]);
            return false;
        }

        return true;
    }

    private function autorizarSalida(): void
    {
        $salida = Salida::findOrFail($this->salida->id);
        abort_unless(Auth::user()->puedeOperarSucursal((int) $salida->sucursal_id), 403);
        $this->salida = $salida;
    }

    public function render()
    {
        return view('livewire.admin.salidas.items-salida');
    }

    // ============================
// FINALIZAR SALIDA DESDE LIVEWIRE
// ============================
    public function finalizarSalida()
    {
        $this->autorizarSalida();
        abort_unless(Auth::user()->can('salidas.finalizarSalida'), 403);

        try {
            DB::transaction(function () {
                $salida = Salida::query()->lockForUpdate()->findOrFail($this->salida->id);
                if ($salida->estado !== 'Pendiente') {
                    throw new \RuntimeException('La salida ya fue finalizada o cancelada.');
                }

                $detalles = $salida->detalles()->with(['producto', 'lote'])->orderBy('lote_id')->get();
                if ($detalles->isEmpty()) {
                    throw new \RuntimeException('No hay productos en la salida.');
                }

                $inventario = app(InventarioService::class);
                foreach ($detalles as $detalle) {
                    $inventario->disminuir(
                        (int) $detalle->lote_id,
                        (int) $salida->sucursal_id,
                        (int) $detalle->cantidad,
                        Auth::id(),
                        Salida::class,
                        (int) $salida->id,
                        'Salida #' . $salida->id . ' por ' . strtolower($salida->motivo)
                    );
                }

                $salida->update(['estado' => 'Entregado']);
                $this->salida = $salida->fresh(['detalles.producto', 'detalles.lote']);
            }, 3);

            $this->dispatch('salida-finalizada-con-nota',
                salidaId: $this->salida->id,
                notaUrl: route('salidas.nota-pdf', $this->salida->id),
                descargarUrl: route('salidas.descargar-nota', $this->salida->id)
            );
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Error: ' . $e->getMessage());
        }
    }

    // ============================
// ============================
// VACIAR TODO EL CARRITO
// ============================
public function vaciarCarrito()
{
    $this->autorizarSalida();
    abort_unless(Auth::user()->can('salidas.edit'), 403);

    try {
        DB::transaction(function () {
            $salida = Salida::query()->lockForUpdate()->findOrFail($this->salida->id);
            if ($salida->estado !== 'Pendiente') {
                throw new \RuntimeException('La salida ya fue finalizada o cancelada.');
            }

            $salida->detalles()->delete();
            $salida->update(['total' => 0]);
            $this->salida = $salida->fresh(['detalles.producto', 'detalles.lote']);
        }, 3);

        $this->cargarDatos();
        $this->productoId = null;
        $this->loteSeleccionado = null;
        $this->lotesDisponibles = [];
        $this->cantidad = 1;

        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Se eliminaron todos los productos de la salida.');
        $this->dispatch('carrito-vaciado');
    } catch (\Throwable $e) {
        $this->dispatch('mostrar-alerta', icono: 'error', mensaje: $e->getMessage());
    }
}
}
