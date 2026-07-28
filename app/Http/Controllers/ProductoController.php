<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria', 'marca')->get();
        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::all();
        $marcas = Marca::orderBy('nombre')->get(); // 👈 Agregar marcas

        $ultimoProducto = Producto::orderBy('id', 'desc')->first();

        if ($ultimoProducto) {
            $ultimoCodigo = $ultimoProducto->codigo;
            $numero = intval(substr($ultimoCodigo, 4));
            $nuevoNumero = $numero + 1;
        } else {
            $nuevoNumero = 1;
        }

        $nuevoCodigo = 'PROD' . str_pad($nuevoNumero, 4, '0', STR_PAD_LEFT);

        return view('admin.productos.create', compact('categorias', 'marcas', 'nuevoCodigo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id', // 👈 Cambiado de 'marca' a 'marca_id'
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'porcentaje' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_maximo' => 'nullable|integer|min:0',
            'unidad_medida' => 'required|string|max:20',
            'norma' => 'nullable|string|max:100',
            'presion' => 'nullable|string|max:50',
            'diametro' => 'nullable|string|max:50',
        ]);

        $producto = new Producto();
        $producto->categoria_id = $request->categoria_id;
        $producto->marca_id = $request->marca_id; // 👈 Cambiado
        $producto->codigo = $request->codigo;
        $producto->nombre = mb_strtoupper($request->nombre, 'utf-8');
        $producto->descripcion = mb_strtoupper($request->descripcion ?? '', 'utf-8');

        $producto->norma = mb_strtoupper($request->norma ?? '', 'utf-8');
        $producto->presion = mb_strtoupper($request->presion ?? '', 'utf-8');
        $producto->diametro = mb_strtoupper($request->diametro ?? '', 'utf-8');

        if ($request->hasFile('imagen')) {
            $producto->imagen = $request->file('imagen')->store('images/productos', 'public');
        } else {
            $producto->imagen = 'images/productos/conserdei.png';
        }

        $producto->precio_compra = $request->precio_compra;
        $producto->precio_venta = $request->precio_venta;
        $producto->porcentaje_ganancia = $request->porcentaje;
        $producto->stock_minimo = $request->stock_minimo;
        $producto->stock_maximo = $request->stock_maximo;
        $producto->unidad_medida = $request->unidad_medida;
        $producto->estado = false;

        $producto->save();

        return redirect()->route('productos.index')
            ->with('mensaje', 'Producto creado exitosamente.')
            ->with('icono', 'success');
    }

    public function show($id)
    {
        $producto = Producto::with('categoria', 'marca')->findOrFail($id);
        return view('admin.productos.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        $categorias = Categoria::all();
        $marcas = Marca::orderBy('nombre')->get(); // 👈 Agregar marcas
        return view('admin.productos.edit', compact('producto', 'categorias', 'marcas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id', // 👈 Cambiado
            'codigo' => 'required|string|max:50|unique:productos,codigo,' . $id,
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'porcentaje' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_maximo' => 'nullable|integer|min:0',
            'unidad_medida' => 'required|string|max:20',
            'estado' => 'required|boolean',
            'norma' => 'nullable|string|max:100',
            'presion' => 'nullable|string|max:50',
            'diametro' => 'nullable|string|max:50',
        ]);

        $categoria = Categoria::find($request->categoria_id);
        if ($categoria && $categoria->nombre === 'PLOMERIA') {
            $request->validate([
                'norma' => 'required|string|max:100'
            ]);
        }

        $producto = Producto::findOrFail($id);
        $producto->categoria_id = $request->categoria_id;
        $producto->marca_id = $request->marca_id; // 👈 Cambiado
        $producto->codigo = $request->codigo;
        $producto->nombre = mb_strtoupper($request->nombre, 'utf-8');
        $producto->descripcion = mb_strtoupper($request->descripcion ?? '', 'utf-8');

        $producto->norma = mb_strtoupper($request->norma ?? '', 'utf-8');
        $producto->presion = mb_strtoupper($request->presion ?? '', 'utf-8');
        $producto->diametro = mb_strtoupper($request->diametro ?? '', 'utf-8');

        if ($request->hasFile('imagen')) {
            if ($producto->imagen && $producto->imagen !== 'images/productos/conserdei.png') {
                Storage::disk('public')->delete($producto->imagen);
            }
            $producto->imagen = $request->file('imagen')->store('images/productos', 'public');
        }

        $producto->precio_compra = $request->precio_compra;
        $producto->precio_venta = $request->precio_venta;
        $producto->porcentaje_ganancia = $request->porcentaje;
        $producto->stock_minimo = $request->stock_minimo;
        $producto->stock_maximo = $request->stock_maximo;
        $producto->unidad_medida = $request->unidad_medida;
        $producto->estado = $request->estado;

        $producto->save();

        return redirect()->route('productos.index')
            ->with('mensaje', 'Producto actualizado exitosamente.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $tieneHistorial = $producto->lotes()->exists()
            || $producto->movimientosInventario()->exists()
            || $producto->detalleCompras()->exists()
            || $producto->detalleSalidas()->exists();

        if ($tieneHistorial) {
            $producto->update(['estado' => false]);
            return back()->with('mensaje', 'El producto tiene historial; fue desactivado en lugar de eliminarse.')
                ->with('icono', 'info');
        }

        if ($producto->imagen && $producto->imagen !== 'images/productos/conserdei.png') {
            Storage::disk('public')->delete($producto->imagen);
        }
        $producto->delete();

        return redirect()->route('productos.index')
            ->with('mensaje', 'Producto sin movimientos eliminado correctamente.')
            ->with('icono', 'success');
    }

    public function desactivar(Producto $producto)
    {
        $producto->estado = false;
        $producto->save();

        return back()
            ->with('mensaje', 'Producto desactivado correctamente')
            ->with('icono', 'success');
    }

    public function verificarCodigo(Request $request)
    {
        $codigo = $request->input('codigo');
        $id = $request->input('id');

        $query = Producto::where('codigo', $codigo);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        $existe = $query->exists();

        return response()->json(['existe' => $existe]);
    }

    public function ultimoCodigo()
    {
        $ultimoProducto = Producto::orderBy('id', 'desc')->first();

        if ($ultimoProducto) {
            $ultimoCodigo = $ultimoProducto->codigo;
            $numero = intval(substr($ultimoCodigo, 4));
        } else {
            $numero = 0;
        }

        return response()->json(['ultimo_numero' => $numero]);
    }

    public function historialPrecios(Producto $producto)
    {
        return view('admin.productos.historial-precios', [
            'producto' => $producto
        ]);
    }
}
