<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:marcas,nombre'
        ]);

        try {
            $marca = Marca::create([
                'nombre' => strtoupper($request->nombre)
            ]);

            return response()->json([
                'success' => true,
                'marca' => [
                    'id' => $marca->id,
                    'nombre' => $marca->nombre
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
