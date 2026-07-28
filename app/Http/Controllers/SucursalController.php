<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::withCount(['users', 'compras', 'ventas', 'salidas'])
            ->orderBy('nombre')
            ->get();

        return view('admin.sucursales.index', compact('sucursales'));
    }

    public function create()
    {
        return view('admin.sucursales.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:sucursals,nombre'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'activa' => ['required', 'boolean'],
        ]);

        Sucursal::create($validated);

        return redirect()->route('sucursales.index')
            ->with('mensaje', 'Sucursal creada exitosamente.')
            ->with('icono', 'success');
    }

    public function show($id)
    {
        $sucursal = Sucursal::withCount(['users', 'compras', 'ventas', 'salidas'])->findOrFail($id);

        return view('admin.sucursales.show', compact('sucursal'));
    }

    public function edit($id)
    {
        $sucursal = Sucursal::findOrFail($id);

        return view('admin.sucursales.edit', compact('sucursal'));
    }

    public function update(Request $request, $id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('sucursals', 'nombre')->ignore($sucursal->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'activa' => ['required', 'boolean'],
        ]);

        if (!$validated['activa']) {
            if ($sucursal->users()->whereNull('deleted_at')->exists()) {
                return back()->withInput()
                    ->with('mensaje', 'No se puede desactivar la sucursal mientras tenga usuarios activos asignados. Reasígnelos primero.')
                    ->with('icono', 'error');
            }
            if ($sucursal->cajas()->where('estado', 'abierta')->exists()) {
                return back()->withInput()
                    ->with('mensaje', 'No se puede desactivar la sucursal mientras tenga una caja abierta.')
                    ->with('icono', 'error');
            }
        }

        $sucursal->update($validated);

        return redirect()->route('sucursales.index')
            ->with('mensaje', 'Sucursal actualizada exitosamente.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $sucursal = Sucursal::findOrFail($id);

        $tieneHistorial = $sucursal->users()->withTrashed()->exists()
            || $sucursal->compras()->exists()
            || $sucursal->ventas()->exists()
            || $sucursal->salidas()->exists()
            || $sucursal->cajas()->exists()
            || $sucursal->inventarioSucuralLotes()->exists();

        if ($tieneHistorial) {
            return redirect()->route('sucursales.index')
                ->with('mensaje', 'La sucursal tiene usuarios, inventario u operaciones relacionadas y no puede eliminarse. Desactívela después de reasignar usuarios y cerrar caja.')
                ->with('icono', 'error');
        }

        $sucursal->delete();

        return redirect()->route('sucursales.index')
            ->with('mensaje', 'Sucursal eliminada exitosamente.')
            ->with('icono', 'success');
    }
}
