<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class ItemsCaja extends Component
{
    // Propiedades para apertura de caja
    public $sucursal_id;
    public $sucursales;
    public $monto_inicial = 0;
    public $observaciones_apertura;

    // Propiedades para cierre de caja
    public $caja_activa;
    public $monto_final_real;
    public $monto_esperado;
    public $diferencia;
    public $observaciones_cierre;
    public $movimientos_dia;

    // Propiedades para reportes
    public $fecha_desde;
    public $fecha_hasta;
    public $cajas_cerradas = [];
    public $total_ingresos = 0;
    public $total_egresos = 0;

    // Propiedades para movimientos manuales
    public $mostrar_modal_movimiento = false;
    public $tipo_movimiento = 'ingreso';
    public $monto_movimiento = 0;
    public $concepto_movimiento = '';
    public $metodo_pago_movimiento = 'efectivo';

    public $total_ingresos_efectivo = 0;
    public $total_egresos_efectivo = 0;
    public $total_ingresos_qr_transferencia = 0;
    public $total_ingresos_tarjeta = 0;
    public $monto_esperado_efectivo = 0;
    public $monto_esperado_qr_transferencia = 0;
    public $monto_esperado_tarjeta = 0;

    public $mostrar_modal_detalle_venta = false;
    public $detalle_venta_actual = null;
    public $productos_venta = [];

    public $mostrar_modal_ventas = false;
    public $caja_seleccionada_id = null;

    public $mostrar_modal_historial = false;
    public $ventas_caja = [];

    public $resumen_ventas_caja = [
        'total_compras' => 0,
        'total_ventas' => 0,
        'total_ganancia' => 0,
        'cantidad_ventas' => 0,
        'cantidad_productos' => 0,
    ];
    public $detalles_ventas_caja = [];

    protected $listeners = [
        'confirmarApertura' => 'confirmarApertura',
        'confirmarCierre' => 'confirmarCierre',
        'confirmarMovimiento' => 'confirmarMovimiento'
    ];

    public function abrirModalHistorial()
    {
        if (!Auth::user()->can('caja.reportes')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para consultar el historial de cajas.');
            return;
        }

        $this->cargarCajasCerradas();
        $this->mostrar_modal_historial = true;
    }

    public function cerrarModalHistorial()
    {
        $this->mostrar_modal_historial = false;
    }

    public function mount()
    {
        $user = Auth::user();
        abort_unless($user && $user->tieneSucursalOperativa(), 403, 'Su usuario debe tener una sucursal activa asignada.');

        $this->sucursal_id = (int) $user->sucursal_id;
        $this->sucursales = collect([$user->sucursal]);

        $this->fecha_desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = Carbon::now()->format('Y-m-d');
        $this->cargarCajaActiva();
    }

    public function updatedSucursalId()
    {
        $sucursalAsignada = (int) Auth::user()->sucursal_id;
        if ((int) $this->sucursal_id !== $sucursalAsignada) {
            $this->sucursal_id = $sucursalAsignada;
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La caja se administra únicamente en la sucursal asignada a su usuario.');
        }
        $this->cargarCajaActiva();
        $this->cargarCajasCerradas();
    }

    public function updatedFechaDesde()
    {
        $this->cargarCajasCerradas();
    }

    public function updatedFechaHasta()
    {
        $this->cargarCajasCerradas();
    }

    public function cargarCajaActiva()
    {
        if ($this->sucursal_id && Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->caja_activa = Caja::getCajaAbierta($this->sucursal_id);
            if ($this->caja_activa) {
                $this->cargarMovimientosDia();
            }
        } else {
            $this->caja_activa = null;
        }
    }

    public function cargarMovimientosDia()
    {
        if (!$this->caja_activa) {
            $this->movimientos_dia = collect();
            return;
        }

        $this->movimientos_dia = MovimientoCaja::where('caja_id', $this->caja_activa->id)
            ->with('user')->orderByDesc('fecha')->orderByDesc('id')->get();

        $this->total_ingresos = (float) $this->movimientos_dia->where('tipo', 'ingreso')->sum('monto');
        $this->total_egresos = (float) $this->movimientos_dia->where('tipo', 'egreso')->sum('monto');
        $this->total_ingresos_efectivo = (float) $this->movimientos_dia
            ->where('tipo', 'ingreso')->where('metodo_pago', 'efectivo')
            ->reject(fn ($m) => $m->concepto === 'Apertura de caja')->sum('monto');
        $this->total_egresos_efectivo = (float) $this->movimientos_dia
            ->where('tipo', 'egreso')->where('metodo_pago', 'efectivo')->sum('monto');
        $this->total_ingresos_qr_transferencia = (float) $this->movimientos_dia
            ->where('tipo', 'ingreso')->whereIn('metodo_pago', ['qr', 'transferencia'])->sum('monto');
        $this->total_ingresos_tarjeta = (float) $this->movimientos_dia
            ->where('tipo', 'ingreso')->where('metodo_pago', 'tarjeta')->sum('monto');

        $this->monto_esperado_efectivo = $this->caja_activa->calcularEfectivoEsperado();
        $this->monto_esperado = $this->monto_esperado_efectivo;
        $this->monto_esperado_qr_transferencia = $this->total_ingresos_qr_transferencia;
        $this->monto_esperado_tarjeta = $this->total_ingresos_tarjeta;
    }

    public function cargarCajasCerradas()
    {
        if ($this->sucursal_id && $this->fecha_desde && $this->fecha_hasta
            && Auth::user()->puedeOperarSucursal((int) $this->sucursal_id)) {
            $this->cajas_cerradas = Caja::where('sucursal_id', $this->sucursal_id)
                ->where('estado', 'cerrada')
                ->whereBetween('fecha_apertura', [
                    Carbon::parse($this->fecha_desde)->startOfDay(),
                    Carbon::parse($this->fecha_hasta)->endOfDay()
                ])
                ->with('user', 'userCierre')
                ->orderBy('fecha_apertura', 'desc')
                ->get();
        } else {
            $this->cajas_cerradas = []; // ← Agregar esta línea para evitar errores
        }
    }

    public function calcularMontoEsperado()
    {
        if ($this->caja_activa) {
            $this->monto_esperado = $this->caja_activa->calcularEfectivoEsperado();
            $this->monto_esperado_efectivo = $this->monto_esperado;
        }
    }

    public function updatedMontoFinalReal()
    {
        if ($this->caja_activa && $this->monto_final_real !== null) {
            $this->calcularMontoEsperado();
            $this->diferencia = $this->monto_final_real - $this->monto_esperado;
        }
    }

    public function abrirModalApertura()
    {
        if ($this->caja_activa) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'warning',
                'mensaje' => 'Ya hay una caja abierta en esta sucursal. Debe cerrarla antes de abrir una nueva.'
            ]);
            return;
        }

        $this->dispatch('mostrar-modal-apertura');
    }

    public function confirmarApertura()
    {
        if (!Auth::user()->can('caja.apertura')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para abrir caja.');
            return;
        }
        $this->validate([
            'sucursal_id' => 'required|exists:sucursals,id,activa,1',
            'monto_inicial' => 'required|numeric|min:0|max:99999999.99',
            'observaciones_apertura' => 'nullable|string|max:500',
        ]);
        $sucursalAsignada = (int) Auth::user()->sucursal_id;
        if ((int) $this->sucursal_id !== $sucursalAsignada || !Auth::user()->puedeOperarSucursal($sucursalAsignada)) {
            $this->sucursal_id = $sucursalAsignada;
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede abrir caja fuera de su sucursal asignada.');
            return;
        }

        try {
            $caja = DB::transaction(function () {
                Sucursal::query()->where('activa', true)->lockForUpdate()->findOrFail($this->sucursal_id);
                if (Caja::query()->where('sucursal_id', $this->sucursal_id)->where('estado', 'abierta')->lockForUpdate()->exists()) {
                    throw new \RuntimeException('Ya existe una caja abierta en esta sucursal.');
                }
                return Caja::create([
                    'sucursal_id' => $this->sucursal_id,
                    'user_id' => Auth::id(),
                    'fecha_apertura' => now(),
                    'monto_inicial' => round((float) $this->monto_inicial, 2),
                    'monto_esperado' => round((float) $this->monto_inicial, 2),
                    'estado' => 'abierta',
                    'observaciones_apertura' => $this->observaciones_apertura,
                ]);
            }, 3);

            $this->cargarCajaActiva();
            $this->reset(['monto_inicial', 'observaciones_apertura']);
            $this->dispatch('mostrar-alerta', icono: 'success',
                mensaje: 'Caja abierta. Fondo inicial: Bs ' . number_format((float) $caja->monto_inicial, 2));
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se abrió la caja: ' . $e->getMessage());
        }
    }

    public function abrirModalCierre()
    {
        if (!$this->caja_activa) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'warning',
                'mensaje' => 'No hay una caja abierta en esta sucursal.'
            ]);
            return;
        }

        $this->calcularMontoEsperado();
        $this->monto_final_real = $this->monto_esperado;
        $this->diferencia = 0;

        $this->dispatch('mostrar-modal-cierre');
    }

    public function confirmarCierre()
    {
        if (!Auth::user()->can('caja.cierre')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para cerrar caja.');
            return;
        }
        $this->validate([
            'monto_final_real' => 'required|numeric|min:0|max:99999999.99',
            'observaciones_cierre' => 'nullable|string|max:500',
        ]);
        if (!$this->caja_activa) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'No hay una caja abierta.');
            return;
        }

        try {
            $resultado = DB::transaction(function () {
                $caja = Caja::query()->lockForUpdate()->findOrFail($this->caja_activa->id);
                if ($caja->estado !== 'abierta') {
                    throw new \RuntimeException('La caja ya fue cerrada por otro usuario.');
                }
                if (!Auth::user()->puedeOperarSucursal((int) $caja->sucursal_id)) {
                    throw new \RuntimeException('No puede cerrar esta caja.');
                }

                $esperado = $caja->calcularEfectivoEsperado();
                $real = round((float) $this->monto_final_real, 2);
                $diferencia = round($real - $esperado, 2);
                $caja->update([
                    'fecha_cierre' => now(),
                    'user_cierre_id' => Auth::id(),
                    'monto_final' => $real,
                    'monto_esperado' => $esperado,
                    'diferencia' => $diferencia,
                    'estado' => 'cerrada',
                    'observaciones_cierre' => $this->observaciones_cierre,
                ]);
                return compact('caja', 'esperado', 'real', 'diferencia');
            }, 3);

            $caja = $resultado['caja'];
            $mensaje = "Caja cerrada correctamente.\n";
            $mensaje .= 'Efectivo contado: Bs ' . number_format($resultado['real'], 2) . "\n";
            $mensaje .= 'Efectivo esperado: Bs ' . number_format($resultado['esperado'], 2) . "\n";
            $mensaje .= 'Diferencia: Bs ' . number_format($resultado['diferencia'], 2);

            $this->cargarCajaActiva();
            $this->cargarCajasCerradas();
            $this->reset(['monto_final_real', 'observaciones_cierre', 'diferencia']);
            $this->dispatch('mostrar-alerta-cierre-con-pdf', mensaje: $mensaje, caja_id: $caja->id,
                fecha_apertura: Carbon::parse($caja->fecha_apertura)->format('d/m/Y H:i'),
                pdf_url: route('caja.ventas-pdf', ['cajaId' => $caja->id]));
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se cerró la caja: ' . $e->getMessage());
        }
    }

    public function abrirModalMovimiento()
    {
        if (!Auth::user()->can('caja.movimientos')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar movimientos manuales.');
            return;
        }
        if (!$this->caja_activa) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Debe existir una caja abierta para registrar movimientos.');
            return;
        }
        $this->mostrar_modal_movimiento = true;
        $this->reset(['tipo_movimiento', 'monto_movimiento', 'concepto_movimiento', 'metodo_pago_movimiento']);
        $this->tipo_movimiento = 'ingreso';
        $this->metodo_pago_movimiento = 'efectivo';
    }

    public function cerrarModalMovimiento()
    {
        $this->mostrar_modal_movimiento = false;
        $this->reset(['tipo_movimiento', 'monto_movimiento', 'concepto_movimiento', 'metodo_pago_movimiento']);
    }

    public function confirmarMovimiento()
    {
        if (!Auth::user()->can('caja.movimientos')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para registrar movimientos.');
            return;
        }
        $this->validate([
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'monto_movimiento' => 'required|numeric|min:0.01|max:99999999.99',
            'concepto_movimiento' => 'required|string|min:3|max:255',
            'metodo_pago_movimiento' => 'required|in:efectivo,qr,transferencia,tarjeta',
        ]);
        if (!$this->caja_activa) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'La caja ya no está abierta.');
            return;
        }

        try {
            DB::transaction(function () {
                $caja = Caja::query()->lockForUpdate()->findOrFail($this->caja_activa->id);
                if ($caja->estado !== 'abierta') throw new \RuntimeException('La caja ya fue cerrada.');
                if (!Auth::user()->puedeOperarSucursal((int) $caja->sucursal_id)) throw new \RuntimeException('No puede operar esta caja.');

                $monto = round((float) $this->monto_movimiento, 2);
                if ($this->tipo_movimiento === 'egreso' && $this->metodo_pago_movimiento === 'efectivo'
                    && $monto > $caja->calcularEfectivoEsperado()) {
                    throw new \RuntimeException('El egreso supera el efectivo esperado en caja.');
                }

                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'user_id' => Auth::id(),
                    'tipo' => $this->tipo_movimiento,
                    'monto' => $monto,
                    'metodo_pago' => $this->metodo_pago_movimiento,
                    'concepto' => $this->concepto_movimiento,
                    'fecha' => now(),
                ]);
                if ($this->metodo_pago_movimiento === 'efectivo') {
                    $caja->monto_esperado = $caja->calcularEfectivoEsperado();
                    $caja->save();
                }
            }, 3);

            $tipo = $this->tipo_movimiento === 'ingreso' ? 'Ingreso' : 'Egreso';
            $monto = $this->monto_movimiento;
            $this->cargarCajaActiva();
            $this->cerrarModalMovimiento();
            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: "{$tipo} registrado por Bs " . number_format((float) $monto, 2));
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se registró el movimiento: ' . $e->getMessage());
        }
    }

    public function imprimirReporte($cajaId)
    {
        if (!Auth::user()->can('caja.reportes')) {
            abort(403, 'No tiene permiso para imprimir reportes de caja.');
        }

        $caja = Caja::with(['movimientos', 'sucursal', 'user', 'userCierre'])->findOrFail($cajaId);
        abort_unless(Auth::user()->puedeOperarSucursal((int) $caja->sucursal_id), 403);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.caja.pdf.reporte', [
            'caja' => $caja,
            'movimientos' => $caja->movimientos,
            'fecha_generacion' => now(),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'reporte_caja_' . $caja->id . '_' . date('Ymd') . '.pdf');
    }

    public function render()
    {
        return view('livewire.admin.ventas.items-caja');
    }

    public function verVentasCaja($cajaId)
    {
        if (!Auth::user()->can('caja.reportes')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para consultar ventas de caja.');
            return;
        }

        $caja = Caja::findOrFail($cajaId);
        if (!Auth::user()->puedeOperarSucursal((int) $caja->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No puede consultar esta caja.');
            return;
        }

        $this->caja_seleccionada_id = $caja->id;
        $ventas = Venta::query()
            ->where('caja_id', $caja->id)
            ->where('estado', '!=', 'anulada')
            ->with(['cliente', 'user', 'detalles.producto', 'detalles.lote'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $this->ventas_caja = $ventas;
        $totalCompras = 0.0;
        $totalGanancia = 0.0;
        $cantidadProductos = 0;
        $detalles = [];

        foreach ($ventas as $venta) {
            $subtotalVenta = (float) $venta->subtotal;
            $factorNeto = $subtotalVenta > 0
                ? min(1, max(0, (float) $venta->total / $subtotalVenta))
                : 1;

            foreach ($venta->detalles as $detalle) {
                $cantidad = (int) $detalle->cantidad;
                $precioCompra = (float) ($detalle->costo_unitario ?? $detalle->lote?->precio_compra ?? 0);
                $subtotalCompra = round($precioCompra * $cantidad, 2);
                $subtotalNeto = round((float) $detalle->subtotal * $factorNeto, 2);
                $ganancia = round($subtotalNeto - $subtotalCompra, 2);

                $cantidadProductos += $cantidad;
                $totalCompras += $subtotalCompra;
                $totalGanancia += $ganancia;

                $detalles[] = [
                    'venta_id' => $venta->id,
                    'venta_codigo' => $venta->codigo,
                    'fecha' => $venta->fecha,
                    'fecha_cierre' => $caja->fecha_cierre,
                    'cliente' => $venta->cliente?->nombre ?? 'CLIENTE OCASIONAL',
                    'vendedor' => $venta->user?->name ?? 'N/A',
                    'producto_nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                    'producto_codigo' => $detalle->producto?->codigo ?? 'N/A',
                    'cantidad' => $cantidad,
                    'precio_compra' => $precioCompra,
                    'precio_venta' => (float) $detalle->precio_unitario,
                    'subtotal_compra' => $subtotalCompra,
                    'subtotal_venta' => $subtotalNeto,
                    'ganancia' => $ganancia,
                ];
            }
        }

        $this->detalles_ventas_caja = $detalles;
        $this->resumen_ventas_caja = [
            'total_compras' => round($totalCompras, 2),
            'total_ventas' => round((float) $ventas->sum('total'), 2),
            'total_ganancia' => round($totalGanancia, 2),
            'cantidad_ventas' => $ventas->count(),
            'cantidad_productos' => $cantidadProductos,
        ];
        $this->mostrar_modal_ventas = true;
    }

    /**
     * Cerrar modal de ventas
     */
    public function cerrarModalVentas()
    {
        $this->mostrar_modal_ventas = false;
        $this->caja_seleccionada_id = null;
        $this->ventas_caja = [];
        $this->resumen_ventas_caja = [
            'total_compras' => 0,
            'total_ventas' => 0,
            'total_ganancia' => 0,
            'cantidad_ventas' => 0,
            'cantidad_productos' => 0,
        ];
        $this->detalles_ventas_caja = [];
    }

    public function verDetalleVenta($ventaId)
    {
        if (!Auth::user()->can('caja.reportes')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para consultar esta venta.');
            return;
        }

        $venta = Venta::with(['cliente', 'user', 'detalles.producto', 'detalles.lote'])->find($ventaId);
        if (!$venta || !Auth::user()->puedeOperarSucursal((int) $venta->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'Venta no encontrada o sin autorización.');
            return;
        }

        $this->detalle_venta_actual = $venta;
        $this->productos_venta = $venta->detalles;
        $this->mostrar_modal_detalle_venta = true;
    }

    public function cerrarModalDetalleVenta()
    {
        $this->mostrar_modal_detalle_venta = false;
        $this->detalle_venta_actual = null;
        $this->productos_venta = [];
    }
}
