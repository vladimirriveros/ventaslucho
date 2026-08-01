<?php

namespace App\Http\Controllers;

use App\Services\AlertaInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertaController extends Controller
{
    public function index(Request $request, AlertaInventarioService $alertas): View
    {
        return view('admin.alertas.index', [
            'alertas' => $alertas->resumen($request->user()),
        ]);
    }

    public function resumen(Request $request, AlertaInventarioService $alertas): JsonResponse
    {
        $data = $alertas->resumen($request->user());

        return response()->json([
            'alerta' => $data['alerta'],
            'total' => $data['total'],
            'stock_bajo' => $data['stock_bajo'],
            'lotes_por_vencer' => $data['lotes_por_vencer'],
            'lotes_vencidos' => $data['lotes_vencidos'],
            'dias_vencimiento' => $data['dias_vencimiento'],
            'alcance' => $data['alcance'],
        ]);
    }

    public function stock(): RedirectResponse
    {
        return redirect()->route('alertas.index', ['seccion' => 'stock']);
    }

    public function lotes(): RedirectResponse
    {
        return redirect()->route('alertas.index', ['seccion' => 'lotes']);
    }
}
