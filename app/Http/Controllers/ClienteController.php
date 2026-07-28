<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::orderBy('nombre')->paginate(10);
        return view('admin.clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'nit' => 'nullable|string|max:50|unique:clientes,nit',
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'tipo' => 'required|in:regular,credito',
            'limite_credito' => 'required_if:tipo,credito|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $cliente = Cliente::create([
                'nombre' => strtoupper($request->nombre),
                'nit' => $request->nit ?: null,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'tipo' => $request->tipo,
                'limite_credito' => $request->tipo == 'credito' ? $request->limite_credito : 0,
                'saldo_pendiente' => 0,
                'activo' => true,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()->route('clientes.index')
                ->with('mensaje', 'Cliente creado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cliente = Cliente::with('ventas')->findOrFail($id);
        return view('admin.clientes.show', compact('cliente'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('admin.clientes.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'nit' => 'nullable|string|max:50|unique:clientes,nit,' . $id,
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'tipo' => 'required|in:regular,credito',
            'limite_credito' => 'required_if:tipo,credito|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $cliente->update([
                'nombre' => strtoupper($request->nombre),
                'nit' => $request->nit ?: null,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'tipo' => $request->tipo,
                'limite_credito' => $request->tipo == 'credito' ? $request->limite_credito : 0,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()->route('clientes.index')
                ->with('mensaje', 'Cliente actualizado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);

        // Verificar si tiene ventas asociadas
        if ($cliente->ventas()->count() > 0) {
            return redirect()->back()
                ->with('mensaje', 'No se puede eliminar el cliente porque tiene ventas asociadas.')
                ->with('icono', 'warning');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('mensaje', 'Cliente eliminado exitosamente.')
            ->with('icono', 'success');
    }

    /**
     * Activar/Desactivar cliente
     */
    public function toggleActivo($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->activo = !$cliente->activo;
        $cliente->save();

        $estado = $cliente->activo ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('mensaje', "Cliente {$estado} exitosamente.")
            ->with('icono', 'success');
    }

    /**
     * Buscar clientes (para autocomplete)
     */
    public function buscar(Request $request)
    {
        $search = $request->get('q');

        $clientes = Cliente::where('activo', true)
            ->where(function($query) use ($search) {
                $query->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('nit', 'LIKE', "%{$search}%")
                    ->orWhere('telefono', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'nombre', 'nit', 'telefono']);

        return response()->json($clientes);
    }
}
