<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('negocio:auditar', function () {
    $hallazgos = [];
    $agregar = static function (string $modulo, string $referencia, string $problema) use (&$hallazgos): void {
        $hallazgos[] = compact('modulo', 'referencia', 'problema');
    };
    $diferente = static fn ($a, $b): bool => abs(round((float) $a, 2) - round((float) $b, 2)) > 0.01;

    DB::table('lotes')->where('cantidad_actual', '<', 0)->orderBy('id')->each(
        fn ($lote) => $agregar('Inventario', "Lote #{$lote->id}", 'La cantidad actual es negativa.')
    );

    DB::table('inventario_sucural_lotes')->where('cantidad_en_sucursal', '<', 0)->orderBy('id')->each(
        fn ($fila) => $agregar('Inventario', "Registro #{$fila->id}", 'La cantidad de la sucursal es negativa.')
    );

    DB::table('inventario_sucural_lotes')
        ->select('lote_id', 'sucursal_id', DB::raw('COUNT(*) as repeticiones'))
        ->groupBy('lote_id', 'sucursal_id')
        ->havingRaw('COUNT(*) > 1')
        ->get()
        ->each(fn ($fila) => $agregar(
            'Inventario',
            "Lote {$fila->lote_id} / sucursal {$fila->sucursal_id}",
            "Existen {$fila->repeticiones} filas de inventario para la misma combinación."
        ));

    DB::table('lotes')->select('id', 'cantidad_actual')->orderBy('id')->chunkById(200, function ($lotes) use ($agregar) {
        foreach ($lotes as $lote) {
            $distribuido = (int) DB::table('inventario_sucural_lotes')->where('lote_id', $lote->id)->sum('cantidad_en_sucursal');
            if ((int) $lote->cantidad_actual !== $distribuido) {
                $agregar(
                    'Inventario',
                    "Lote #{$lote->id}",
                    "Cantidad del lote: {$lote->cantidad_actual}; suma por sucursales: {$distribuido}."
                );
            }
        }
    });

    DB::table('cajas')->where('estado', 'abierta')
        ->select('sucursal_id', DB::raw('COUNT(*) as abiertas'))
        ->groupBy('sucursal_id')
        ->havingRaw('COUNT(*) > 1')
        ->get()
        ->each(fn ($fila) => $agregar(
            'Caja',
            "Sucursal #{$fila->sucursal_id}",
            "Existen {$fila->abiertas} cajas abiertas simultáneamente."
        ));

    DB::table('ventas')->whereNull('deleted_at')->orderBy('id')->chunkById(200, function ($ventas) use ($agregar, $diferente) {
        foreach ($ventas as $venta) {
            $subtotalDetalles = (float) DB::table('detalle_ventas')->where('venta_id', $venta->id)->sum('subtotal');
            $pagos = (float) DB::table('pagos')->where('venta_id', $venta->id)->sum('monto');
            $totalCalculado = round(max(0, (float) $venta->subtotal - (float) $venta->descuento), 2);
            $pendienteCalculado = round(max(0, (float) $venta->total - $pagos), 2);

            if ($diferente($subtotalDetalles, $venta->subtotal)) {
                $agregar('Ventas', "Venta {$venta->codigo}", 'El subtotal no coincide con sus detalles.');
            }
            if ($diferente($totalCalculado, $venta->total)) {
                $agregar('Ventas', "Venta {$venta->codigo}", 'El total no coincide con subtotal menos descuento.');
            }
            if ($venta->estado !== 'anulada' && $diferente($pagos, $venta->pagado)) {
                $agregar('Ventas', "Venta {$venta->codigo}", 'El campo pagado no coincide con la suma de pagos.');
            }
            if ($venta->estado !== 'anulada' && $diferente($pendienteCalculado, $venta->pendiente)) {
                $agregar('Ventas', "Venta {$venta->codigo}", 'El saldo pendiente no coincide con total menos pagos.');
            }
            if ($venta->estado !== 'anulada' && $pagos - (float) $venta->total > 0.01) {
                $agregar('Ventas', "Venta {$venta->codigo}", 'Los pagos superan el total de la venta.');
            }
            if (Schema::hasColumn('ventas', 'caja_id') && !$venta->caja_id) {
                $agregar('Caja', "Venta {$venta->codigo}", 'No tiene registrada la caja donde se originó.');
            }
        }
    });

    DB::table('clientes')->whereNull('deleted_at')->orderBy('id')->chunkById(200, function ($clientes) use ($agregar, $diferente) {
        foreach ($clientes as $cliente) {
            $saldo = (float) DB::table('ventas')
                ->where('cliente_id', $cliente->id)
                ->where('tipo', 'credito')
                ->where('estado', '!=', 'anulada')
                ->whereNull('deleted_at')
                ->sum('pendiente');

            if ($diferente($saldo, $cliente->saldo_pendiente)) {
                $agregar(
                    'Clientes',
                    "Cliente #{$cliente->id}",
                    'El saldo registrado no coincide con las ventas a crédito pendientes.'
                );
            }
        }
    });

    DB::table('cotizaciones')->where('estado', 'convertida')->orderBy('id')->each(function ($cotizacion) use ($agregar) {
        $ventaValida = DB::table('ventas')
            ->where('cotizacion_id', $cotizacion->id)
            ->where('estado', '!=', 'anulada')
            ->whereNull('deleted_at')
            ->exists();

        if (!$ventaValida) {
            $agregar('Cotizaciones', $cotizacion->codigo, 'Figura como convertida, pero no tiene una venta válida asociada.');
        }
    });

    if (Schema::hasColumn('movimiento_inventarios', 'stock_anterior')) {
        DB::table('movimiento_inventarios')
            ->whereNotNull('stock_anterior')
            ->whereNotNull('stock_nuevo')
            ->orderBy('id')
            ->each(function ($movimiento) use ($agregar) {
                $esperado = $movimiento->tipo_movimiento === 'entrada'
                    ? (int) $movimiento->stock_anterior + (int) $movimiento->cantidad
                    : (int) $movimiento->stock_anterior - (int) $movimiento->cantidad;

                if ($esperado !== (int) $movimiento->stock_nuevo) {
                    $agregar('Inventario', "Movimiento #{$movimiento->id}", 'La transición de existencias no es coherente.');
                }
            });
    }

    if ($hallazgos === []) {
        $this->info('Auditoría completada: no se detectaron inconsistencias de negocio.');
        return 0;
    }

    $this->error('Auditoría completada con ' . count($hallazgos) . ' hallazgo(s).');
    $this->table(['Módulo', 'Referencia', 'Problema'], $hallazgos);
    return 1;
})->purpose('Detecta inconsistencias entre inventario, ventas, caja, pagos, clientes y cotizaciones.');
