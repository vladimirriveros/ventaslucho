<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimientoInventarioController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $movimientos = $this->consultaAutorizada($filtros)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        // Compatibilidad con las vistas antiguas que usan $movimiento->usuario.
        $movimientos->each(fn ($movimiento) => $movimiento->setRelation('usuario', $movimiento->user));

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.inventario.movimientos.partials.tabla', compact('movimientos'))->render(),
                'total' => $movimientos->count(),
            ]);
        }

        return view('admin.inventario.movimientos.index', compact('movimientos'));
    }

    public function generarPDF(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $movimientos = $this->consultaAutorizada($filtros)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $movimientos->each(fn ($movimiento) => $movimiento->setRelation('usuario', $movimiento->user));

        $data = [
            'movimientos' => $movimientos,
            'fecha_desde' => $filtros['fecha_desde']
                ? Carbon::parse($filtros['fecha_desde'])->format('d/m/Y')
                : 'Todo',
            'fecha_hasta' => $filtros['fecha_hasta']
                ? Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y')
                : 'Todo',
            'search' => $filtros['search'] ?: 'Sin filtro',
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'total_entradas' => $movimientos->where('tipo_movimiento', 'Entrada')->sum('cantidad'),
            'total_salidas' => $movimientos->where('tipo_movimiento', 'Salida')->sum('cantidad'),
            'total_movimientos' => $movimientos->count(),
            'usuario' => Auth::user()->name ?? 'Sistema',
        ];

        $pdf = Pdf::loadView('admin.inventario.movimientos.pdf', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => false,
        ]);

        return $pdf->download('kardex-' . now()->format('Y-m-d') . '.pdf');
    }

    private function consultaAutorizada(array $filtros): Builder
    {
        $user = Auth::user();

        return MovimientoInventario::query()
            ->with(['producto', 'lote', 'sucursal', 'user'])
            ->when(!$user->can('operaciones.todas-sucursales'), function (Builder $query) use ($user) {
                $user->sucursal_id
                    ? $query->where('sucursal_id', $user->sucursal_id)
                    : $query->whereRaw('1 = 0');
            })
            ->when($filtros['fecha_desde'], function (Builder $query) use ($filtros) {
                $query->where('fecha', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
            })
            ->when($filtros['fecha_hasta'], function (Builder $query) use ($filtros) {
                $query->where('fecha', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
            })
            ->when($filtros['search'], function (Builder $query) use ($filtros) {
                $search = $filtros['search'];
                $query->where(function (Builder $subquery) use ($search) {
                    $subquery->where('tipo_movimiento', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%")
                        ->orWhereHas('producto', fn (Builder $q) => $q
                            ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%"))
                        ->orWhereHas('lote', fn (Builder $q) => $q->where('codigo_lote', 'like', "%{$search}%"))
                        ->orWhereHas('sucursal', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            });
    }

    private function validarFiltros(Request $request): array
    {
        $validados = $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'search' => 'nullable|string|max:100',
        ]);

        // validate() solo devuelve claves presentes; se agregan valores por defecto
        // para evitar Undefined array key al abrir la pantalla sin filtros.
        return array_merge([
            'fecha_desde' => null,
            'fecha_hasta' => null,
            'search' => null,
        ], $validados);
    }
}
