<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Venta;
use App\Models\Pago;
use App\Models\Banca;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Services\InventarioService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListaVentas extends Component
{
    use WithPagination;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $estado = '';
    public $tipo = '';
    public $sucursal_id = '';
    public $perPage = 10;

    // Propiedades para el modal de pago
    public $mostrar_modal_pago = false;
    public $venta_id_pago = null;
    public $venta_codigo_pago = null;
    public $cliente_pago = null;
    public $total_venta = 0;
    public $pagado_venta = 0;
    public $pendiente_pago = 0;

    // Datos para nuevo pago
    public $monto_pago = null;
    public $metodo_pago = 'efectivo';
    public $fecha_pago = null;
    public $referencia_pago = '';
    public $observaciones_pago = '';

    // Datos para banca
    public $bancas = [];
    public $banca_id = null;
    public $banca_seleccionada = null;
    public $mostrar_modal_bancas = false;

    public $total_productos_vendidos = 0;
    public $total_costo_compras = 0;
    public $total_ingresos_ventas = 0;
    public $total_ganancia = 0;

    // Lista de pagos de la venta
    public $pagos_venta = [];

    protected $queryString = ['search', 'fecha_desde', 'fecha_hasta', 'estado', 'tipo', 'sucursal_id'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'fecha_desde', 'fecha_hasta', 'estado', 'tipo', 'sucursal_id']);
        $this->resetPage();
    }

    // Método para cargar bancas
    public function cargarBancas()
    {
        $this->bancas = Banca::activas()
            ->ordenadas()
            ->get();
    }

    public function anularVenta($id)
    {
        if (!Auth::user()->can('ventas.anular')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para anular ventas.');
            return;
        }

        try {
            DB::transaction(function () use ($id) {
                $venta = Venta::with(['detalles.lote', 'cliente', 'cotizacion'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!Auth::user()->puedeGestionarSucursal((int) $venta->sucursal_id)) {
                    throw new \RuntimeException('No puede anular una venta de otra sucursal.');
                }

                if ($venta->estado === 'anulada') {
                    throw new \RuntimeException('La venta ya se encuentra anulada.');
                }

                if ($venta->pagos()->lockForUpdate()->exists() || (float) $venta->pagado > 0) {
                    throw new \RuntimeException('La venta tiene pagos registrados. Primero debe realizarse una devolución controlada.');
                }

                $inventario = app(InventarioService::class);
                foreach ($venta->detalles as $detalle) {
                    $inventario->aumentar(
                        (int) $detalle->lote_id,
                        (int) $venta->sucursal_id,
                        (int) $detalle->cantidad,
                        Auth::id(),
                        Venta::class,
                        (int) $venta->id,
                        "Anulación de venta {$venta->codigo}"
                    );
                }

                $venta->update([
                    'estado' => 'anulada',
                    'pagado' => 0,
                    'pendiente' => 0,
                ]);

                if ($venta->cliente) {
                    $venta->cliente->saldo_pendiente = $venta->cliente->ventas()
                        ->where('estado', '!=', 'anulada')
                        ->sum('pendiente');
                    $venta->cliente->save();
                }

                if ($venta->cotizacion && !$venta->cotizacion->venta()->where('estado', '!=', 'anulada')->exists()) {
                    $venta->cotizacion->update(['estado' => 'activa']);
                }
            });

            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Venta anulada y existencias restituidas correctamente.');
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: $e->getMessage());
        }
    }

    public function registrarPago($ventaId)
    {
        if (!Auth::user()->can('pagos.store')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar pagos.');
            return;
        }

        $venta = Venta::with('pagos')->findOrFail($ventaId);
        if (!Auth::user()->puedeGestionarSucursal((int) $venta->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede registrar pagos de otra sucursal.');
            return;
        }

        if ($venta->estado !== 'pendiente' || (float) $venta->pendiente <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Esta venta no tiene un saldo pendiente.');
            return;
        }

        $this->venta_id_pago = $venta->id;
        $this->venta_codigo_pago = $venta->codigo;
        $this->cliente_pago = $venta->cliente?->nombre ?? 'CLIENTE OCASIONAL';
        $this->total_venta = $venta->total;
        $this->pagado_venta = $venta->pagado;
        $this->pendiente_pago = $venta->pendiente;
        $this->pagos_venta = $venta->pagos()->orderByDesc('fecha')->orderByDesc('id')->get();
        $this->bancas = Banca::activas()->ordenadas()->get();
        $this->monto_pago = $venta->pendiente;
        $this->metodo_pago = 'efectivo';
        $this->fecha_pago = now()->format('Y-m-d');
        $this->referencia_pago = '';
        $this->observaciones_pago = '';
        $this->banca_id = null;
        $this->banca_seleccionada = null;
        $this->mostrar_modal_pago = true;
    }

    public function cerrarModalPago()
    {
        $this->mostrar_modal_pago = false;
        $this->reset(['venta_id_pago', 'venta_codigo_pago', 'cliente_pago', 'total_venta', 'pagado_venta', 'pendiente_pago', 'monto_pago', 'metodo_pago', 'fecha_pago', 'referencia_pago', 'observaciones_pago', 'pagos_venta', 'banca_id', 'banca_seleccionada']);
    }

    // Cuando cambia el método de pago
    public function updatedMetodoPago()
    {
        if (!in_array($this->metodo_pago, ['qr', 'transferencia', 'tarjeta'], true)) {
            $this->banca_id = null;
            $this->banca_seleccionada = null;
        }
    }

    // Seleccionar banca
    public function seleccionarBanca($id): void
    {
        $banca = Banca::query()->where('activa', true)->find($id);
        if (!$banca) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cuenta seleccionada no está disponible.');
            return;
        }
        if ($this->metodo_pago === 'qr' && !$banca->qr_code) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Para cobrar por QR, seleccione una cuenta con imagen QR.');
            return;
        }
        $this->banca_id = (int) $banca->id;
        $this->banca_seleccionada = $banca;
        $this->mostrar_modal_bancas = false;
    }

    // Limpiar banca seleccionada
    public function limpiarBanca()
    {
        $this->banca_id = null;
        $this->banca_seleccionada = null;
    }

    public function guardarPago()
    {
        if (!Auth::user()->can('pagos.store')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar pagos.');
            return;
        }

        $this->validate([
            'monto_pago' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,qr,transferencia,tarjeta',
            'fecha_pago' => 'required|date|date_equals:today',
            'referencia_pago' => 'nullable|string|max:100',
            'observaciones_pago' => 'nullable|string|max:500',
        ]);

        try {
            $venta = DB::transaction(function () {
                $venta = Venta::with('cliente')->lockForUpdate()->findOrFail($this->venta_id_pago);
                if (!Auth::user()->puedeGestionarSucursal((int) $venta->sucursal_id)) {
                    throw new \RuntimeException('No puede registrar pagos en otra sucursal.');
                }
                $pendienteActual = round((float) $venta->total - (float) $venta->pagos()->lockForUpdate()->sum('monto'), 2);
                $monto = round((float) $this->monto_pago, 2);

                if ($venta->estado === 'anulada' || $pendienteActual <= 0) {
                    throw new \RuntimeException('La venta ya no tiene saldo pendiente.');
                }
                if ($monto > $pendienteActual) {
                    throw new \RuntimeException('El pago supera el saldo pendiente actual de Bs ' . number_format($pendienteActual, 2));
                }

                $caja = Caja::query()
                    ->where('sucursal_id', $venta->sucursal_id)
                    ->where('estado', 'abierta')
                    ->lockForUpdate()
                    ->latest('fecha_apertura')
                    ->first();
                if (!$caja) {
                    throw new \RuntimeException('No existe una caja abierta en la sucursal de la venta.');
                }

                $banca = null;
                if (in_array($this->metodo_pago, ['qr', 'transferencia', 'tarjeta'], true)) {
                    if (!$this->banca_id) {
                        throw new \RuntimeException('Debe seleccionar una cuenta bancaria activa.');
                    }
                    $banca = Banca::query()->where('activa', true)->lockForUpdate()->find($this->banca_id);
                    if (!$banca) {
                        throw new \RuntimeException('La cuenta bancaria seleccionada no está disponible.');
                    }
                    if ($this->metodo_pago === 'qr' && !$banca->qr_code) {
                        throw new \RuntimeException('La cuenta bancaria seleccionada no tiene una imagen QR registrada.');
                    }
                }

                Pago::create([
                    'venta_id' => $venta->id,
                    'user_id' => Auth::id(),
                    'caja_id' => $caja->id,
                    'banca_id' => $banca?->id,
                    'fecha' => $this->fecha_pago,
                    'monto' => $monto,
                    'metodo_pago' => $this->metodo_pago,
                    'referencia' => $this->referencia_pago ?: ($banca ? 'Pago a ' . $banca->nombre : null),
                    'observaciones' => $this->observaciones_pago ?: null,
                ]);

                if ($banca) {
                    $banca->registrarMovimiento(
                        'carga', $monto, Auth::id(), $caja->id,
                        "Pago venta {$venta->codigo}",
                        $this->observaciones_pago ?: 'Cobro registrado desde ventas'
                    );
                }

                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'venta_id' => $venta->id,
                    'user_id' => Auth::id(),
                    'tipo' => 'ingreso',
                    'monto' => $monto,
                    'metodo_pago' => $this->metodo_pago,
                    'referencia' => $this->referencia_pago ?: null,
                    'concepto' => "Pago de venta {$venta->codigo}",
                    'fecha' => now(),
                ]);

                if ($this->metodo_pago === 'efectivo') {
                    $caja->monto_esperado = $caja->calcularEfectivoEsperado();
                    $caja->save();
                }

                $venta->actualizarSaldo();
                return $venta->fresh(['pagos', 'cliente']);
            }, 3);

            $this->pagado_venta = $venta->pagado;
            $this->pendiente_pago = $venta->pendiente;
            $this->pagos_venta = $venta->pagos()->orderByDesc('fecha')->orderByDesc('id')->get();
            $this->monto_pago = $venta->pendiente > 0 ? $venta->pendiente : null;
            $this->referencia_pago = '';
            $this->observaciones_pago = '';
            $this->banca_id = null;
            $this->banca_seleccionada = null;

            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Pago registrado. Saldo pendiente: Bs ' . number_format((float) $venta->pendiente, 2));
            if ((float) $venta->pendiente <= 0) {
                $this->dispatch('venta-liquidada');
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se registró el pago: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // ========== CONSULTA PARA LA TABLA (CON PAGINACIÓN) ==========
        $query = Venta::with(['cliente', 'user', 'sucursal', 'detalles.producto', 'detalles.lote']);
        $user = Auth::user();
        if (!$user->can('operaciones.todas-sucursales')) {
            $user->sucursal_id
                ? $query->where('sucursal_id', $user->sucursal_id)
                : $query->whereRaw('1 = 0');
            $this->sucursal_id = $user->sucursal_id ? (string) $user->sucursal_id : '';
        }

        // Filtro por búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $q->where('codigo', 'LIKE', "%{$this->search}%")
                  ->orWhereHas('cliente', function($cq) {
                      $cq->where('nombre', 'LIKE', "%{$this->search}%")
                         ->orWhere('nit', 'LIKE', "%{$this->search}%");
                  })
                  ->orWhereHas('user', function($uq) {
                      $uq->where('name', 'LIKE', "%{$this->search}%");
                  });
            });
        }

        // Filtro por fechas
        if ($this->fecha_desde) {
            $query->whereDate('fecha', '>=', $this->fecha_desde);
        }
        if ($this->fecha_hasta) {
            $query->whereDate('fecha', '<=', $this->fecha_hasta);
        }

        // Filtro por estado
        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        // Filtro por tipo (contado/credito)
        if ($this->tipo) {
            $query->where('tipo', $this->tipo);
        }

        // Filtro por sucursal
        if ($this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }

        $ventas = $query->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        // ========== CALCULAR TOTALES (CON LOS MISMOS FILTROS) ==========
        $queryTotal = Venta::query();
        if (!$user->can('operaciones.todas-sucursales')) {
            $user->sucursal_id
                ? $queryTotal->where('sucursal_id', $user->sucursal_id)
                : $queryTotal->whereRaw('1 = 0');
        }

        // Aplicar los mismos filtros a la consulta de totales
        if ($this->search) {
            $queryTotal->where(function($q) {
                $q->where('codigo', 'LIKE', "%{$this->search}%")
                  ->orWhereHas('cliente', function($cq) {
                      $cq->where('nombre', 'LIKE', "%{$this->search}%")
                         ->orWhere('nit', 'LIKE', "%{$this->search}%");
                  })
                  ->orWhereHas('user', function($uq) {
                      $uq->where('name', 'LIKE', "%{$this->search}%");
                  });
            });
        }

        if ($this->fecha_desde) {
            $queryTotal->whereDate('fecha', '>=', $this->fecha_desde);
        }
        if ($this->fecha_hasta) {
            $queryTotal->whereDate('fecha', '<=', $this->fecha_hasta);
        }
        if ($this->estado) {
            $queryTotal->where('estado', $this->estado);
        } else {
            $queryTotal->where('estado', '!=', 'anulada');
        }
        if ($this->tipo) {
            $queryTotal->where('tipo', $this->tipo);
        }
        if ($this->sucursal_id) {
            $queryTotal->where('sucursal_id', $this->sucursal_id);
        }

        $ventasTotales = $queryTotal->with('detalles.lote')->get();

        // Reiniciar valores
        $this->total_productos_vendidos = 0;
        $this->total_costo_compras = 0;
        $this->total_ingresos_ventas = 0;

        // Calcular totales
        foreach ($ventasTotales as $venta) {
            $this->total_ingresos_ventas += $venta->total;

            foreach ($venta->detalles as $detalle) {
                $this->total_productos_vendidos += $detalle->cantidad;

                $precioCompra = (float) ($detalle->costo_unitario ?? $detalle->lote?->precio_compra ?? 0);
                $this->total_costo_compras += $precioCompra * $detalle->cantidad;
            }
        }

        $this->total_ganancia = $this->total_ingresos_ventas - $this->total_costo_compras;

        return view('livewire.admin.ventas.lista-ventas', [
            'ventas' => $ventas
        ]);
    }
}
