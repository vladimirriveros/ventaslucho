<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesVentas extends Component
{
    // Filtros
    public $tipo_reporte = 'diario'; // diario, mensual, vendedores
    public $fecha_desde;
    public $fecha_hasta;
    public $mes_seleccionado;
    public $anio_seleccionado;
    public $sucursal_id;
    public $vendedor_id;

    // Datos
    public $sucursales;
    public $vendedores;
    public $ventas;
    public $resumen;
    public $top_vendedores;
    public $productos_mas_vendidos;
    public $metodos_pago;

    // Gráficos
    public $ventas_por_dia;
    public $ventas_por_mes;

    protected $listeners = [
        'generarReporte' => 'generarReporte',
        'exportarPDF' => 'exportarPDF',
        'exportarExcel' => 'exportarExcel'
    ];

    public function mount()
    {
        abort_unless(Auth::user()->can('reportes.ventas'), 403);
        $user = Auth::user();

        $sucursalesQuery = Sucursal::where('activa', true)->orderBy('nombre');
        $vendedoresQuery = User::role('vendedor')->orderBy('name');
        if (!$user->can('operaciones.todas-sucursales')) {
            if ($user->sucursal_id) {
                $sucursalesQuery->whereKey($user->sucursal_id);
                $vendedoresQuery->where('sucursal_id', $user->sucursal_id);
                $this->sucursal_id = $user->sucursal_id;
            } else {
                $sucursalesQuery->whereRaw('1 = 0');
                $vendedoresQuery->whereRaw('1 = 0');
            }
        }

        $this->sucursales = $sucursalesQuery->get();
        $this->vendedores = $vendedoresQuery->get();

        // Inicializar otros arrays vacíos
        $this->ventas = collect();
        $this->resumen = collect();
        $this->top_vendedores = collect();
        $this->productos_mas_vendidos = collect();
        $this->metodos_pago = collect();
        $this->ventas_por_dia = collect();
        $this->ventas_por_mes = collect();

        // Fechas por defecto
        $this->fecha_desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = Carbon::now()->format('Y-m-d');
        $this->mes_seleccionado = Carbon::now()->month;
        $this->anio_seleccionado = Carbon::now()->year;

        $this->generarReporte();
    }

    public function updatedTipoReporte()
    {
        $this->generarReporte();
    }

    public function updatedFechaDesde()
    {
        $this->generarReporte();
    }

    public function updatedFechaHasta()
    {
        $this->generarReporte();
    }

    public function updatedMesSeleccionado()
    {
        $this->generarReporte();
    }

    public function updatedAnioSeleccionado()
    {
        $this->generarReporte();
    }

    public function updatedSucursalId()
    {
        $this->asegurarAlcance();
        $this->generarReporte();
    }

    public function updatedVendedorId()
    {
        $this->asegurarAlcance();
        $this->generarReporte();
    }

    public function generarReporte()
    {
        abort_unless(Auth::user()->can('reportes.ventas'), 403);
        $this->asegurarAlcance();

        if (!in_array($this->tipo_reporte, ['diario', 'mensual', 'vendedores'], true)) {
            $this->tipo_reporte = 'diario';
        }
        if ($this->fecha_desde && $this->fecha_hasta && $this->fecha_desde > $this->fecha_hasta) {
            [$this->fecha_desde, $this->fecha_hasta] = [$this->fecha_hasta, $this->fecha_desde];
        }

        switch ($this->tipo_reporte) {
            case 'diario':
                $this->generarReporteDiario();
                break;
            case 'mensual':
                $this->generarReporteMensual();
                break;
            case 'vendedores':
                $this->generarReporteVendedores();
                break;
        }
    }

    public function generarReporteDiario()
    {
        $query = Venta::whereBetween('fecha', [$this->fecha_desde, $this->fecha_hasta])
            ->where('estado', '!=', 'anulada');

        if ($this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }

        if ($this->vendedor_id) {
            $query->where('user_id', $this->vendedor_id);
        }

        $this->ventas = $query->with(['cliente', 'user', 'sucursal', 'detalles'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Resumen por día
        $this->resumen = [
            'total_ventas' => $this->ventas->count(),
            'total_ingresos' => $this->ventas->sum('total'),
            'total_contado' => $this->ventas->where('tipo', 'contado')->sum('total'),
            'total_credito' => $this->ventas->where('tipo', 'credito')->sum('total'),
            'total_pendiente' => $this->ventas->where('estado', 'pendiente')->sum('pendiente'),
            'promedio_venta' => $this->ventas->count() > 0 ? $this->ventas->sum('total') / $this->ventas->count() : 0,
        ];

        // Métodos de pago (solo contado)
        $this->metodos_pago = DB::table('pagos')
            ->join('ventas', 'pagos.venta_id', '=', 'ventas.id')
            ->whereBetween('pagos.fecha', [$this->fecha_desde, $this->fecha_hasta])
            ->where('ventas.estado', '!=', 'anulada')
            ->when($this->sucursal_id, function($query) {
                $query->where('ventas.sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('ventas.user_id', $this->vendedor_id);
            })
            ->select('pagos.metodo_pago', DB::raw('SUM(pagos.monto) as total'))
            ->groupBy('pagos.metodo_pago')
            ->get()
            ->mapWithKeys(function($item) {
                $nombres = [
                    'efectivo' => 'Efectivo',
                    'qr' => 'QR',
                    'transferencia' => 'Transferencia',
                    'tarjeta' => 'Tarjeta'
                ];
                return [$nombres[$item->metodo_pago] ?? $item->metodo_pago => $item->total];
            });

        // Productos más vendidos
        $this->productos_mas_vendidos = DetalleVenta::select(
                'productos.id',
                'productos.nombre',
                'productos.codigo',
                DB::raw('SUM(detalle_ventas.cantidad) as total_cantidad'),
                DB::raw('SUM(detalle_ventas.subtotal * (ventas.total / NULLIF(ventas.subtotal, 0))) as total_venta')
            )
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->whereBetween('ventas.fecha', [$this->fecha_desde, $this->fecha_hasta])
            ->where('ventas.estado', '!=', 'anulada')
            ->when($this->sucursal_id, function($query) {
                $query->where('ventas.sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('ventas.user_id', $this->vendedor_id);
            })
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderBy('total_cantidad', 'desc')
            ->limit(10)
            ->get();

        // Ventas por día (para gráfico)
        $this->ventas_por_dia = Venta::select(
                DB::raw('DATE(fecha) as dia'),
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(total) as total_ingresos')
            )
            ->whereBetween('fecha', [$this->fecha_desde, $this->fecha_hasta])
            ->where('estado', '!=', 'anulada')
            ->when($this->sucursal_id, function($query) {
                $query->where('sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('user_id', $this->vendedor_id);
            })
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function generarReporteMensual()
    {
        $fechaInicio = Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1)->startOfMonth();
        $fechaFin = Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1)->endOfMonth();

        $query = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', '!=', 'anulada');

        if ($this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }

        if ($this->vendedor_id) {
            $query->where('user_id', $this->vendedor_id);
        }

        $this->ventas = $query->with(['cliente', 'user', 'sucursal'])
            ->orderBy('fecha', 'desc')
            ->get();

        // Resumen mensual
        $this->resumen = [
            'total_ventas' => $this->ventas->count(),
            'total_ingresos' => $this->ventas->sum('total'),
            'total_contado' => $this->ventas->where('tipo', 'contado')->sum('total'),
            'total_credito' => $this->ventas->where('tipo', 'credito')->sum('total'),
            'total_pendiente' => $this->ventas->where('estado', 'pendiente')->sum('pendiente'),
            'promedio_venta' => $this->ventas->count() > 0 ? $this->ventas->sum('total') / $this->ventas->count() : 0,
            'dias_con_ventas' => $this->ventas->pluck('fecha')->unique()->count(),
        ];

        // Ventas por día del mes
        $this->ventas_por_dia = Venta::select(
                DB::raw('DAY(fecha) as dia'),
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(total) as total_ingresos')
            )
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', '!=', 'anulada')
            ->when($this->sucursal_id, function($query) {
                $query->where('sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('user_id', $this->vendedor_id);
            })
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // Métodos de pago
        $this->metodos_pago = DB::table('pagos')
            ->join('ventas', 'pagos.venta_id', '=', 'ventas.id')
            ->whereBetween('pagos.fecha', [$fechaInicio, $fechaFin])
            ->when($this->sucursal_id, function($query) {
                $query->where('ventas.sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('ventas.user_id', $this->vendedor_id);
            })
            ->select('pagos.metodo_pago', DB::raw('SUM(pagos.monto) as total'))
            ->groupBy('pagos.metodo_pago')
            ->get()
            ->mapWithKeys(function($item) {
                $nombres = [
                    'efectivo' => 'Efectivo',
                    'qr' => 'QR',
                    'transferencia' => 'Transferencia',
                    'tarjeta' => 'Tarjeta'
                ];
                return [$nombres[$item->metodo_pago] ?? $item->metodo_pago => $item->total];
            });

        // Productos más vendidos del mes
        $this->productos_mas_vendidos = DetalleVenta::select(
                'productos.id',
                'productos.nombre',
                'productos.codigo',
                DB::raw('SUM(detalle_ventas.cantidad) as total_cantidad'),
                DB::raw('SUM(detalle_ventas.subtotal * (ventas.total / NULLIF(ventas.subtotal, 0))) as total_venta')
            )
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', '!=', 'anulada')
            ->when($this->sucursal_id, function($query) {
                $query->where('ventas.sucursal_id', $this->sucursal_id);
            })
            ->when($this->vendedor_id, function($query) {
                $query->where('ventas.user_id', $this->vendedor_id);
            })
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderBy('total_cantidad', 'desc')
            ->limit(10)
            ->get();
    }

    public function generarReporteVendedores()
    {
        $fechaInicio = $this->fecha_desde ? Carbon::parse($this->fecha_desde)->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = $this->fecha_hasta ? Carbon::parse($this->fecha_hasta)->endOfDay() : Carbon::now()->endOfDay();

        $this->top_vendedores = User::select(
                'users.id',
                'users.name',
                DB::raw('COUNT(ventas.id) as total_ventas'),
                DB::raw('SUM(ventas.total) as total_ingresos'),
                DB::raw('AVG(ventas.total) as promedio_venta'),
                DB::raw('SUM(CASE WHEN ventas.tipo = "contado" THEN ventas.total ELSE 0 END) as contado'),
                DB::raw('SUM(CASE WHEN ventas.tipo = "credito" THEN ventas.total ELSE 0 END) as credito')
            )
            ->leftJoin('ventas', function($join) use ($fechaInicio, $fechaFin) {
                $join->on('users.id', '=', 'ventas.user_id')
                    ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
                    ->where('ventas.estado', '!=', 'anulada');
            })
            ->whereHas('roles', function($query) {
                $query->where('name', 'vendedor');
            })
            ->when($this->sucursal_id, function($query) {
                $query->where('ventas.sucursal_id', $this->sucursal_id);
            })
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_ingresos', 'desc')
            ->get();

        // Calcular totales
        $this->resumen = [
            'total_vendedores' => $this->top_vendedores->count(),
            'total_ventas' => $this->top_vendedores->sum('total_ventas'),
            'total_ingresos' => $this->top_vendedores->sum('total_ingresos'),
            'promedio_ventas' => $this->top_vendedores->avg('total_ventas'),
            'promedio_ingresos' => $this->top_vendedores->avg('total_ingresos'),
            'vendedor_top' => $this->top_vendedores->first(),
            'vendedor_bottom' => $this->top_vendedores->last(),
        ];

        // Productos más vendidos por vendedor (opcional, para comparativa)
        if ($this->vendedor_id) {
            $this->productos_mas_vendidos = DetalleVenta::select(
                    'productos.id',
                    'productos.nombre',
                    'productos.codigo',
                    DB::raw('SUM(detalle_ventas.cantidad) as total_cantidad'),
                    DB::raw('SUM(detalle_ventas.subtotal * (ventas.total / NULLIF(ventas.subtotal, 0))) as total_venta')
                )
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
                ->where('ventas.estado', '!=', 'anulada')
                ->where('ventas.user_id', $this->vendedor_id)
                ->when($this->sucursal_id, function($query) {
                    $query->where('ventas.sucursal_id', $this->sucursal_id);
                })
                ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
                ->orderBy('total_cantidad', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function exportarPDF()
    {
        abort_unless(Auth::user()->can('reportes.ventas'), 403);
        $this->generarReporte();

        $data = [
            'tipo_reporte' => $this->tipo_reporte,
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'mes_seleccionado' => $this->mes_seleccionado,
            'anio_seleccionado' => $this->anio_seleccionado,
            'sucursal' => $this->sucursal_id ? Sucursal::find($this->sucursal_id) : null,
            'vendedor' => $this->vendedor_id ? User::find($this->vendedor_id) : null,
            'ventas' => $this->ventas,
            'resumen' => $this->resumen,
            'top_vendedores' => $this->top_vendedores,
            'productos_mas_vendidos' => $this->productos_mas_vendidos,
            'metodos_pago' => $this->metodos_pago,
            'ventas_por_dia' => $this->ventas_por_dia,
            'fecha_generacion' => now(),
        ];

        $pdf = Pdf::loadView('admin.reportes.pdf.reporte_ventas', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $nombreArchivo = 'reporte_ventas_' . date('Ymd_His') . '.pdf';

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo);
    }

    private function asegurarAlcance(): void
    {
        $user = Auth::user();
        if (!$user->can('operaciones.todas-sucursales')) {
            $this->sucursal_id = $user->sucursal_id ?: null;
        }

        if ($this->sucursal_id && !$user->puedeGestionarSucursal((int) $this->sucursal_id)) {
            $this->sucursal_id = $user->sucursal_id ?: null;
        }

        if ($this->vendedor_id) {
            $vendedorValido = User::role('vendedor')
                ->whereKey($this->vendedor_id)
                ->when($this->sucursal_id, fn ($query) => $query->where('sucursal_id', $this->sucursal_id))
                ->exists();
            if (!$vendedorValido) {
                $this->vendedor_id = null;
            }
        }
    }

    public function exportarExcel()
    {
        // Implementar exportación a Excel si es necesario
        $this->dispatch('mostrar-alerta', [
            'icono' => 'info',
            'mensaje' => 'Funcionalidad de exportación a Excel en desarrollo'
        ]);
    }

    public function render()
    {
        return view('livewire.admin.ventas.reportes-ventas');
    }
}
