<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\InventarioSucuralLote;
use App\Models\Producto;
use App\Models\ProductoSucursal;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AsistenteNegocioService
{
    private const MAX_ROWS = 10;

    private const NUMBER_WORDS = [
        'un' => 1, 'uno' => 1, 'una' => 1,
        'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5,
        'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9,
        'diez' => 10, 'once' => 11, 'doce' => 12, 'trece' => 13,
        'catorce' => 14, 'quince' => 15, 'dieciseis' => 16,
        'diecisiete' => 17, 'dieciocho' => 18, 'diecinueve' => 19,
        'veinte' => 20,
    ];

    public function responder(User $user, string $mensaje, ?int $sucursalSolicitada = null): array
    {
        $mensaje = trim(strip_tags($mensaje));
        $normalizado = $this->normalizar($mensaje);

        [$sucursalId, $alcance] = $this->resolverAlcance($user, $normalizado, $sucursalSolicitada);

        if ($normalizado === '' || $this->esAyuda($normalizado)) {
            return $this->respuestaAyuda($alcance);
        }

        // Debe evaluarse antes que el resumen de ventas, porque contiene la palabra "vendido".
        if ($this->contieneAlguno($normalizado, ['mas vendido', 'más vendido', 'producto estrella', 'producto que mas se vende', 'producto que más se vende'])) {
            if (! $this->puedeConsultarVentas($user)) {
                return $this->sinPermiso('ventas', $alcance);
            }
            return $this->productoMasVendido($normalizado, $sucursalId, $alcance);
        }

        if ($this->pareceCalculoPedido($normalizado)) {
            if (! $this->puedeConsultarProductos($user)) {
                return $this->sinPermiso('productos e inventario', $alcance);
            }
            $calculo = $this->calcularPedido($mensaje, $sucursalId, $alcance);
            if ($calculo !== null) {
                return $calculo;
            }
        }

        if ($this->contieneAlguno($normalizado, ['stock bajo', 'por agotarse', 'por acabarse', 'agotando', 'reponer'])) {
            if (! $this->puedeConsultarProductos($user)) {
                return $this->sinPermiso('productos e inventario', $alcance);
            }
            return $this->stockBajo($sucursalId, $alcance);
        }

        if ($this->esConsultaCantidadProductos($normalizado)) {
            if (! $this->puedeConsultarProductos($user)) {
                return $this->sinPermiso('productos e inventario', $alcance);
            }
            return $this->cantidadProductos($sucursalId, $alcance);
        }

        if ($this->esConsultaVentas($normalizado)) {
            if (! $this->puedeConsultarVentas($user)) {
                return $this->sinPermiso('ventas', $alcance);
            }
            return $this->resumenVentas($normalizado, $sucursalId, $alcance);
        }

        if ($this->esConsultaProducto($normalizado)) {
            if (! $this->puedeConsultarProductos($user)) {
                return $this->sinPermiso('productos e inventario', $alcance);
            }
            return $this->consultaProducto($mensaje, $normalizado, $sucursalId, $alcance);
        }

        // Último intento: si el texto coincide razonablemente con un producto, responder su ficha.
        if (mb_strlen($normalizado) >= 3 && $this->puedeConsultarProductos($user)) {
            $producto = $this->buscarProducto($mensaje);
            if ($producto) {
                return $this->respuestaProducto($producto, $sucursalId, $alcance);
            }
        }

        return [
            'reply' => 'No pude identificar esa consulta todavía. Puedo ayudarte con ventas, inventario, precios, stock, productos más vendidos y cálculos de pedidos.',
            'scope' => $alcance,
            'suggestions' => $this->sugerenciasBase(),
        ];
    }

    private function resolverAlcance(User $user, string $mensajeNormalizado, ?int $sucursalSolicitada): array
    {
        if ($user->hasRole('invitado') || ! $user->can('operaciones.todas-sucursales')) {
            $id = (int) ($user->sucursal_id ?? 0);
            abort_if($id <= 0, 403, 'Su usuario no tiene una sucursal asignada.');

            $sucursal = Sucursal::query()->find($id);
            abort_if(! $sucursal, 403, 'La sucursal asignada ya no existe.');

            return [$id, $sucursal->nombre];
        }

        $sucursal = null;
        if ($sucursalSolicitada) {
            $sucursal = Sucursal::query()->find($sucursalSolicitada);
        }

        // El Superadministrador también puede escribir el nombre de una sucursal.
        if (! $sucursal) {
            $sucursales = Sucursal::query()->select(['id', 'nombre'])->orderBy('nombre')->get();
            foreach ($sucursales as $item) {
                $nombre = $this->normalizar($item->nombre);
                if (mb_strlen($nombre) >= 3 && str_contains($mensajeNormalizado, $nombre)) {
                    $sucursal = $item;
                    break;
                }
            }
        }

        return $sucursal
            ? [(int) $sucursal->id, $sucursal->nombre]
            : [null, 'Todas las sucursales'];
    }

    private function resumenVentas(string $mensaje, ?int $sucursalId, string $alcance): array
    {
        [$desde, $hasta, $periodo] = $this->resolverPeriodo($mensaje);

        $query = Venta::query()
            ->where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($sucursalId, fn (Builder $q) => $q->where('sucursal_id', $sucursalId));

        $resumen = (clone $query)
            ->selectRaw('COUNT(*) as cantidad_ventas, COALESCE(SUM(total), 0) as total_vendido, COALESCE(SUM(pagado), 0) as total_cobrado')
            ->first();
        $cantidad = (int) ($resumen?->cantidad_ventas ?? 0);
        $total = (float) ($resumen?->total_vendido ?? 0);
        $cobrado = (float) ($resumen?->total_cobrado ?? 0);
        $pendiente = max(0, $total - $cobrado);

        $response = [
            'reply' => $cantidad > 0
                ? "En {$periodo}, {$alcance} registró {$cantidad} venta(s) por un total de Bs " . $this->money($total) . '.'
                : "No hay ventas registradas en {$periodo} para {$alcance}.",
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Ventas', 'value' => number_format($cantidad, 0, ',', '.')],
                ['label' => 'Total vendido', 'value' => 'Bs ' . $this->money($total)],
                ['label' => 'Pagado de esas ventas', 'value' => 'Bs ' . $this->money($cobrado)],
                ['label' => 'Pendiente', 'value' => 'Bs ' . $this->money($pendiente)],
            ],
            'suggestions' => ['¿Cuál fue el producto más vendido?', '¿Tenemos taladro?', '¿Qué productos tienen stock bajo?'],
        ];

        if ($sucursalId === null) {
            $porSucursal = (clone $query)
                ->selectRaw('sucursal_id, COUNT(*) as cantidad_ventas, SUM(total) as total_ventas')
                ->with('sucursal:id,nombre')
                ->groupBy('sucursal_id')
                ->orderByDesc('total_ventas')
                ->limit(self::MAX_ROWS)
                ->get();

            if ($porSucursal->isNotEmpty()) {
                $response['table'] = [
                    'headers' => ['Sucursal', 'Ventas', 'Total'],
                    'rows' => $porSucursal->map(fn ($row) => [
                        $row->sucursal?->nombre ?? 'Sin sucursal',
                        (string) $row->cantidad_ventas,
                        'Bs ' . $this->money((float) $row->total_ventas),
                    ])->values()->all(),
                ];
            }
        }

        return $response;
    }

    private function productoMasVendido(string $mensaje, ?int $sucursalId, string $alcance): array
    {
        [$desde, $hasta, $periodo] = $this->resolverPeriodo($mensaje);

        $ranking = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereNull('ventas.deleted_at')
            ->where('ventas.estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($sucursalId, fn ($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->selectRaw('productos.id, productos.codigo, productos.nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as importe')
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        if ($ranking->isEmpty()) {
            return [
                'reply' => "No hay ventas suficientes en {$periodo} para calcular el producto más vendido de {$alcance}.",
                'scope' => $alcance,
                'suggestions' => $this->sugerenciasBase(),
            ];
        }

        $primero = $ranking->first();

        return [
            'reply' => "El producto más vendido en {$periodo} para {$alcance} es {$primero->nombre}, con {$primero->unidades} unidad(es).",
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Producto líder', 'value' => $primero->nombre],
                ['label' => 'Unidades', 'value' => (string) $primero->unidades],
                ['label' => 'Subtotal bruto', 'value' => 'Bs ' . $this->money((float) $primero->importe)],
            ],
            'table' => [
                'headers' => ['Producto', 'Unidades', 'Subtotal bruto'],
                'rows' => $ranking->map(fn ($row) => [
                    trim(($row->codigo ? $row->codigo . ' · ' : '') . $row->nombre),
                    (string) $row->unidades,
                    'Bs ' . $this->money((float) $row->importe),
                ])->all(),
            ],
            'suggestions' => ['¿Cuánto vendimos hoy?', '¿Cuántos productos tenemos?', '¿Qué productos tienen stock bajo?'],
        ];
    }

    private function cantidadProductos(?int $sucursalId, string $alcance): array
    {
        $catalogo = Producto::query()->count();

        $stockQuery = $this->queryStockDisponible()->when($sucursalId, fn ($q) => $q->where('inventario_sucural_lotes.sucursal_id', $sucursalId));
        $unidades = (int) $stockQuery->sum('inventario_sucural_lotes.cantidad_en_sucursal');

        if ($sucursalId) {
            $manejados = ProductoSucursal::query()
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->distinct('producto_id')
                ->count('producto_id');
        } else {
            $manejados = ProductoSucursal::query()
                ->where('activo', true)
                ->distinct('producto_id')
                ->count('producto_id');
        }

        $conStock = (clone $this->queryStockDisponible())
            ->when($sucursalId, fn ($q) => $q->where('inventario_sucural_lotes.sucursal_id', $sucursalId))
            ->distinct('lotes.producto_id')
            ->count('lotes.producto_id');

        return [
            'reply' => "{$alcance} tiene {$unidades} unidad(es) disponibles distribuidas en {$conStock} producto(s) con existencias.",
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Catálogo general', 'value' => number_format($catalogo, 0, ',', '.')],
                ['label' => 'Productos manejados', 'value' => number_format($manejados, 0, ',', '.')],
                ['label' => 'Con stock', 'value' => number_format($conStock, 0, ',', '.')],
                ['label' => 'Unidades disponibles', 'value' => number_format($unidades, 0, ',', '.')],
            ],
            'suggestions' => ['¿Tenemos taladro?', '¿Qué productos tienen stock bajo?', '¿Cuál fue el producto más vendido este mes?'],
        ];
    }

    private function stockBajo(?int $sucursalId, string $alcance): array
    {
        $configuraciones = ProductoSucursal::query()
            ->with(['producto:id,codigo,nombre,stock_minimo'])
            ->where('activo', true)
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->get(['id', 'producto_id', 'sucursal_id', 'stock_minimo']);

        $stock = $this->stockAgrupado($sucursalId);
        $sucursales = Sucursal::query()->pluck('nombre', 'id');

        $bajos = $configuraciones->map(function ($config) use ($stock, $sucursales) {
            $clave = $config->sucursal_id . ':' . $config->producto_id;
            $actual = (int) ($stock[$clave] ?? 0);
            $minimo = (int) ($config->stock_minimo ?? $config->producto?->stock_minimo ?? 0);

            return [
                'sucursal' => $sucursales[$config->sucursal_id] ?? 'Sucursal',
                'producto' => $config->producto,
                'actual' => $actual,
                'minimo' => $minimo,
            ];
        })->filter(fn ($row) => $row['producto'] && $row['actual'] <= $row['minimo'])
            ->sortBy('actual')
            ->values();

        if ($bajos->isEmpty()) {
            return [
                'reply' => "No encontré productos con stock bajo en {$alcance}.",
                'scope' => $alcance,
                'suggestions' => $this->sugerenciasBase(),
            ];
        }

        return [
            'reply' => "Encontré {$bajos->count()} producto(s) que requieren revisión de stock en {$alcance}.",
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Con stock bajo', 'value' => (string) $bajos->count()],
                ['label' => 'Sin existencias', 'value' => (string) $bajos->where('actual', 0)->count()],
            ],
            'table' => [
                'headers' => $sucursalId ? ['Producto', 'Stock', 'Mínimo'] : ['Sucursal', 'Producto', 'Stock', 'Mínimo'],
                'rows' => $bajos->take(self::MAX_ROWS)->map(function ($row) use ($sucursalId) {
                    $producto = trim((($row['producto']->codigo ?? '') ? $row['producto']->codigo . ' · ' : '') . ($row['producto']->nombre ?? 'Producto'));
                    return $sucursalId
                        ? [$producto, (string) $row['actual'], (string) $row['minimo']]
                        : [$row['sucursal'], $producto, (string) $row['actual'], (string) $row['minimo']];
                })->all(),
            ],
            'suggestions' => ['¿Cuántos productos tenemos?', '¿Tenemos pintura?', '¿Cuánto vendimos hoy?'],
        ];
    }

    private function consultaProducto(string $mensajeOriginal, string $mensajeNormalizado, ?int $sucursalId, string $alcance): array
    {
        $termino = $this->extraerTerminoProducto($mensajeOriginal, $mensajeNormalizado);
        $producto = $this->buscarProducto($termino);

        if (! $producto) {
            return [
                'reply' => "No encontré un producto que coincida con “{$termino}” en el catálogo.",
                'scope' => $alcance,
                'suggestions' => ['¿Cuántos productos tenemos?', '¿Qué productos tienen stock bajo?', 'Ayuda'],
            ];
        }

        return $this->respuestaProducto($producto, $sucursalId, $alcance);
    }

    private function respuestaProducto(Producto $producto, ?int $sucursalId, string $alcance): array
    {
        $precioCatalogo = (float) $producto->precio_venta;

        if ($sucursalId) {
            $lotesDisponibles = $this->lotesDisponiblesProducto($producto->id, $sucursalId);
            $stock = (int) $lotesDisponibles->sum('stock');
            $precio = $lotesDisponibles->isNotEmpty() && (float) $lotesDisponibles->first()['precio'] > 0
                ? (float) $lotesDisponibles->first()['precio']
                : $precioCatalogo;
            $preciosLote = $lotesDisponibles->pluck('precio')
                ->map(fn ($v) => round((float) ($v > 0 ? $v : $precioCatalogo), 2))
                ->unique()->sort()->values();
            $habilitado = ProductoSucursal::query()
                ->where('producto_id', $producto->id)
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->exists();

            $estado = $stock > 0
                ? "Sí. Hay {$stock} unidad(es) disponibles de {$producto->nombre} en {$alcance}."
                : ($habilitado
                    ? "{$producto->nombre} está habilitado en {$alcance}, pero actualmente no tiene stock disponible."
                    : "{$producto->nombre} existe en el catálogo, pero {$alcance} todavía no lo maneja o no tiene stock disponible.");

            return [
                'reply' => $estado . ' Precio de venta actual: Bs ' . $this->money($precio) . ($preciosLote->count() > 1 ? ' (puede variar según el lote disponible).' : '.') ,
                'scope' => $alcance,
                'cards' => [
                    ['label' => 'Producto', 'value' => $producto->nombre],
                    ['label' => 'Código', 'value' => $producto->codigo ?: '—'],
                    ['label' => 'Stock disponible', 'value' => (string) $stock],
                    ['label' => 'Precio', 'value' => 'Bs ' . $this->money($precio)],
                ],
                'suggestions' => ['Calcula 2 ' . $producto->nombre, '¿Qué productos tienen stock bajo?', '¿Cuánto vendimos hoy?'],
            ];
        }

        $precio = $precioCatalogo;
        $porSucursal = $this->stockProductoPorSucursal($producto->id);
        $total = (int) $porSucursal->sum('stock');

        return [
            'reply' => "{$producto->nombre} tiene {$total} unidad(es) disponibles entre todas las sucursales. Precio de catálogo: Bs " . $this->money($precio) . '.',
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Producto', 'value' => $producto->nombre],
                ['label' => 'Stock total', 'value' => (string) $total],
                ['label' => 'Precio', 'value' => 'Bs ' . $this->money($precio)],
            ],
            'table' => [
                'headers' => ['Sucursal', 'Stock'],
                'rows' => $porSucursal->map(fn ($row) => [$row->sucursal, (string) $row->stock])->all(),
            ],
            'suggestions' => ['¿Cuál fue el producto más vendido este mes?', '¿Cuánto vendimos hoy?', '¿Qué productos tienen stock bajo?'],
        ];
    }

    private function calcularPedido(string $mensaje, ?int $sucursalId, string $alcance): ?array
    {
        $texto = preg_replace('/^(calcula(?:me)?|cotiza(?:me)?|total(?:\s+de)?|cuanto\s+(?:sale|seria|es)|cuánto\s+(?:sale|sería|es))\s*/iu', '', trim($mensaje));
        $segmentos = $this->segmentarPedido((string) $texto);

        if (count($segmentos) === 0) {
            return null;
        }

        $filas = [];
        $faltantes = [];
        $total = 0.0;

        foreach ($segmentos as $segmento) {
            [$cantidad, $termino] = $this->extraerCantidadProducto($segmento);
            if ($cantidad === null || $termino === '') {
                continue;
            }

            $producto = $this->buscarProducto($termino);
            if (! $producto) {
                $faltantes[] = trim($segmento);
                continue;
            }

            if ($sucursalId) {
                $cotizacion = $this->cotizarCantidadProducto($producto, $cantidad, $sucursalId);
                $precio = $cotizacion['precio_promedio'];
                $subtotal = $cotizacion['subtotal'];
                $stock = $cotizacion['stock'];
                $estimado = $cotizacion['estimado'];
            } else {
                $precio = (float) $producto->precio_venta;
                $subtotal = $cantidad * $precio;
                $stock = (int) $this->stockProductoPorSucursal($producto->id)->sum('stock');
                $estimado = false;
            }
            $total += $subtotal;

            $filas[] = [
                'producto' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => $subtotal,
                'stock' => $stock,
                'insuficiente' => $cantidad > $stock,
                'estimado' => $estimado,
            ];
        }

        if (count($filas) === 0) {
            return null;
        }

        $conFaltaStock = collect($filas)->where('insuficiente', true)->count();
        $reply = 'El cálculo da un total de Bs ' . $this->money($total) . '.';
        if ($conFaltaStock > 0 && $sucursalId) {
            $reply .= " Hay {$conFaltaStock} producto(s) cuya cantidad solicitada supera el stock disponible de {$alcance}.";
        }
        if ($faltantes) {
            $reply .= ' No pude identificar: ' . implode(', ', array_slice($faltantes, 0, 3)) . '.';
        }

        return [
            'reply' => $reply,
            'scope' => $alcance,
            'cards' => [
                ['label' => 'Productos', 'value' => (string) count($filas)],
                ['label' => 'Unidades', 'value' => (string) collect($filas)->sum('cantidad')],
                ['label' => 'Total calculado', 'value' => 'Bs ' . $this->money($total)],
            ],
            'table' => [
                'headers' => ['Producto', 'Cant.', 'Precio', 'Subtotal', 'Stock'],
                'rows' => collect($filas)->map(fn ($row) => [
                    $row['producto'],
                    (string) $row['cantidad'],
                    'Bs ' . $this->money($row['precio']),
                    'Bs ' . $this->money($row['subtotal']) . ($row['estimado'] ? ' *' : ''),
                    (string) $row['stock'] . ($row['insuficiente'] ? ' ⚠' : ''),
                ])->all(),
            ],
            'note' => 'Este cálculo es informativo. Cuando hay stock suficiente usa los precios de los lotes en el mismo orden de salida de la venta. * Si falta stock, la parte faltante se estima con el precio de catálogo. El asistente no registra la venta ni descuenta inventario.',
            'suggestions' => ['¿Tenemos stock bajo?', '¿Cuánto vendimos hoy?', '¿Cuál fue el producto más vendido?'],
        ];
    }

    private function segmentarPedido(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        if ($texto === '') {
            return [];
        }

        $segmentos = preg_split('/\s*(?:,|;|\n|\s+y\s+(?=(?:\d+|un|uno|una|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|once|doce|trece|catorce|quince|dieciseis|diecisiete|dieciocho|diecinueve|veinte)\s+))\s*/iu', $texto) ?: [];

        // El dictado por voz a veces no agrega comas: "dos taladros tres discos".
        if (count($segmentos) === 1) {
            $segmentos = preg_split('/\s+(?=(?:\d+|un|uno|una|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|once|doce|trece|catorce|quince|dieciseis|diecisiete|dieciocho|diecinueve|veinte)\s+)/iu', $texto) ?: [];
        }

        return array_slice(array_values(array_filter(array_map('trim', $segmentos))), 0, 20);
    }

    private function extraerCantidadProducto(string $segmento): array
    {
        if (! preg_match('/^\s*(\d+|un|uno|una|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|once|doce|trece|catorce|quince|dieciseis|diecisiete|dieciocho|diecinueve|veinte)\s*(?:x\s*)?(?:de\s+)?(.+)$/iu', trim($segmento), $m)) {
            return [null, ''];
        }

        $cantidadTexto = $this->normalizar($m[1]);
        $cantidad = ctype_digit($cantidadTexto)
            ? (int) $cantidadTexto
            : (self::NUMBER_WORDS[$cantidadTexto] ?? null);

        if ($cantidad === null || $cantidad <= 0 || $cantidad > 9999) {
            return [null, ''];
        }

        return [$cantidad, trim($m[2])];
    }

    private function buscarProducto(string $texto): ?Producto
    {
        $termino = trim($texto);
        $normalizado = $this->normalizar($termino);
        if ($normalizado === '') {
            return null;
        }

        $exacto = Producto::query()
            ->whereRaw('LOWER(codigo) = ?', [mb_strtolower($termino)])
            ->first();
        if ($exacto) {
            return $exacto;
        }

        $tokens = collect(preg_split('/\s+/u', $normalizado) ?: [])
            ->map(fn ($t) => trim($t, " .,-_()/"))
            ->filter(fn ($t) => mb_strlen($t) >= 3 && ! in_array($t, ['para', 'con', 'del', 'una', 'uno', 'los', 'las', 'producto', 'precio', 'stock'], true))
            ->flatMap(function (string $token) {
                $variantes = [$token];
                if (mb_strlen($token) > 5 && str_ends_with($token, 'es')) {
                    $variantes[] = mb_substr($token, 0, -2);
                } elseif (mb_strlen($token) > 4 && str_ends_with($token, 's')) {
                    $variantes[] = mb_substr($token, 0, -1);
                }
                return $variantes;
            })
            ->unique()
            ->take(8)
            ->values();

        $candidatos = Producto::query()
            ->when($tokens->isNotEmpty(), function (Builder $query) use ($tokens) {
                $query->where(function (Builder $sub) use ($tokens) {
                    foreach ($tokens as $token) {
                        $sub->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($token) . '%'])
                            ->orWhereRaw('LOWER(codigo) LIKE ?', ['%' . mb_strtolower($token) . '%']);
                    }
                });
            })
            ->limit(80)
            ->get();

        if ($candidatos->isEmpty()) {
            // Fallback útil con Postgres cuando la única palabra tiene tilde y el usuario la dictó sin ella.
            $candidatos = Producto::query()->orderBy('id')->limit(250)->get();
        }

        return $candidatos
            ->map(function (Producto $producto) use ($normalizado, $tokens) {
                $nombre = $this->normalizar(($producto->codigo ? $producto->codigo . ' ' : '') . $producto->nombre);
                $score = 0;
                foreach ($tokens as $token) {
                    if (str_contains($nombre, $token)) {
                        $score += 25;
                    }
                }
                if (str_contains($nombre, $normalizado)) {
                    $score += 80;
                }
                similar_text($normalizado, $nombre, $porcentaje);
                $score += (int) round($porcentaje / 3);

                return ['producto' => $producto, 'score' => $score];
            })
            ->sortByDesc('score')
            ->filter(fn ($item) => $item['score'] >= 18)
            ->first()['producto'] ?? null;
    }

    private function queryStockDisponible()
    {
        return InventarioSucuralLote::query()
            ->join('lotes', 'lotes.id', '=', 'inventario_sucural_lotes.lote_id')
            ->where('lotes.estado', true)
            ->where('inventario_sucural_lotes.cantidad_en_sucursal', '>', 0)
            ->where(function ($query) {
                $query->whereNull('lotes.fecha_vencimiento')
                    ->orWhereDate('lotes.fecha_vencimiento', '>=', today());
            });
    }

    private function lotesDisponiblesProducto(int $productoId, int $sucursalId): Collection
    {
        return InventarioSucuralLote::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cantidad_en_sucursal', '>', 0)
            ->whereHas('lote', function ($query) use ($productoId) {
                $query->where('producto_id', $productoId)
                    ->where('estado', true)
                    ->where('cantidad_actual', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('fecha_vencimiento')->orWhereDate('fecha_vencimiento', '>=', today());
                    });
            })
            ->with('lote:id,producto_id,fecha_entrada,fecha_vencimiento,cantidad_actual,precio_venta')
            ->get()
            ->sortBy(function ($inventario) {
                $vence = $inventario->lote?->fecha_vencimiento
                    ? $inventario->lote->fecha_vencimiento->format('Y-m-d')
                    : '9999-12-31';
                $entrada = $inventario->lote?->fecha_entrada
                    ? Carbon::parse($inventario->lote->fecha_entrada)->format('Y-m-d')
                    : '9999-12-31';
                return $vence . '|' . $entrada . '|' . str_pad((string) $inventario->lote_id, 12, '0', STR_PAD_LEFT);
            })
            ->map(fn ($inventario) => [
                'lote_id' => (int) $inventario->lote_id,
                'stock' => min((int) $inventario->cantidad_en_sucursal, (int) ($inventario->lote?->cantidad_actual ?? 0)),
                'precio' => (float) ($inventario->lote?->precio_venta ?? 0),
            ])
            ->filter(fn ($lote) => $lote['stock'] > 0)
            ->values();
    }

    private function cotizarCantidadProducto(Producto $producto, int $cantidad, int $sucursalId): array
    {
        $lotes = $this->lotesDisponiblesProducto($producto->id, $sucursalId);
        $stock = (int) $lotes->sum('stock');
        $restante = $cantidad;
        $subtotal = 0.0;
        $cubierto = 0;

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }
            $tomar = min($restante, (int) $lote['stock']);
            $precioLote = (float) ($lote['precio'] > 0 ? $lote['precio'] : $producto->precio_venta);
            $subtotal += $tomar * $precioLote;
            $cubierto += $tomar;
            $restante -= $tomar;
        }

        $estimado = $restante > 0;
        if ($estimado) {
            $subtotal += $restante * (float) $producto->precio_venta;
        }

        return [
            'stock' => $stock,
            'subtotal' => round($subtotal, 2),
            'precio_promedio' => $cantidad > 0 ? round($subtotal / $cantidad, 2) : 0.0,
            'estimado' => $estimado,
        ];
    }

    private function stockDisponibleProducto(int $productoId, int $sucursalId): int
    {
        return (int) $this->queryStockDisponible()
            ->where('lotes.producto_id', $productoId)
            ->where('inventario_sucural_lotes.sucursal_id', $sucursalId)
            ->sum('inventario_sucural_lotes.cantidad_en_sucursal');
    }

    private function stockProductoPorSucursal(int $productoId): Collection
    {
        return $this->queryStockDisponible()
            ->join('sucursals', 'sucursals.id', '=', 'inventario_sucural_lotes.sucursal_id')
            ->where('lotes.producto_id', $productoId)
            ->selectRaw('sucursals.id, sucursals.nombre as sucursal, SUM(inventario_sucural_lotes.cantidad_en_sucursal) as stock')
            ->groupBy('sucursals.id', 'sucursals.nombre')
            ->orderBy('sucursals.nombre')
            ->get();
    }

    private function stockAgrupado(?int $sucursalId): Collection
    {
        return $this->queryStockDisponible()
            ->when($sucursalId, fn ($q) => $q->where('inventario_sucural_lotes.sucursal_id', $sucursalId))
            ->selectRaw('inventario_sucural_lotes.sucursal_id, lotes.producto_id, SUM(inventario_sucural_lotes.cantidad_en_sucursal) as stock')
            ->groupBy('inventario_sucural_lotes.sucursal_id', 'lotes.producto_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->sucursal_id . ':' . $row->producto_id => (int) $row->stock]);
    }

    private function resolverPeriodo(string $mensaje): array
    {
        $hoy = Carbon::today();

        if (str_contains($mensaje, 'ayer')) {
            $ayer = $hoy->copy()->subDay();
            return [$ayer, $ayer, 'ayer'];
        }

        if ($this->contieneAlguno($mensaje, ['semana', 'esta semana'])) {
            return [$hoy->copy()->startOfWeek(), $hoy, 'esta semana'];
        }

        if ($this->contieneAlguno($mensaje, ['mes', 'este mes'])) {
            return [$hoy->copy()->startOfMonth(), $hoy, 'este mes'];
        }

        if ($this->contieneAlguno($mensaje, ['ano', 'año', 'este ano', 'este año'])) {
            return [$hoy->copy()->startOfYear(), $hoy, 'este año'];
        }

        return [$hoy, $hoy, 'hoy'];
    }

    private function extraerTerminoProducto(string $original, string $normalizado): string
    {
        $texto = $original;
        $patrones = [
            '/\b(tenemos|tienes|hay|existe|existencia|existencias|stock|precio|cuanto cuesta|cuánto cuesta|cuanto vale|cuánto vale|a cuanto esta|a cuánto está|en cuanto esta|en cuánto está|cantidad|unidades?)\b/iu',
            '/\b(del|de|el|la|los|las|un|una|producto)\b/iu',
            '/[¿?]/u',
        ];
        foreach ($patrones as $patron) {
            $texto = preg_replace($patron, ' ', $texto);
        }
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $texto));

        return $texto !== '' ? $texto : $normalizado;
    }

    private function esConsultaVentas(string $mensaje): bool
    {
        return $this->contieneAlguno($mensaje, ['venta', 'ventas', 'vendimos', 'vendio', 'vendió', 'facturamos', 'facturado', 'ingresos de ventas']);
    }

    private function esConsultaCantidadProductos(string $mensaje): bool
    {
        return (str_contains($mensaje, 'cuantos productos') || str_contains($mensaje, 'cuántos productos') || str_contains($mensaje, 'cantidad de productos'))
            && ! str_contains($mensaje, 'vendid');
    }

    private function esConsultaProducto(string $mensaje): bool
    {
        return $this->contieneAlguno($mensaje, ['tenemos ', 'tienes ', 'hay ', 'stock de', 'existencia', 'precio de', 'cuanto cuesta', 'cuánto cuesta', 'cuanto vale', 'cuánto vale', 'a cuanto esta', 'a cuánto está', 'en cuanto esta', 'en cuánto está']);
    }

    private function pareceCalculoPedido(string $mensaje): bool
    {
        if (preg_match('/^(calcula|calculame|cotiza|cotizame|total de|cuanto sale|cuánto sale|cuanto seria|cuánto sería)/u', $mensaje)) {
            return true;
        }

        preg_match_all('/\b(?:\d+|un|uno|una|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|once|doce|trece|catorce|quince|dieciseis|diecisiete|dieciocho|diecinueve|veinte)\s+[a-z]/u', $mensaje, $m);
        return count($m[0] ?? []) >= 2;
    }

    private function esAyuda(string $mensaje): bool
    {
        return $this->contieneAlguno($mensaje, ['ayuda', 'que puedes hacer', 'qué puedes hacer', 'comandos', 'ejemplos']);
    }

    private function respuestaAyuda(string $alcance): array
    {
        return [
            'reply' => 'Puedo consultar información real del sistema y calcular pedidos sin modificar datos. Mi alcance actual es: ' . $alcance . '.',
            'scope' => $alcance,
            'suggestions' => $this->sugerenciasBase(),
            'table' => [
                'headers' => ['Puedes preguntar', 'Ejemplo'],
                'rows' => [
                    ['Ventas', '¿Cuánto vendimos hoy?'],
                    ['Producto más vendido', '¿Cuál fue el producto más vendido este mes?'],
                    ['Inventario', '¿Tenemos taladro percutor y cuánto cuesta?'],
                    ['Stock bajo', '¿Qué productos están por agotarse?'],
                    ['Pedido', 'Calcula 2 taladros, 3 discos de corte'],
                ],
            ],
        ];
    }

    private function sugerenciasBase(): array
    {
        return [
            '¿Cuánto vendimos hoy?',
            '¿Cuál fue el producto más vendido este mes?',
            '¿Cuántos productos tenemos?',
            '¿Qué productos tienen stock bajo?',
        ];
    }

    private function contieneAlguno(string $texto, array $terminos): bool
    {
        foreach ($terminos as $termino) {
            if (str_contains($texto, $this->normalizar($termino))) {
                return true;
            }
        }
        return false;
    }

    private function puedeConsultarVentas(User $user): bool
    {
        return $user->can('ventas.index') || $user->can('reportes.ventas');
    }

    private function puedeConsultarProductos(User $user): bool
    {
        foreach ([
            'productos.index',
            'mostrar_inventario_por_sucursal.show',
            'sucursal_por_lotes.index',
            'ventas.create',
            'cotizaciones.create',
        ] as $permiso) {
            if ($user->can($permiso)) {
                return true;
            }
        }

        return false;
    }

    private function sinPermiso(string $modulo, string $alcance): array
    {
        return [
            'reply' => "Su perfil no tiene permiso para consultar {$modulo} desde el asistente. El chat respeta los mismos permisos del sistema.",
            'scope' => $alcance,
            'suggestions' => ['Ayuda'],
        ];
    }

    private function normalizar(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', Str::lower(Str::ascii($texto))));
    }

    private function money(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
