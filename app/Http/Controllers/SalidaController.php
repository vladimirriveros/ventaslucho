<?php

namespace App\Http\Controllers;

// use App\Models\Compra;
use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use App\Models\Salida;
use App\Models\DetalleSalida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

class SalidaController extends Controller
{

    public function index()
    {
        $query = Salida::with(['sucursal', 'usuario'])
            ->latest('fecha')->latest('id');
        $user = Auth::user();
        if (!$user->can('operaciones.todas-sucursales')) {
            $user->sucursal_id
                ? $query->where('sucursal_id', $user->sucursal_id)
                : $query->whereRaw('1 = 0');
        }

        return view('admin.salidas.index', ['salidas' => $query->get()]);
    }


    public function create()
    {
        $user = Auth::user();
        abort_unless($user && $user->tieneSucursalOperativa(), 403, 'Su usuario debe tener una sucursal activa asignada.');

        return view('admin.salidas.create', ['sucursal' => $user->sucursal]);
    }


    public function store(Request $request)
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->tieneSucursalOperativa(), 403, 'Su usuario debe tener una sucursal activa asignada.');

        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $salida = new Salida();
        $salida->sucursal_id = (int) $usuario->sucursal_id;
        $salida->user_id = (int) $usuario->id;



        $salida->fecha = $datos['fecha'];
        $salida->motivo = $datos['motivo'];
        $salida->observaciones = $datos['observaciones'] ?? null;

        $salida->total = 0; // inicia en 0 (se calculará al agregar productos)
        $salida->estado = 'Pendiente';

        $salida->save();

        // Redirigir a editar (para agregar productos)
        return redirect()->route('salidas.edit', $salida->id)
            ->with('mensaje', 'Salida creada exitosamente. Ahora puede añadir productos.')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        $salida = Salida::findOrFail($id);
        abort_unless(Auth::user()->puedeOperarSucursal((int) $salida->sucursal_id), 403);

        return view('admin.salidas.edit', [
            'salida' => $salida,
            'productos' => Producto::where('estado', true)->orderBy('nombre')->get(),
        ]);
    }


    public function finalizarSalida(Request $request, Salida $salida)
    {
        abort_unless(Auth::user()->puedeOperarSucursal((int) $salida->sucursal_id), 403);

        try {
            DB::transaction(function () use ($salida) {
                $salida = Salida::query()->lockForUpdate()->findOrFail($salida->id);
                if ($salida->estado !== 'Pendiente') {
                    throw new \RuntimeException('La salida ya fue finalizada o cancelada.');
                }

                $detalles = $salida->detalles()->with(['producto', 'lote'])->orderBy('lote_id')->get();
                if ($detalles->isEmpty()) {
                    throw new \RuntimeException('No se puede finalizar una salida sin productos.');
                }

                $inventario = app(InventarioService::class);
                foreach ($detalles as $detalle) {
                    $observacion = 'Salida #' . $salida->id . ' por ' . strtolower($salida->motivo);
                    if ($salida->observaciones) {
                        $observacion .= ' - ' . $salida->observaciones;
                    }
                    $inventario->disminuir(
                        (int) $detalle->lote_id,
                        (int) $salida->sucursal_id,
                        (int) $detalle->cantidad,
                        Auth::id(),
                        Salida::class,
                        (int) $salida->id,
                        $observacion
                    );
                }

                $salida->update(['estado' => 'Entregado']);
            }, 3);

            return redirect()->route('salidas.index')
                ->with('mensaje', 'Salida #' . $salida->id . ' finalizada correctamente.')
                ->with('icono', 'success')
                ->with('nota_salida_url', route('salidas.nota-pdf', $salida->id))
                ->with('salida_id', $salida->id);
        } catch (\Throwable $e) {
            return back()->with('mensaje', $e->getMessage())->with('icono', 'error');
        }
    }


    public function show($id)
    {
        $salida = Salida::with(['usuario', 'sucursal', 'detalles.producto', 'detalles.lote'])->findOrFail($id);
        abort_unless(Auth::user()->puedeGestionarSucursal((int) $salida->sucursal_id), 403);

        return view('admin.salidas.show', compact('salida') + ['sucursal_destino' => null]);
    }


    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $salida = Salida::query()->lockForUpdate()->findOrFail($id);
                abort_unless(Auth::user()->puedeOperarSucursal((int) $salida->sucursal_id), 403);
                if ($salida->estado !== 'Pendiente') {
                    throw new \RuntimeException('No se puede eliminar una salida que ya afectó el inventario.');
                }
                $salida->detalles()->delete();
                $salida->delete();
            }, 3);

            return redirect()->route('salidas.index')
                ->with('mensaje', 'La salida pendiente fue eliminada.')
                ->with('icono', 'success');
        } catch (\Throwable $e) {
            return back()->with('mensaje', $e->getMessage())->with('icono', 'error');
        }
    }

    // Documentos de salida

/**
     * Generar PDF de nota de salida (para ver en navegador)
     */
    public function notaSalidaPdf($id)
    {
        $salida = Salida::with([
            'detalles.producto',
            'detalles.lote',
            'sucursal',
            'usuario'
        ])->findOrFail($id);
        abort_unless(Auth::user()->puedeGestionarSucursal((int) $salida->sucursal_id), 403);

        $pdf = Pdf::loadView('admin.salidas.pdf.nota_salida', [
            'salida' => $salida,
            'detalles' => $salida->detalles
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->stream('nota_salida_'.$salida->id.'.pdf');
    }

    /**
     * Descargar PDF de nota de salida
     */
    public function descargarNotaSalida($id)
    {
        $salida = Salida::with([
            'detalles.producto',
            'detalles.lote',
            'sucursal',
            'usuario'
        ])->findOrFail($id);
        abort_unless(Auth::user()->puedeGestionarSucursal((int) $salida->sucursal_id), 403);

        $pdf = Pdf::loadView('admin.salidas.pdf.nota_salida', [
            'salida' => $salida,
            'detalles' => $salida->detalles
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        return $pdf->download('nota_salida_'.$salida->id.'.pdf');
    }
}
