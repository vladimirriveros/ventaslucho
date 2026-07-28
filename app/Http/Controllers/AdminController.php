<?php

namespace App\Http\Controllers;

use App\Models\Banca;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\Cotizacion;
use App\Models\DetalleCompra;
use App\Models\DetalleSalida;
use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Salida;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $accesoGlobal = $user->can('operaciones.todas-sucursales');
        $sucursalIds = Sucursal::query()
            ->where('activa', true)
            ->when(!$accesoGlobal, fn (Builder $query) => $user->sucursal_id
                ? $query->whereKey($user->sucursal_id)
                : $query->whereRaw('1 = 0'))
            ->pluck('id');

        $aplicarSucursal = static function (Builder $query, string $columna = 'sucursal_id') use ($accesoGlobal, $sucursalIds): Builder {
            return $accesoGlobal ? $query : $query->whereIn($columna, $sucursalIds);
        };

        $total_sucursales = $sucursalIds->count();
        $total_proveedores = Proveedor::count();

        $total_lotes_vencidos = Lote::query()
            ->whereDate('fecha_vencimiento', '<', today())
            ->whereHas('inventarioSucuralLotes', function (Builder $query) use ($accesoGlobal, $sucursalIds) {
                $query->where('cantidad_en_sucursal', '>', 0);
                if (!$accesoGlobal) {
                    $query->whereIn('sucursal_id', $sucursalIds);
                }
            })
            ->count();

        $comprasBase = $aplicarSucursal(Compra::query());
        $total_compras_lotes = DetalleCompra::query()
            ->join('compras', 'detalle_compras.compra_id', '=', 'compras.id')
            ->where('compras.estado', 'Recibido')
            ->when(!$accesoGlobal, fn ($query) => $query->whereIn('compras.sucursal_id', $sucursalIds))
            ->sum(DB::raw('detalle_compras.cantidad * detalle_compras.precio_unitario'));

        $compras_count = (clone $comprasBase)->count();
        $compras_pendientes = (clone $comprasBase)->where('estado', 'pendiente')->count();
        $compras_enviadas = (clone $comprasBase)->where('estado', 'enviado al proveedor')->count();
        $compras_recibidas = (clone $comprasBase)->where('estado', 'Recibido')->count();

        $salidasBase = $aplicarSucursal(Salida::query());
        $salidas_count = (clone $salidasBase)->count();
        $salidas_pendientes = (clone $salidasBase)->whereRaw('LOWER(estado) = ?', ['pendiente'])->count();
        $salidas_proceso = (clone $salidasBase)->whereRaw('LOWER(estado) = ?', ['en proceso'])->count();
        $salidas_entregadas = (clone $salidasBase)->whereRaw('LOWER(estado) = ?', ['entregado'])->count();

        $total_productos_inventario = Producto::query()
            ->where('estado', true)
            ->whereHas('lotes.inventarioSucuralLotes', function (Builder $query) use ($accesoGlobal, $sucursalIds) {
                $query->where('cantidad_en_sucursal', '>', 0);
                if (!$accesoGlobal) {
                    $query->whereIn('sucursal_id', $sucursalIds);
                }
            })
            ->count();

        $ventasValidas = $aplicarSucursal(Venta::query())->where('estado', '!=', 'anulada');
        $total_ventas = (clone $ventasValidas)->count();
        $total_ingresos_ventas = (float) (clone $ventasValidas)->sum('total');

        $resumenDetallesVenta = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->leftJoin('lotes', 'detalle_ventas.lote_id', '=', 'lotes.id')
            ->where('ventas.estado', '!=', 'anulada')
            ->when(!$accesoGlobal, fn ($query) => $query->whereIn('ventas.sucursal_id', $sucursalIds))
            ->selectRaw('COALESCE(SUM(detalle_ventas.cantidad), 0) AS total_productos')
            ->selectRaw('COALESCE(SUM(detalle_ventas.cantidad * COALESCE(detalle_ventas.costo_unitario, lotes.precio_compra, 0)), 0) AS total_costo')
            ->first();

        $total_productos_vendidos = (float) ($resumenDetallesVenta->total_productos ?? 0);
        $total_costo_compras = (float) ($resumenDetallesVenta->total_costo ?? 0);
        $total_ganancia_ventas = $total_ingresos_ventas - $total_costo_compras;

        $inventario_por_sucursal = [];
        foreach (Sucursal::query()->whereIn('id', $sucursalIds)->orderBy('nombre')->get() as $sucursal) {
            $productos_con_stock = InventarioSucuralLote::query()
                ->where('inventario_sucural_lotes.sucursal_id', $sucursal->id)
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->join('productos', 'lotes.producto_id', '=', 'productos.id')
                ->where('productos.estado', true)
                ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
                ->distinct()
                ->count('productos.id');

            $inventario_por_sucursal[$sucursal->id] = [
                'nombre' => $sucursal->nombre,
                'total_productos' => $productos_con_stock,
                'total_unidades' => InventarioSucuralLote::query()
                    ->where('sucursal_id', $sucursal->id)
                    ->where('cantidad_en_sucursal', '>', 0)
                    ->sum('cantidad_en_sucursal'),
            ];
        }

        $productos_mas_salidas = DetalleSalida::query()
            ->select(
                'productos.id',
                'productos.nombre as producto',
                'productos.codigo',
                DB::raw('SUM(detalle_salidas.cantidad) as total_vendido'),
                DB::raw('SUM(detalle_salidas.subtotal) as total_monto')
            )
            ->join('salidas', 'detalle_salidas.salida_id', '=', 'salidas.id')
            ->join('productos', 'detalle_salidas.producto_id', '=', 'productos.id')
            ->where('salidas.motivo', 'Venta')
            ->whereRaw('LOWER(salidas.estado) = ?', ['entregado'])
            ->when(!$accesoGlobal, fn ($query) => $query->whereIn('salidas.sucursal_id', $sucursalIds))
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        $total_cajas_abiertas = $aplicarSucursal(Caja::query())->where('estado', 'abierta')->count();
        $total_saldo_bancas = $user->can('bancas.index') ? (float) Banca::sum('saldo_actual') : 0.0;
        $total_cotizaciones_activas = $aplicarSucursal(Cotizacion::query())->where('estado', 'activa')->count();

        return view('admin.index', compact(
            'total_sucursales',
            'total_proveedores',
            'total_lotes_vencidos',
            'total_compras_lotes',
            'compras_count',
            'compras_pendientes',
            'compras_enviadas',
            'compras_recibidas',
            'salidas_count',
            'salidas_pendientes',
            'salidas_proceso',
            'salidas_entregadas',
            'total_productos_inventario',
            'inventario_por_sucursal',
            'productos_mas_salidas',
            'total_ventas',
            'total_cajas_abiertas',
            'total_saldo_bancas',
            'total_cotizaciones_activas',
            'total_productos_vendidos',
            'total_costo_compras',
            'total_ingresos_ventas',
            'total_ganancia_ventas'
        ));
    }
}
