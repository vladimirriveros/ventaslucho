<?php

namespace App\Http\Controllers;

use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventarioSucuralLoteController extends Controller
{
    public function index()
    {
        // Mantener el withCount como en el original
        $user = Auth::user();
        $sucursales = Sucursal::query()
            ->where('activa', true)
            ->when(!$user->can('operaciones.todas-sucursales'), function ($query) use ($user) {
                $user->sucursal_id ? $query->whereKey($user->sucursal_id) : $query->whereRaw('1 = 0');
            })
            ->withCount('inventarioSucuralLotes')
            ->orderBy('nombre')
            ->get();

        foreach ($sucursales as $sucursal) {
            $inventario = InventarioSucuralLote::where('sucursal_id', $sucursal->id)
                ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                ->join('productos', 'lotes.producto_id', '=', 'productos.id')
                ->where('productos.estado', true)
                ->select(
                    'productos.id',
                    'productos.stock_minimo',
                    DB::raw('SUM(inventario_sucural_lotes.cantidad_en_sucursal) as total_por_producto'),
                    DB::raw('COUNT(DISTINCT lotes.id) as total_lotes')
                )
                ->groupBy('productos.id', 'productos.stock_minimo')
                ->get();

            // ✅ CORRECCIÓN: Asignar a total_inventario (lo que usa la vista)
            $sucursal->total_inventario = $inventario->sum('total_por_producto');

            // Opcional: también puedes mantener el calculado si lo necesitas
            $sucursal->total_inventario_calculado = $inventario->sum('total_por_producto');

            $productos_bajo_stock = $this->consultaStockProductos((int) $sucursal->id)
                ->filter(fn ($item) => (int) $item->cantidad <= (int) $item->stock_minimo)
                ->count();

            $sucursal->tiene_stock_bajo = $productos_bajo_stock > 0;
            $sucursal->stock_bajo_count = $productos_bajo_stock;
        }

        return view('admin.inventario.sucursales_por_lotes.index', compact('sucursales'));
    }

    public function mostrar_inventario_por_sucursal($id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $this->autorizarSucursal((int) $id);

        $inventario_sucursal_por_lotes = InventarioSucuralLote::where('sucursal_id', $id)
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            // 👇 COMENTAR O ELIMINAR ESTA LÍNEA
            ->where('productos.estado', true)
            ->select(
                'productos.id as producto_id',
                'productos.codigo as codigo_producto',
                'productos.nombre as producto',
                'productos.stock_minimo',
                'productos.stock_maximo',
                'productos.estado', // Agregar esto para ver el estado
                DB::raw('SUM(inventario_sucural_lotes.cantidad_en_sucursal) as cantidad')
            )
            ->groupBy(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                'productos.stock_minimo',
                'productos.stock_maximo',
                'productos.estado' // Agregar esto al groupBy
            )
            ->get();

        return view('admin.inventario.sucursales_por_lotes.mostrar_inventario_por_sucursal', compact(
            'sucursal',
            'inventario_sucursal_por_lotes'
        ));
    }

    //**********************************************STOCK BAJO POR SUCURSAL ********************************************* */
    public function stock_bajo_por_sucursal($id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $this->autorizarSucursal((int) $id);

        $productos_stock_bajo = $this->consultaStockProductos((int) $id)
            ->filter(fn ($item) => (int) $item->cantidad <= (int) $item->stock_minimo)
            ->values();

        return view(
            'admin.inventario.sucursales_por_lotes.stock_bajo',
            compact('sucursal', 'productos_stock_bajo')
        );
    }



    // ... (otros métodos)

    /**
     * Generar PDF del inventario de una sucursal
     */
    /**
 * Generar PDF del inventario de una sucursal con estadísticas de movimientos
 */
    public function generarPDF($id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $this->autorizarSucursal((int) $id);

        // Obtener inventario agrupado por producto
        $inventario = InventarioSucuralLote::where('sucursal_id', $id)
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->where('productos.estado', true)
            ->select(
                'productos.id as producto_id',
                'productos.codigo as codigo_producto',
                'productos.nombre as producto',
                'productos.stock_minimo',
                DB::raw('SUM(inventario_sucural_lotes.cantidad_en_sucursal) as cantidad')
            )
            ->groupBy(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                'productos.stock_minimo'
            )
            ->orderBy('productos.nombre')
            ->get();

        // Agregar estadísticas de movimientos para cada producto
        foreach ($inventario as $item) {
            // Total de entradas para este producto en la sucursal
            $item->total_entradas = MovimientoInventario::where('producto_id', $item->producto_id)
                ->where('sucursal_id', $id)
                ->where('tipo_movimiento', 'Entrada')
                ->sum('cantidad');

            // Total de salidas para este producto en la sucursal
            $item->total_salidas = MovimientoInventario::where('producto_id', $item->producto_id)
                ->where('sucursal_id', $id)
                ->where('tipo_movimiento', 'Salida')
                ->sum('cantidad');

            // Último movimiento
            $ultimo = MovimientoInventario::where('producto_id', $item->producto_id)
                ->where('sucursal_id', $id)
                ->orderBy('fecha', 'desc')
                ->first();

            $item->ultimo_movimiento = $ultimo ? $ultimo->fecha : null;
        }

        // Calcular estadísticas generales
        $total_productos = $inventario->count();
        $total_items = $inventario->sum('cantidad');

        $productos_stock_bajo = $inventario->filter(function($item) {
            return $item->cantidad <= $item->stock_minimo && $item->cantidad > 0;
        })->count();

        $productos_sin_stock = $inventario->filter(function($item) {
            return $item->cantidad == 0;
        })->count();

        $productos_con_stock = $inventario->filter(function($item) {
            return $item->cantidad > 0;
        })->count();

        // Totales de movimientos
        $total_entradas = MovimientoInventario::where('sucursal_id', $id)
            ->where('tipo_movimiento', 'Entrada')
            ->sum('cantidad');

        $total_salidas = MovimientoInventario::where('sucursal_id', $id)
            ->where('tipo_movimiento', 'Salida')
            ->sum('cantidad');

        // Valor total del inventario (usando precio de compra)
        $valor_total_inventario = InventarioSucuralLote::where('sucursal_id', $id)
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->sum(DB::raw('inventario_sucural_lotes.cantidad_en_sucursal * lotes.precio_compra'));

        $data = [
            'sucursal' => $sucursal,
            'inventario' => $inventario,
            'total_productos' => $total_productos,
            'total_items' => $total_items,
            'productos_stock_bajo' => $productos_stock_bajo,
            'productos_sin_stock' => $productos_sin_stock,
            'productos_con_stock' => $productos_con_stock,
            'total_entradas' => $total_entradas,
            'total_salidas' => $total_salidas,
            'valor_total_inventario' => $valor_total_inventario,
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'observaciones' => 'Reporte generado desde el sistema'
        ];

        $pdf = Pdf::loadView('admin.inventario.sucursales_por_lotes.pdf', $data);

        // Configuración del PDF
        // $pdf->setPaper('A4', 'landscape'); // Horizontal para mejor visualización
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true
        ]);

        $nombre_archivo = 'inventario-' . str_replace(' ', '-', $sucursal->nombre) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($nombre_archivo);
        // return $pdf->stream($nombre_archivo); // Para ver en navegador
    }

    /**
 * Generar PDF de productos con stock bajo
 */
public function generarPDFStockBajo($id)
{
    $sucursal = Sucursal::findOrFail($id);
    $this->autorizarSucursal((int) $id);

    $productos_stock_bajo = $this->consultaStockProductos((int) $id)
        ->filter(fn ($item) => (int) $item->cantidad <= (int) $item->stock_minimo)
        ->sortBy('producto')
        ->values();

    // Calcular estadísticas
    $total_productos = $productos_stock_bajo->count();
    $total_unidades_faltantes = $productos_stock_bajo->sum(function($item) {
        return max(0, $item->stock_minimo - $item->cantidad);
    });
    $total_unidades_actuales = $productos_stock_bajo->sum('cantidad');
    $valor_reposicion = $productos_stock_bajo->sum(function($item) {
        // Obtener precio promedio del producto
        $precio_promedio = Lote::where('producto_id', $item->producto_id)
            ->where('estado', true)
            ->avg('precio_compra') ?? 0;
        return max(0, ($item->stock_minimo - $item->cantidad)) * $precio_promedio;
    });

    $data = [
        'sucursal' => $sucursal,
        'productos' => $productos_stock_bajo,
        'total_productos' => $total_productos,
        'total_unidades_faltantes' => $total_unidades_faltantes,
        'total_unidades_actuales' => $total_unidades_actuales,
        'valor_reposicion' => $valor_reposicion,
        'fecha_generacion' => now()->format('d/m/Y H:i:s'),
        'observaciones' => 'Reporte de productos con stock bajo - Requiere reposición urgente'
    ];

    $pdf = Pdf::loadView('admin.inventario.sucursales_por_lotes.stock_bajo_pdf', $data);

    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions([
        'defaultFont' => 'sans-serif',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

    $nombre_archivo = 'stock-bajo-' . str_replace(' ', '-', $sucursal->nombre) . '-' . date('Y-m-d') . '.pdf';

    return $pdf->download($nombre_archivo);
}

    private function autorizarSucursal(int $sucursalId): void
    {
        abort_unless(Auth::user()?->puedeGestionarSucursal($sucursalId), 403, 'No puede consultar ni operar en esta sucursal.');
    }

    private function consultaStockProductos(int $sucursalId)
    {
        return Producto::query()
            ->where('productos.estado', true)
            ->leftJoin('lotes', 'lotes.producto_id', '=', 'productos.id')
            ->leftJoin('inventario_sucural_lotes', function ($join) use ($sucursalId) {
                $join->on('inventario_sucural_lotes.lote_id', '=', 'lotes.id')
                    ->where('inventario_sucural_lotes.sucursal_id', '=', $sucursalId);
            })
            ->select(
                'productos.id as producto_id',
                'productos.codigo as codigo_producto',
                'productos.nombre as producto',
                'productos.stock_minimo',
                DB::raw("COALESCE(SUM(CASE WHEN lotes.estado = 1 AND (lotes.fecha_vencimiento IS NULL OR lotes.fecha_vencimiento >= '" . today()->toDateString() . "') THEN inventario_sucural_lotes.cantidad_en_sucursal ELSE 0 END), 0) as cantidad")
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'productos.stock_minimo')
            ->get();
    }

}
