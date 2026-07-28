<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Marca;  // <-- AGREGAR ESTA LÍNEA
use App\Models\TipoCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoCambioController extends Controller
{
    public function index()
    {
        $tipoCambio = TipoCambio::orderBy('created_at', 'desc')->get();
        $tipoCambioOficial = TipoCambio::getOficial();
        $tipoCambioActivo = TipoCambio::getActivo();

        // Obtener categorías y marcas
        $categorias = Categoria::orderBy('nombre')->get();
        // CORREGIDO: Obtener marcas desde la tabla marcas (no desde productos.marca)
        $marcas = Marca::orderBy('nombre')->pluck('nombre');

        $ultimoTipoCambioUsado = session('ultimo_tipo_cambio_usado');
        $ultimaAccion = session('ultima_accion');
        $ultimosFiltros = session('ultimos_filtros'); // <-- NUEVO

        // Si hay filtros guardados, obtener los nombres
        $nombresCategorias = [];
        $nombresMarcas = [];

        if ($ultimosFiltros && isset($ultimosFiltros['categorias'])) {
            $nombresCategorias = Categoria::whereIn('id', $ultimosFiltros['categorias'])
                                    ->pluck('nombre')
                                    ->toArray();
        }

        if ($ultimosFiltros && isset($ultimosFiltros['marcas'])) {
            $nombresMarcas = $ultimosFiltros['marcas']; // Ya son strings
        }

        return view('admin.tipo_cambio.index', compact(
            'tipoCambio',
            'tipoCambioOficial',
            'tipoCambioActivo',
            'categorias',
            'marcas',
            'ultimoTipoCambioUsado',
            'ultimaAccion',
            'ultimosFiltros',
            'nombresCategorias',
            'nombresMarcas'
        ));
    }

    /**
     * Crear un nuevo tipo de cambio (siempre inactivo, no oficial)
     */
    public function store(Request $request)
    {
        $request->validate([
            'precio' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            TipoCambio::create([
                'precio_dolar' => $request->precio,
                'fecha' => now(),
                'estado' => false,
                'is_oficial' => false,
            ]);

            DB::commit();

            return redirect()->route('tipo_cambio.index')
                ->with('mensaje', 'Tipo de cambio alternativo creado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    /**
     * Editar un tipo de cambio (solo precio, no afecta productos)
     */
    public function update(Request $request, TipoCambio $tipoCambio)
    {
        $request->validate([
            'precio' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            // Si es el oficial, permitir editar sin restricciones
            $tipoCambio->update([
                'precio_dolar' => $request->precio,
            ]);

            DB::commit();

            return redirect()->route('tipo_cambio.index')
                ->with('mensaje', 'Tipo de cambio actualizado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    /**
     * Establecer un tipo de cambio como oficial
     */
    public function setOficial(Request $request)
    {
        $request->validate([
            'tipo_cambio_id' => 'required|exists:tipo_cambios,id',
        ]);

        DB::beginTransaction();

        try {
            // Quitar oficial a todos
            TipoCambio::where('is_oficial', true)->update(['is_oficial' => false]);

            // Establecer el nuevo oficial
            $tipoCambio = TipoCambio::find($request->tipo_cambio_id);
            $tipoCambio->is_oficial = true;
            $tipoCambio->save();

            DB::commit();

            return redirect()->route('tipo_cambio.index')
                ->with('mensaje', '✅ Tipo de cambio oficial actualizado a 1 USD = ' . number_format($tipoCambio->precio_dolar, 2) . ' Bs')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    public function actualizarPreciosVenta(Request $request)
{
    $request->validate([
        'tipo_cambio_id' => 'required|exists:tipo_cambios,id',
        'categorias' => 'nullable|array',
        'categorias.*' => 'exists:categorias,id',
        'marcas' => 'nullable|array',
        'aplicar_a' => 'required|in:todos,seleccionados',
    ]);

    DB::beginTransaction();

    try {
        $tipoCambioOficial = TipoCambio::getOficial();

        if (!$tipoCambioOficial) {
            return redirect()->back()
                ->with('mensaje', '❌ No hay un tipo de cambio oficial definido.')
                ->with('icono', 'error');
        }

        $nuevoTipoCambio = TipoCambio::find($request->tipo_cambio_id);
        $factor = $nuevoTipoCambio->precio_dolar / $tipoCambioOficial->precio_dolar;

        // ============================================
        // 1. ACTUALIZAR PRODUCTOS
        // ============================================
        $productosQuery = Producto::query();

        if ($request->aplicar_a == 'seleccionados') {
            if (!empty($request->categorias)) {
                $productosQuery->whereIn('categoria_id', $request->categorias);
            }
            if (!empty($request->marcas)) {
                // CORREGIDO: Filtrar por marca_id usando los IDs de las marcas seleccionadas
                    $marcasIds = Marca::whereIn('nombre', $request->marcas)->pluck('id');
                    $productosQuery->whereIn('marca_id', $marcasIds);
            }
        }

        $productos = $productosQuery->get();
        $productosActualizados = 0; // 👈 INICIALIZAR AQUÍ

        foreach ($productos as $producto) {
            $precioBase = $producto->precio_compra * $factor;
            $nuevoPrecioVenta = round($precioBase * (1 + $producto->porcentaje_ganancia / 100), 2);
            $producto->precio_venta = $nuevoPrecioVenta;
            $producto->save();
            $productosActualizados++;
        }

        // ============================================
        // 2. ACTUALIZAR LOTES
        // ============================================
        $lotesQuery = Lote::query()
            ->where('cantidad_actual', '>', 0);

        if ($request->aplicar_a == 'seleccionados') {
            $lotesQuery->whereHas('producto', function($query) use ($request) {
                if (!empty($request->categorias)) {
                    $query->whereIn('categoria_id', $request->categorias);
                }
                if (!empty($request->marcas)) {
                    // CORREGIDO: Filtrar por marca_id
                        $marcasIds = Marca::whereIn('nombre', $request->marcas)->pluck('id');
                        $query->whereIn('marca_id', $marcasIds);
                }
            });
        }

        $lotes = $lotesQuery->get();
        $lotesActualizados = 0; // 👈 INICIALIZAR AQUÍ

        foreach ($lotes as $lote) {
            $producto = $lote->producto;
            $porcentajeGanancia = $producto->porcentaje_ganancia ?? 30;

            $precioBase = $lote->precio_compra * $factor;
            $nuevoPrecioVenta = round($precioBase * (1 + $porcentajeGanancia / 100), 2);
            $lote->precio_venta = $nuevoPrecioVenta;
            $lote->save();
            $lotesActualizados++;
        }

        // ============================================
        // 3. ACTIVAR TIPO DE CAMBIO
        // ============================================
        TipoCambio::where('estado', true)->update(['estado' => false]);
        $nuevoTipoCambio->estado = true;
        $nuevoTipoCambio->save();

        DB::commit();

        $mensaje = "✅ ACTUALIZACIÓN COMPLETA<br>";
        $mensaje .= "💰 Tipo de cambio: 1 USD = " . number_format($nuevoTipoCambio->precio_dolar, 2) . " Bs<br>";
        $mensaje .= "📦 Productos actualizados: {$productosActualizados}<br>";
        $mensaje .= "🏷️ Lotes actualizados: {$lotesActualizados}";

        return redirect()->route('tipo_cambio.index')
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()
            ->with('mensaje', 'Error: ' . $e->getMessage())
            ->with('icono', 'error');
    }
}

    /**
     * Activar un tipo de cambio (para mostrar precios)
     */
    public function activar(Request $request)
    {
        $request->validate([
            'tipo_cambio_id' => 'required|exists:tipo_cambios,id',
        ]);

        DB::beginTransaction();

        try {
            // Desactivar todos
            TipoCambio::where('estado', true)->update(['estado' => false]);

            // Activar el seleccionado
            $tipoCambio = TipoCambio::find($request->tipo_cambio_id);
            $tipoCambio->estado = true;
            $tipoCambio->save();

            DB::commit();

            return redirect()->route('tipo_cambio.index')
                ->with('mensaje', '✅ Tipo de cambio activado: 1 USD = ' . number_format($tipoCambio->precio_dolar, 2) . ' Bs')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    /**
     * Recalcular precios de venta según % de ganancia actual
     * (manteniendo el tipo de cambio actual)
     */
    public function recalcularPorGanancia()
    {
        $productos = Producto::all();
        $contador = 0;

        foreach ($productos as $producto) {
            $producto->precio_venta = round($producto->precio_compra * (1 + $producto->porcentaje_ganancia / 100), 2);
            $producto->save();
            $contador++;
        }

        return redirect()->route('tipo_cambio.index')
            ->with('mensaje', "✅ Precios de venta recalculados según % de ganancia. {$contador} productos actualizados.")
            ->with('icono', 'success');
    }

    public function destroy(TipoCambio $tipoCambio)
    {
        try {
            // 🔴 NUEVA VALIDACIÓN: No permitir eliminar el oficial
            if ($tipoCambio->is_oficial) {
                return redirect()->back()
                    ->with('mensaje', '❌ No se puede eliminar el tipo de cambio oficial. Debes establecer otro como oficial primero.')
                    ->with('icono', 'error');
            }

            // No permitir eliminar si está activo (ya existía)
            if ($tipoCambio->estado) {
                return redirect()->back()
                    ->with('mensaje', '❌ No se puede eliminar el tipo de cambio activo.')
                    ->with('icono', 'warning');
            }

            $tipoCambio->delete();

            return redirect()->route('tipo_cambio.index')
                ->with('mensaje', '✅ Tipo de cambio eliminado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('mensaje', 'Error: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }
}
