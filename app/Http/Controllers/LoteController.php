<?php

namespace App\Http\Controllers;

use App\Models\DetalleCompra;
use App\Models\InventarioSucuralLote;
use App\Models\Lote;
use App\Models\Sucursal;
use App\Models\Salida;
use App\Services\InventarioService;
// use App\Models\DetalleSalida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
// use Illuminate\Support\Facades\Log; // 👈 AGREGAR ESTA LÍNEA
// use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    public function index(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');
        $search = $request->input('search');

        // Log para depuración

        $usuario = Auth::user();
        $sucursalUsuario = $usuario->can('operaciones.todas-sucursales') ? null : (int) ($usuario->sucursal_id ?? 0);

        $query = Lote::with([
            'producto.categoria',
            'producto.marca',
            'proveedor',
            'inventarioSucuralLotes' => function ($inventarios) use ($sucursalUsuario) {
                $inventarios->when($sucursalUsuario > 0, fn ($q) => $q->where('sucursal_id', $sucursalUsuario))
                    ->with('sucursal');
            },
        ])->when(!$usuario->can('operaciones.todas-sucursales'), function ($lotes) use ($sucursalUsuario) {
            $sucursalUsuario > 0
                ? $lotes->whereHas('inventarioSucuralLotes', fn ($q) => $q->where('sucursal_id', $sucursalUsuario))
                : $lotes->whereRaw('1 = 0');
        });

        // FILTRO POR FECHA DE ENTRADA (corregido)
        if ($fecha_desde && $fecha_hasta) {
            // Asegurar que las fechas incluyan todo el día
            $fecha_desde_obj = Carbon::parse($fecha_desde)->startOfDay();
            $fecha_hasta_obj = Carbon::parse($fecha_hasta)->endOfDay();

            // Cambiado de fecha_vencimiento a fecha_entrada
            $query->whereBetween('fecha_entrada', [$fecha_desde_obj, $fecha_hasta_obj]);

        } elseif ($fecha_desde || $fecha_hasta) {
            // Si solo una fecha está presente, devolver error y DETENER ejecución
            if ($request->ajax()) {
                return response()->json([
                    'html' => '<tr><td colspan="14" class="text-center text-danger">Debe seleccionar ambas fechas</td></tr>',
                    'total' => 0
                ]);
            } else {
                return redirect()->back()->with('mensaje', 'Debe seleccionar ambas fechas')->with('icono', 'warning');
            }
        }

        // Obtener todos los lotes
        $lotes = $query->get();

        // Log del total antes del filtrado

        // Calcular propiedades adicionales
        $lotes->each(function ($lote) {
            if ($lote->fecha_vencimiento) {
                $hoy = Carbon::today();
                $lote->is_expired = $lote->fecha_vencimiento->isPast();
                $lote->day_to_expired = $hoy->diffInDays($lote->fecha_vencimiento, false);
            } else {
                $lote->is_expired = false;
                $lote->day_to_expired = null;
            }

            // Calcular estado como texto
            if ($lote->cantidad_actual <= 0) {
                $lote->estado_texto = 'terminado';
                $lote->estado_original = 'Lote terminado';
            } elseif ($lote->is_expired) {
                $lote->estado_texto = 'vencido';
                $lote->estado_original = 'Vencido';
            } elseif ($lote->day_to_expired !== null && $lote->day_to_expired <= 3) {
                $lote->estado_texto = 'por caducar';
                $lote->estado_original = 'Por caducar';
            } else {
                $lote->estado_texto = 'vigente';
                $lote->estado_original = 'Vigente';
            }
        });

        // Aplicar filtro de búsqueda GENERAL (incluyendo estado)
        if ($search && $search !== '') {
            $searchLower = trim(strtolower($search));
            $searchTerms = explode(' ', $searchLower);

            $lotes = $lotes->filter(function($lote) use ($searchLower, $searchTerms) {
                $textoBusqueda = strtolower(
                    $lote->codigo_lote . ' ' .
                    ($lote->producto->nombre ?? '') . ' ' .
                    ($lote->producto->codigo ?? '') . ' ' .
                    ($lote->producto->categoria->nombre ?? '') . ' ' .
                    ($lote->proveedor->nombre ?? '') . ' ' .
                    ($lote->proveedor->empresa ?? '') . ' ' .
                    $lote->estado_texto . ' ' .
                    $lote->estado_original
                );

                if (str_contains($textoBusqueda, $searchLower)) {
                    return true;
                }

                foreach ($searchTerms as $term) {
                    if (strlen($term) > 1 && str_contains($textoBusqueda, $term)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        // Log del total después del filtrado

        // Si es una petición AJAX, devolver solo el HTML de la tabla
        if ($request->ajax()) {
            $html = view('admin.lotes.partials.tabla', compact('lotes'))->render();
            return response()->json([
                'html' => $html,
                'total' => $lotes->count()
            ]);
        }

        return view('admin.lotes.index', compact('lotes'));
    }


    public function actualizar(Request $request, $id)
    {
        $datos = $request->validate([
            'fecha_vencimiento' => ['nullable', 'date'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'set_null' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($id, $datos, $request) {
                $lote = Lote::query()->lockForUpdate()->findOrFail($id);
                $this->autorizarLote($lote);

                $lote->fecha_vencimiento = $request->boolean('set_null')
                    ? null
                    : ($datos['fecha_vencimiento'] ?? null);
                $lote->precio_compra = round((float) $datos['precio_unitario'], 2);
                $lote->save();

                $detalle = DetalleCompra::query()->where('lote_id', $lote->id)->lockForUpdate()->first();
                if ($detalle) {
                    $detalle->precio_unitario = $lote->precio_compra;
                    $detalle->subtotal = round((int) $detalle->cantidad * $lote->precio_compra, 2);
                    $detalle->save();

                    $compra = $detalle->compra()->lockForUpdate()->first();
                    if ($compra) {
                        $compra->update(['total' => round((float) $compra->detalles()->sum('subtotal'), 2)]);
                    }
                }
            }, 3);

            return redirect()->back()->with('mensaje', 'Lote actualizado sin alterar las existencias.')->with('icono', 'success');
        } catch (\Throwable $e) {
            return redirect()->back()->with('mensaje', $e->getMessage())->with('icono', 'error');
        }
    }

    public function vencidos_index()
    {
        $hoy = \Carbon\Carbon::today(); // 🔥 fecha actual sin hora

        $productos_vencidos = InventarioSucuralLote::join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->join('sucursals', 'inventario_sucural_lotes.sucursal_id', '=', 'sucursals.id')
            ->whereDate('lotes.fecha_vencimiento', '<=', $hoy) // 🔥 menor a hoy
            ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0) // 🔥 que aún tenga stock
            ->select(
                'productos.id as producto_id',
                'lotes.id as lote_id',
                'inventario_sucural_lotes.sucursal_id',
                'productos.codigo as codigo_producto',
                'productos.nombre as producto',
                'lotes.codigo_lote as lote',
                'lotes.fecha_vencimiento',
                'inventario_sucural_lotes.cantidad_en_sucursal as cantidad',
                'inventario_sucural_lotes.sucursal_id',
                'sucursals.nombre as sucursal'
            )
            ->when(!Auth::user()->can('operaciones.todas-sucursales'), function ($query) {
                Auth::user()->sucursal_id
                    ? $query->where('inventario_sucural_lotes.sucursal_id', Auth::user()->sucursal_id)
                    : $query->whereRaw('1 = 0');
            })
            ->orderBy('lotes.fecha_vencimiento', 'asc')
            ->get();

        return view('admin.lotes.vencidos', compact('productos_vencidos'));
    }
//************************************************************************************************************************************** ***********************************+*/
//************************************************************************************************************************************** ***********************************+*/
//************************************************************************************************************************************** ***********************************+*/
//************************************************************************************************************************************** ***********************************+*/

    public function vencidos_sucursal($id)
    {
        $sucursal = Sucursal::query()->whereKey($id)->where('activa', true)->firstOrFail();
        $this->autorizarSucursal((int) $id);
        $hoy = now()->format('Y-m-d');

        // ============================================
        // PASO 1: OBTENER TODOS LOS PRODUCTOS VENCIDOS DE ESTA SUCURSAL
        // ============================================
        $todos_productos_vencidos = InventarioSucuralLote::join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->join('sucursals', 'inventario_sucural_lotes.sucursal_id', '=', 'sucursals.id')
            ->where('inventario_sucural_lotes.sucursal_id', $id)
            ->whereDate('lotes.fecha_vencimiento', '<=', $hoy)
            ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
            ->select(
                'lotes.id as lote_id',
                'productos.id as producto_id',
                'productos.codigo as codigo_producto',
                'productos.nombre as producto',
                'lotes.codigo_lote as codigo_lote',
                'lotes.fecha_vencimiento',
                'inventario_sucural_lotes.cantidad_en_sucursal as cantidad',
                'sucursals.nombre as sucursal',
                'lotes.precio_compra'
            )
            ->get()
            ->keyBy('lote_id');

        // ============================================
        // PASO 2: OBTENER CARRITO DE SESSION (SIN AUTO-INICIALIZAR)
        // ============================================
        $sessionKey = 'carrito_vencidos_sucursal_' . $id;
        $carrito = session($sessionKey, []); // Comienza VACÍO si no existe

        // ============================================
        // PASO 3: PRODUCTOS DISPONIBLES = TOTAL - CARRITO
        // ============================================
        $productos_vencidos_transformados = collect();

        foreach ($todos_productos_vencidos as $lote_id => $item) {
            if (!isset($carrito[$lote_id])) {
                $productos_vencidos_transformados->push((object) [
                    'lote_id' => $item->lote_id,
                    'codigo_lote' => $item->codigo_lote,
                    'producto_id' => $item->producto_id,
                    'producto' => $item->producto,
                    'codigo_producto' => $item->codigo_producto,
                    'fecha_vencimiento' => $item->fecha_vencimiento,
                    'cantidad' => $item->cantidad,
                    'precio_compra' => $item->precio_compra ?? 0,
                    'perdida' => $item->cantidad * ($item->precio_compra ?? 0),
                    'sucursal_id' => $id,
                    'sucursal_nombre' => $item->sucursal,
                ]);
            }
        }

        // ============================================
        // PASO 4: PROCESAR CARRITO PARA LA VISTA
        // ============================================
        $detalles_carrito = collect();
        $total_perdida = 0;

        foreach ($carrito as $lote_id => $item) {
            $perdida = $item['cantidad'] * $item['precio_compra'];
            $total_perdida += $perdida;

            $detalles_carrito->push((object) [
                'id' => 'temp_' . $lote_id,
                'producto' => $item['producto'],
                'codigo_lote' => $item['codigo_lote'],
                'fecha_vencimiento' => $item['fecha_vencimiento'],
                'cantidad' => $item['cantidad'],
                'precio_compra' => $item['precio_compra'],
                'perdida' => $perdida,
                'lote_id' => $lote_id,
            ]);
        }

        return view('admin.lotes.vencidos_sucursal', compact(
            'sucursal',
            'productos_vencidos_transformados',
            'detalles_carrito',
            'total_perdida',
            'sessionKey'
        ));
    }

    /**
     * Agregar un producto vencido a la salida
     */
    public function agregarASalida(Request $request)
    {
        $request->validate([
            'lote_id' => 'required|exists:lotes,id',
            'sucursal_id' => 'required|exists:sucursals,id,activa,1',
            'session_key' => 'required|string|max:100',
            'cantidad' => 'required|integer|min:1',
        ]);

        $sucursalId = (int) $request->sucursal_id;
        $this->autorizarSucursal($sucursalId);
        $sessionKeyEsperada = 'carrito_vencidos_sucursal_' . $sucursalId;
        abort_unless(hash_equals($sessionKeyEsperada, (string) $request->session_key), 422, 'La sesión del carrito no es válida.');

        // Verificar que el lote realmente pertenezca a la sucursal y tenga stock
        $inventario = InventarioSucuralLote::where('lote_id', $request->lote_id)
            ->where('sucursal_id', $request->sucursal_id)
            ->where('cantidad_en_sucursal', '>=', $request->cantidad)
            ->first();

        if (!$inventario) {
            return redirect()->back()
                ->with('mensaje', 'El producto no tiene suficiente stock en esta sucursal')
                ->with('icono', 'error');
        }

        $sessionKey = $request->session_key;
        $carrito = session($sessionKey, []);

        // Verificar si ya existe en el carrito
        if (isset($carrito[$request->lote_id])) {
            return redirect()->back()
                ->with('mensaje', 'Este producto ya está en el carrito')
                ->with('icono', 'warning');
        }

        // Obtener datos del lote
        $lote = Lote::with('producto')->findOrFail($request->lote_id);
        if (!$lote->fecha_vencimiento || !$lote->fecha_vencimiento->lte(today())) {
            return back()->with('mensaje', 'El lote seleccionado todavía no está vencido.')->with('icono', 'error');
        }

        // Agregar al carrito
        $carrito[$request->lote_id] = [
            'lote_id' => $lote->id,
            'codigo_lote' => $lote->codigo_lote,
            'producto_id' => $lote->producto_id,
            'producto' => $lote->producto->nombre,
            'codigo_producto' => $lote->producto->codigo,
            'fecha_vencimiento' => $lote->fecha_vencimiento,
            'cantidad' => $request->cantidad,
            'precio_compra' => $lote->precio_compra ?? 0,
            'perdida' => $request->cantidad * ($lote->precio_compra ?? 0),
            'sucursal_id' => $request->sucursal_id,
        ];

        session([$sessionKey => $carrito]);

        return redirect()->route('lotes.vencidos.sucursal', $request->sucursal_id)
            ->with('mensaje', 'Producto agregado al carrito')
            ->with('icono', 'success');
    }



    /**
     * Agregar TODOS los productos vencidos a la salida de una sola vez
     */
    public function agregarTodosASalida(Request $request)
    {
        $request->validate([
            'sucursal_id' => 'required|exists:sucursals,id,activa,1',
            'session_key' => 'required|string|max:100',
        ]);

        $sucursal_id = (int) $request->sucursal_id;
        $this->autorizarSucursal($sucursal_id);
        $sessionKey = 'carrito_vencidos_sucursal_' . $sucursal_id;
        abort_unless(hash_equals($sessionKey, (string) $request->session_key), 422, 'La sesión del carrito no es válida.');
        $hoy = now()->format('Y-m-d');

        // Obtener todos los productos vencidos de esta sucursal
        $productos_vencidos = InventarioSucuralLote::join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->where('inventario_sucural_lotes.sucursal_id', $sucursal_id)
            ->whereDate('lotes.fecha_vencimiento', '<=', $hoy)
            ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
            ->select(
                'lotes.id as lote_id',
                'lotes.producto_id',
                'lotes.codigo_lote',
                'lotes.fecha_vencimiento',
                'lotes.precio_compra',
                'productos.nombre as producto_nombre',
                'productos.codigo as producto_codigo',
                'inventario_sucural_lotes.cantidad_en_sucursal as cantidad'
            )
            ->get();

        if ($productos_vencidos->isEmpty()) {
            return redirect()->back()
                ->with('mensaje', 'No hay productos vencidos para agregar')
                ->with('icono', 'warning');
        }

        // Obtener carrito actual
        $carrito = session($sessionKey, []);
        $contador = 0;
        $ya_existentes = 0;

        foreach ($productos_vencidos as $producto) {
            // Verificar si ya está en el carrito
            if (!isset($carrito[$producto->lote_id])) {
                $carrito[$producto->lote_id] = [
                    'lote_id' => $producto->lote_id,
                    'codigo_lote' => $producto->codigo_lote,
                    'producto_id' => $producto->producto_id,
                    'producto' => $producto->producto_nombre,
                    'codigo_producto' => $producto->producto_codigo,
                    'fecha_vencimiento' => $producto->fecha_vencimiento,
                    'cantidad' => $producto->cantidad,
                    'precio_compra' => $producto->precio_compra ?? 0,
                    'perdida' => $producto->cantidad * ($producto->precio_compra ?? 0),
                    'sucursal_id' => $sucursal_id,
                ];
                $contador++;
            } else {
                $ya_existentes++;
            }
        }

        // Guardar carrito actualizado
        session([$sessionKey => $carrito]);

        $mensaje = "Se agregaron {$contador} productos al carrito";
        if ($ya_existentes > 0) {
            $mensaje .= ". {$ya_existentes} productos ya estaban en el carrito";
        }

        return redirect()->route('lotes.vencidos.sucursal', $sucursal_id)
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');
    }

    /**
     * Vaciar todo el carrito
     */
    public function vaciarCarrito(Request $request)
    {
        $request->validate([
            'session_key' => 'required|string|max:100',
            'sucursal_id' => 'required|exists:sucursals,id,activa,1',
        ]);

        $sucursalId = (int) $request->sucursal_id;
        $this->autorizarSucursal($sucursalId);
        $sessionKey = 'carrito_vencidos_sucursal_' . $sucursalId;
        abort_unless(hash_equals($sessionKey, (string) $request->session_key), 422, 'La sesión del carrito no es válida.');
        session()->forget($sessionKey);

        return redirect()->route('lotes.vencidos.sucursal', $request->sucursal_id)
            ->with('mensaje', 'Carrito vaciado correctamente')
            ->with('icono', 'success');
    }

    public function eliminarDeSalida(Request $request, $lote_id)
    {
        $request->validate([
            'session_key' => 'required|string|max:100',
            'sucursal_id' => 'required|exists:sucursals,id,activa,1',
        ]);

        $sucursalId = (int) $request->sucursal_id;
        $this->autorizarSucursal($sucursalId);
        $sessionKey = 'carrito_vencidos_sucursal_' . $sucursalId;
        abort_unless(hash_equals($sessionKey, (string) $request->session_key), 422, 'La sesión del carrito no es válida.');
        $carrito = session($sessionKey, []);

        // Eliminar del carrito
        unset($carrito[$lote_id]);

        session([$sessionKey => $carrito]);

        return redirect()->route('lotes.vencidos.sucursal', $request->sucursal_id)
            ->with('mensaje', 'Producto eliminado del carrito')
            ->with('icono', 'success');
    }

    public function finalizarSalidaVencidos(Request $request)
    {
        $validated = $request->validate([
            'session_key' => 'required|string|max:100',
            'sucursal_id' => 'required|integer|exists:sucursals,id,activa,1',
        ]);

        $sucursalId = (int) $validated['sucursal_id'];
        abort_unless(Auth::user()->puedeGestionarSucursal($sucursalId), 403);

        $sessionKey = 'carrito_vencidos_sucursal_' . $sucursalId;
        if (!hash_equals($sessionKey, (string) $validated['session_key'])) {
            return back()->with('mensaje', 'La sesión de productos vencidos no es válida.')->with('icono', 'error');
        }

        $carrito = session($sessionKey, []);
        if (empty($carrito)) {
            return back()->with('mensaje', 'No hay productos en el carrito.')->with('icono', 'error');
        }

        try {
            $resultado = DB::transaction(function () use ($carrito, $sucursalId) {
                Sucursal::query()->whereKey($sucursalId)->where('activa', true)->lockForUpdate()->firstOrFail();
                $salida = Salida::create([
                    'sucursal_id' => $sucursalId,
                    'user_id' => Auth::id(),
                    'fecha' => today(),
                    'motivo' => 'Caducidad',
                    'observaciones' => 'Baja por caducidad realizada por ' . Auth::user()->name,
                    'total' => 0,
                    'estado' => 'Pendiente',
                ]);

                $totalPerdida = 0.0;
                $inventario = app(InventarioService::class);
                foreach ($carrito as $loteId => $item) {
                    $cantidadRaw = $item['cantidad'] ?? null;
                    $cantidad = (int) $cantidadRaw;
                    if (!is_numeric($cantidadRaw) || (float) $cantidadRaw !== (float) $cantidad || $cantidad <= 0) {
                        throw new \RuntimeException('Una cantidad del carrito no es válida.');
                    }

                    $lote = Lote::query()->lockForUpdate()->findOrFail((int) $loteId);
                    if (!$lote->fecha_vencimiento || !$lote->fecha_vencimiento->lte(today())) {
                        throw new \RuntimeException('El lote ' . $lote->codigo_lote . ' no está vencido.');
                    }

                    $precioCompra = round((float) ($lote->precio_compra ?? 0), 2);
                    $subtotal = round($cantidad * $precioCompra, 2);
                    $salida->detalles()->create([
                        'producto_id' => $lote->producto_id,
                        'lote_id' => $lote->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioCompra,
                        'subtotal' => $subtotal,
                    ]);

                    $inventario->disminuir(
                        (int) $lote->id,
                        $sucursalId,
                        $cantidad,
                        Auth::id(),
                        Salida::class,
                        (int) $salida->id,
                        'Baja por caducidad; vencimiento ' . $lote->fecha_vencimiento->format('d/m/Y')
                    );
                    $totalPerdida += $subtotal;
                }

                $salida->update(['total' => round($totalPerdida, 2), 'estado' => 'Entregado']);
                return ['salida' => $salida, 'total' => round($totalPerdida, 2)];
            }, 3);

            session()->forget($sessionKey);
            return redirect()->route('lotes.index')
                ->with('mensaje', 'Baja por caducidad registrada. Pérdida total: Bs ' . number_format($resultado['total'], 2))
                ->with('icono', 'success');
        } catch (\Throwable $e) {
            return back()->with('mensaje', 'No se registró la baja: ' . $e->getMessage())->with('icono', 'error');
        }
    }


    public function generarPDF(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');
        $search = $request->input('search');

        $usuario = Auth::user();
        $sucursalUsuario = $usuario->can('operaciones.todas-sucursales') ? null : (int) ($usuario->sucursal_id ?? 0);
        $query = Lote::with([
            'producto.categoria',
            'producto.marca',
            'proveedor',
            'inventarioSucuralLotes' => function ($inventarios) use ($sucursalUsuario) {
                $inventarios->when($sucursalUsuario > 0, fn ($q) => $q->where('sucursal_id', $sucursalUsuario))
                    ->with('sucursal');
            },
        ])->when(!$usuario->can('operaciones.todas-sucursales'), function ($lotes) use ($sucursalUsuario) {
            $sucursalUsuario > 0
                ? $lotes->whereHas('inventarioSucuralLotes', fn ($q) => $q->where('sucursal_id', $sucursalUsuario))
                : $lotes->whereRaw('1 = 0');
        });

        // MISMA LÓGICA DE FILTRO DE FECHAS - USANDO fecha_entrada
        if ($fecha_desde && $fecha_hasta) {
            $fecha_desde = Carbon::parse($fecha_desde)->startOfDay();
            $fecha_hasta = Carbon::parse($fecha_hasta)->endOfDay();
            // Cambiado de fecha_vencimiento a fecha_entrada
            $query->whereBetween('fecha_entrada', [$fecha_desde, $fecha_hasta]);
        }

        $lotes = $query->get();

        // MISMA LÓGICA DE CÁLCULO DE ESTADOS
        $lotes->each(function ($lote) {
            if ($lote->fecha_vencimiento) {
                $hoy = Carbon::today();
                $lote->is_expired = $lote->fecha_vencimiento->isPast();
                $lote->day_to_expired = $hoy->diffInDays($lote->fecha_vencimiento, false);
            } else {
                $lote->is_expired = false;
                $lote->day_to_expired = null;
            }

            if ($lote->cantidad_actual <= 0) {
                $lote->estado_texto = 'terminado';
                $lote->estado_original = 'Lote terminado';
            } elseif ($lote->is_expired) {
                $lote->estado_texto = 'vencido';
                $lote->estado_original = 'Vencido';
            } elseif ($lote->day_to_expired !== null && $lote->day_to_expired <= 3) {
                $lote->estado_texto = 'por caducar';
                $lote->estado_original = 'Por caducar';
            } else {
                $lote->estado_texto = 'vigente';
                $lote->estado_original = 'Vigente';
            }
        });

        // MISMA LÓGICA DE FILTRO DE BÚSQUEDA
        if ($search && $search !== '') {
            $searchLower = trim(strtolower($search));
            $searchTerms = explode(' ', $searchLower);

            $lotes = $lotes->filter(function($lote) use ($searchLower, $searchTerms) {
                $textoBusqueda = strtolower(
                    $lote->codigo_lote . ' ' .
                    ($lote->producto->nombre ?? '') . ' ' .
                    ($lote->producto->codigo ?? '') . ' ' .
                    ($lote->producto->categoria->nombre ?? '') . ' ' .
                    ($lote->proveedor->nombre ?? '') . ' ' .
                    ($lote->proveedor->empresa ?? '') . ' ' .
                    $lote->estado_texto . ' ' .
                    $lote->estado_original
                );

                if (str_contains($textoBusqueda, $searchLower)) {
                    return true;
                }

                foreach ($searchTerms as $term) {
                    if (strlen($term) > 1 && str_contains($textoBusqueda, $term)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $total_compras = $lotes->sum(function ($lote) {
            return $lote->precio_compra * $lote->cantidad_inicial;
        });

        $data = [
            'lotes' => $lotes,
            'fecha_desde' => $fecha_desde ? Carbon::parse($fecha_desde)->format('d/m/Y') : 'Todo',
            'fecha_hasta' => $fecha_hasta ? Carbon::parse($fecha_hasta)->format('d/m/Y') : 'Todo',
            'search' => $search ?: 'Sin filtro',
            'total_compras' => $total_compras,
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'usuario' => Auth::user()->name ?? 'Sistema',
            'total_lotes' => $lotes->count()
        ];

        $pdf = Pdf::loadView('admin.lotes.pdf', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        return $pdf->download('lotes-' . date('Y-m-d') . '.pdf');
    }

    private function autorizarSucursal(int $sucursalId): void
    {
        abort_unless(Auth::user()->puedeGestionarSucursal($sucursalId), 403);
        Sucursal::query()->whereKey($sucursalId)->where('activa', true)->firstOrFail();
    }

    private function autorizarLote(Lote $lote): void
    {
        $usuario = Auth::user();
        if ($usuario->can('operaciones.todas-sucursales')) {
            return;
        }

        abort_unless($usuario->sucursal_id, 403);
        abort_unless(
            $lote->inventarioSucuralLotes()->where('sucursal_id', $usuario->sucursal_id)->exists(),
            403
        );
    }

}
