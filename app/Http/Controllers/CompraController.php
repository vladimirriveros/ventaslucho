<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\CompraProveedorMail;
// use App\Models\InventarioSucuralLote;
// use App\Models\Lote;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
// o si usas laravel-dompdf: use PDF;

class CompraController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $compras = Compra::query()
            ->when(!$user->can('operaciones.todas-sucursales'), function ($query) use ($user) {
                $query->where(function ($subquery) use ($user) {
                    if ($user->sucursal_id) {
                        $subquery->where('sucursal_id', $user->sucursal_id);
                    } else {
                        $subquery->whereRaw('1 = 0');
                    }
                    $subquery->orWhere(function ($pendientes) use ($user) {
                        $pendientes->whereNull('sucursal_id')->where('user_id', $user->id);
                    });
                });
            })
            ->latest('fecha')
            ->latest('id')
            ->get();

        return view('admin.compras.index', compact('compras'));
    }

    public function create(Request $request)
    {
        $sucursalOperativa = Auth::user()?->sucursalOperativa();
        abort_unless($sucursalOperativa, 403, 'Su usuario debe tener una sucursal activa asignada para crear compras.');
        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', true)->orderBy('nombre')->get();

        $observacion_predefinida = $request->query('obs', '');
        $productos_sugeridos = $request->query('productos', '');

        if (!empty($productos_sugeridos)) {
            session(['productos_sugeridos_temp' => $productos_sugeridos]);
            session(['observacion_compra_temp' => $observacion_predefinida]);
        }

        return view('admin.compras.create', compact('proveedores', 'productos', 'observacion_predefinida', 'productos_sugeridos', 'sucursalOperativa'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $sucursalId = $this->sucursalOperativaId();

        $compra = new Compra();
        $compra->proveedor_id = $request->proveedor_id;
        $compra->sucursal_id = $sucursalId;
        $compra->user_id = $user->id;
        $compra->fecha = $request->fecha;
        $compra->observaciones = $request->observaciones;
        $compra->total = 0;
        $compra->estado = 'pendiente';
        $compra->save();

        $productos_sugeridos = session('productos_sugeridos_temp', '');

        // La sucursal nunca se obtiene de observaciones, URL ni sesión.
        // Siempre queda fijada por el usuario autenticado en el servidor.
        session()->forget([
            'productos_sugeridos_temp',
            'observacion_compra_temp',
            'sucursal_origen_nombre',
        ]);

        if (!empty($productos_sugeridos)) {
            return redirect()->route('compras.edit', [
                'id' => $compra->id,
                'productos' => $productos_sugeridos,
            ])->with('mensaje', 'Compra creada exitosamente. Se cargarán los productos con stock bajo.')
                ->with('icono', 'success');
        }

        return redirect()->route('compras.edit', ['id' => $compra->id])
            ->with('mensaje', 'Compra creada exitosamente. Ahora puede añadir productos.')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        $compra = Compra::with('sucursal')->findOrFail($id);
        $this->autorizarOperacionCompra($compra);
        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', true)->orderBy('nombre')->get();
        $marcas = Marca::orderBy('nombre')->get();

        session()->forget('sucursal_origen_nombre');

        return view('admin.compras.edit', compact('compra', 'proveedores', 'productos', 'marcas'));
    }

    public function show($id)
    {
        $compra = Compra::with('detalles.producto', 'proveedor')->findOrFail($id);
        $this->autorizarCompra($compra);

        $movimientoEntrada = MovimientoInventario::whereHas('lote', function ($query) use ($compra) {
            $query->whereIn('id', $compra->detalles->pluck('lote_id'));
        })->where('tipo_movimiento', 'Entrada')->first();

        $sucursal_destino = null;
        if ($movimientoEntrada) {
            $sucursal_destino = Sucursal::find($movimientoEntrada->sucursal_id);
        }

        return view('admin.compras.show', compact('compra', 'sucursal_destino'));
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $compra = Compra::withCount('detalles')->lockForUpdate()->findOrFail($id);
                $this->autorizarCompra($compra);

                if ($compra->estado === 'Recibido' || $compra->detalles_count > 0) {
                    throw new \RuntimeException('Una compra recibida no puede eliminarse porque alteraría el historial y el inventario. Use Corrección de compra.');
                }

                $compra->observaciones = trim(($compra->observaciones ? $compra->observaciones . ' | ' : '') . 'Compra cancelada por ' . Auth::user()->name . ' el ' . now()->format('d/m/Y H:i'));
                $compra->estado = 'cancelada';
                $compra->save();
                $compra->delete();
            });

            return redirect()->route('compras.index')->with('mensaje', 'Compra pendiente cancelada correctamente.')->with('icono', 'success');
        } catch (\Throwable $e) {
            return redirect()->back()->with('mensaje', $e->getMessage())->with('icono', 'error');
        }
    }


    public function enviarCorreo($id)
    {
        $compra = Compra::with('detalles.producto', 'proveedor')->findOrFail($id);
        $this->autorizarCompra($compra);

        // Crear una variable temporal para el carrito
        $carrito_temporal = [];

        // Si la compra aún no tiene detalles guardados en DB, pero tiene carrito en sesión
        if ($compra->detalles->isEmpty()) {
            $carritoKey = 'carrito_compra_' . $compra->id;
            $carrito_temporal = session($carritoKey, []);
        }

        // Actualizar SOLO el estado, nada más
        $compra->estado = 'enviado al proveedor';
        $compra->save();

        // Enviar correo PASANDO el carrito como parámetro adicional
        $proveedorEmail = $compra->proveedor->email;
        Mail::to($proveedorEmail)->send(new CompraProveedorMail($compra, $carrito_temporal));

        return redirect()->back()
            ->with('mensaje', 'Correo enviado al proveedor exitosamente.')
            ->with('icono', 'success');
    }

    public function enviarWhatsapp(Compra $compra)
    {
        $this->autorizarCompra($compra);

        // Obtener proveedor
        $proveedor = $compra->proveedor;

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        // Limpiar teléfono (solo números)
        $telefonoRaw = $proveedor->telefono ?? '';
        $telefonoSoloDigitos = preg_replace('/\D+/', '', (string) $telefonoRaw);

        if (empty($telefonoSoloDigitos)) {
            return response()->json([
                'success' => false,
                'message' => 'Teléfono del proveedor no disponible.'
            ], 400);
        }

        // Código país Bolivia
        $codigoPais = '591';

        // Evitar duplicar código
        if (strpos($telefonoSoloDigitos, $codigoPais) === 0) {
            $telefonoFinal = $telefonoSoloDigitos;
        } else {
            $telefonoFinal = $codigoPais . $telefonoSoloDigitos;
        }

        // CONSTRUIR MENSAJE
        $mensaje = "*SOLICITUD DE COMPRA*\n\n";
        $mensaje .= "Estimado proveedor:\n";
        $mensaje .= "*{$proveedor->nombre}*\n\n";
        $mensaje .= "Detalle de productos:\n\n";

        $total = 0;

        if ($compra->detalles->isNotEmpty()) {
            foreach ($compra->detalles as $detalle) {
                $producto = $detalle->producto->nombre ?? 'Producto';
                $cantidad = $detalle->cantidad;
                $precio = $detalle->precio_unitario ?? 0;
                $subtotal = $cantidad * $precio;
                $total += $subtotal;

                $mensaje .= "• $producto\n";
                $mensaje .= "  Cantidad: $cantidad\n";
                $mensaje .= "  Precio: Bs " . number_format($precio, 2) . "\n";
                $mensaje .= "  Subtotal: Bs " . number_format($subtotal, 2) . "\n\n";
            }
        } else {
            $carritoKey = 'carrito_compra_' . $compra->id;
            $carrito = session($carritoKey, []);

            if (empty($carrito)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos en el carrito para enviar.'
                ], 400);
            }

            foreach ($carrito as $item) {
                $producto = $item['producto_nombre'] ?? 'Producto';
                $cantidad = $item['cantidad'] ?? 0;
                $precio = $item['precio_unitario'] ?? 0;
                $subtotal = $item['subtotal'] ?? ($cantidad * $precio);
                $total += $subtotal;

                $mensaje .= "• $producto\n";
                $mensaje .= "  Cantidad: $cantidad\n";
                $mensaje .= "  Precio: Bs " . number_format($precio, 2) . "\n";
                $mensaje .= "  Subtotal: Bs " . number_format($subtotal, 2) . "\n\n";
            }
        }

        $mensaje .= "-----------------------\n";
        $mensaje .= "*TOTAL: Bs " . number_format($total, 2) . "*\n\n";
        $mensaje .= "Por favor confirmar disponibilidad.\n";
        $mensaje .= "Gracias.";

        // Codificar mensaje para URL
        $mensajeCodificado = urlencode($mensaje);

        // Detectar dispositivo móvil para usar app o web
        $userAgent = request()->header('User-Agent');
        $isMobile = preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent);

        if ($isMobile) {
            // En móvil, usar app de WhatsApp
            $url = "https://wa.me/$telefonoFinal?text=$mensajeCodificado";
        } else {
            // En desktop, usar WhatsApp Web
            $url = "https://web.whatsapp.com/send?phone=$telefonoFinal&text=$mensajeCodificado";
        }

        // Actualizar estado de la compra
        $compra->estado = 'enviado al proveedor';
        $compra->save();

        // Devolver la URL como JSON
        return response()->json([
            'success' => true,
            'url' => $url,
            'message' => 'Redirigiendo a WhatsApp...'
        ]);
    }


    public function generarPdf(Compra $compra)
    {
        $this->autorizarCompra($compra);
        $productos = $compra->detalles->isNotEmpty()
            ? $compra->detalles
            : session('carrito_compra_' . $compra->id, []);

        $pdf = Pdf::loadView('admin.compras.pdf.pedido', [
            'compra' => $compra,
            'productos' => $productos
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        return $pdf->download('pedido_compra_'.$compra->id.'.pdf');
    }


    public function enviarWhatsappPdf(Compra $compra)
    {
        $this->autorizarCompra($compra);

        // Verificar si es petición AJAX
        $wantsJson = request()->wantsJson() || request()->ajax();

        $proveedor = $compra->proveedor;

        if (!$proveedor || empty($proveedor->telefono)) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor o teléfono no disponible'
            ], 400);
        }

        // Generar PDF temporal (opcional, por ahora simplifiquemos)
        try {
            // Limpiar teléfono
            $telefono = preg_replace('/\D+/', '', $proveedor->telefono);
            $codigoPais = '591';
            $telefonoFinal = strpos($telefono, $codigoPais) === 0 ? $telefono : $codigoPais . $telefono;

            // Mensaje de texto simple
            // $mensaje = "*SOLICITUD DE COMPRA #{$compra->id}*\n\n";
            $mensaje = "*SOLICITUD DE COMPRA CONSERDEI*\n\n";
            $mensaje .= "Estimad@ *{$proveedor->nombre}*, tenemos un pedido para ti.\n\n";
            $mensaje .= "Por favor confirmar la disponibilidad de los productos\n";
            $mensaje .= "a la brevedad posible.\n";
            $mensaje .= "Gracias.";

            $mensajeCodificado = urlencode($mensaje);

            // Detectar dispositivo
            $userAgent = request()->header('User-Agent');
            $isMobile = preg_match('/(android|iphone|ipad|ipod)/i', $userAgent);

            if ($isMobile) {
                $url = "https://wa.me/$telefonoFinal?text=$mensajeCodificado";
            } else {
                $url = "https://web.whatsapp.com/send?phone=$telefonoFinal&text=$mensajeCodificado";
            }

            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => 'Mensaje preparado',
                'pdf_url' => route('compras.descargarPdf', $compra->id) // Si existe esta ruta
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar PDF de nota de compra (para ver en navegador)
     */
    public function notaCompraPdf($id)
    {
        $compra = Compra::with(['detalles.producto', 'detalles.lote', 'proveedor'])->findOrFail($id);
        $this->autorizarCompra($compra);

        // Obtener la sucursal de destino del primer movimiento de inventario
        $sucursal = null;
        $primerDetalle = $compra->detalles->first();
        if ($primerDetalle && $primerDetalle->lote) {
            $inventario = $primerDetalle->lote->inventarioSucuralLotes()->first();
            if ($inventario) {
                $sucursal = Sucursal::find($inventario->sucursal_id);
            }
        }

        $pdf = Pdf::loadView('admin.compras.pdf.nota_compra', [
            'compra' => $compra,
            'detalles' => $compra->detalles,
            'sucursal' => $sucursal
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->stream('nota_compra_'.$compra->id.'.pdf');
    }

    /**
     * Descargar PDF de nota de compra
     */
    public function descargarNotaCompra($id)
    {
        $compra = Compra::with(['detalles.producto', 'detalles.lote', 'proveedor'])->findOrFail($id);
        $this->autorizarCompra($compra);

        $sucursal = null;
        $primerDetalle = $compra->detalles->first();
        if ($primerDetalle && $primerDetalle->lote) {
            $inventario = $primerDetalle->lote->inventarioSucuralLotes()->first();
            if ($inventario) {
                $sucursal = Sucursal::find($inventario->sucursal_id);
            }
        }

        $pdf = Pdf::loadView('admin.compras.pdf.nota_compra', [
            'compra' => $compra,
            'detalles' => $compra->detalles,
            'sucursal' => $sucursal
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        return $pdf->download('nota_compra_'.$compra->id.'.pdf');
    }
    private function autorizarCompra(Compra $compra): void
    {
        $user = Auth::user();
        $autorizado = $compra->sucursal_id
            ? $user->puedeGestionarSucursal((int) $compra->sucursal_id)
            : ($user->can('operaciones.todas-sucursales') || (int) $compra->user_id === (int) $user->id);

        abort_unless($autorizado, 403, 'No tiene autorización para acceder a esta compra.');
    }

    private function autorizarOperacionCompra(Compra $compra): void
    {
        $user = Auth::user();
        $sucursalId = $this->sucursalOperativaId();

        abort_unless(
            (int) $compra->sucursal_id === $sucursalId,
            403,
            'Solo puede modificar compras de la sucursal asignada a su usuario.'
        );
    }

    private function sucursalOperativaId(): int
    {
        $sucursal = Auth::user()?->sucursalOperativa();

        abort_unless(
            $sucursal,
            403,
            'Su usuario debe tener una sucursal activa asignada para realizar operaciones.'
        );

        return (int) $sucursal->id;
    }

}
