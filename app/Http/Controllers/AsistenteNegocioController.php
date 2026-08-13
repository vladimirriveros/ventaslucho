<?php

namespace App\Http\Controllers;

use App\Services\AsistenteNegocioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AsistenteNegocioController extends Controller
{
    public function __invoke(Request $request, AsistenteNegocioService $asistente): JsonResponse
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:800'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursals,id'],
        ]);

        try {
            $respuesta = $asistente->responder(
                $request->user(),
                $data['mensaje'],
                isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            );

            return response()->json($respuesta);
        } catch (ValidationException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'reply' => 'No pude completar la consulta en este momento. Intente nuevamente o formule la pregunta de otra manera.',
                'suggestions' => ['¿Cuánto vendimos hoy?', '¿Tenemos taladro?', 'Ayuda'],
            ], 500);
        }
    }
}
