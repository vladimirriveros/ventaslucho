<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_sequences')) {
            Schema::create('business_sequences', function (Blueprint $table) {
                $table->string('clave', 50)->primary();
                $table->unsignedBigInteger('ultimo_numero')->default(0);
                $table->timestamps();
            });
        }

        foreach ([
            'ventas' => ['tabla' => 'ventas', 'prefijo' => 'VEN'],
            'cotizaciones' => ['tabla' => 'cotizaciones', 'prefijo' => 'COT'],
        ] as $clave => $configuracion) {
            $ultimoNumero = DB::table($configuracion['tabla'])
                ->pluck('codigo')
                ->map(function ($codigo) use ($configuracion) {
                    $patron = '/^'.preg_quote($configuracion['prefijo'], '/').'(\d+)$/';

                    return preg_match($patron, (string) $codigo, $coincidencia)
                        ? (int) $coincidencia[1]
                        : 0;
                })
                ->max() ?? 0;

            DB::table('business_sequences')->updateOrInsert(
                ['clave' => $clave],
                ['ultimo_numero' => $ultimoNumero, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        DB::table('business_sequences')->updateOrInsert(
            ['clave' => 'lotes_mutex'],
            ['ultimo_numero' => 0, 'created_at' => now(), 'updated_at' => now()]
        );

        if (!Schema::hasColumn('users', 'sucursal_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->after('id')
                    ->constrained('sucursals')->restrictOnDelete();
            });
        }

        // Compatibilidad para bases anteriores que todavía permitan usuarios
        // sin sucursal. En instalaciones nuevas el campo es obligatorio.
        $sucursalesActivas = DB::table('sucursals')->where('activa', true)->pluck('id');
        if ($sucursalesActivas->count() === 1) {
            DB::table('users')
                ->whereNull('sucursal_id')
                ->update(['sucursal_id' => $sucursalesActivas->first()]);
        }

        if (!Schema::hasColumn('compras', 'sucursal_id')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->after('proveedor_id')
                    ->constrained('sucursals')->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('detalle_ventas', 'costo_unitario')) {
            Schema::table('detalle_ventas', function (Blueprint $table) {
                $table->decimal('costo_unitario', 10, 2)->nullable()->after('precio_unitario');
            });
        }

        if (!Schema::hasColumn('ventas', 'caja_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('caja_id')->nullable()->after('user_id')
                    ->constrained('cajas')->nullOnDelete();
            });

            // Para datos anteriores se usa la primera caja en la que se cobró la venta.
            DB::table('ventas')->select('id')->orderBy('id')->chunkById(200, function ($ventas) {
                foreach ($ventas as $venta) {
                    $cajaId = DB::table('movimientos_caja')
                        ->where('venta_id', $venta->id)
                        ->whereNotNull('caja_id')
                        ->orderBy('id')
                        ->value('caja_id')
                        ?? DB::table('pagos')
                            ->where('venta_id', $venta->id)
                            ->whereNotNull('caja_id')
                            ->orderBy('id')
                            ->value('caja_id');
                    if ($cajaId) {
                        DB::table('ventas')->where('id', $venta->id)->update(['caja_id' => $cajaId]);
                    }
                }
            });
        }

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            if (!Schema::hasColumn('movimiento_inventarios', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('sucursal_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('movimiento_inventarios', 'origen_tipo')) {
                $table->string('origen_tipo', 50)->nullable()->after('tipo_movimiento');
            }
            if (!Schema::hasColumn('movimiento_inventarios', 'origen_id')) {
                $table->unsignedBigInteger('origen_id')->nullable()->after('origen_tipo');
            }
            if (!Schema::hasColumn('movimiento_inventarios', 'stock_anterior')) {
                $table->integer('stock_anterior')->nullable()->after('cantidad');
            }
            if (!Schema::hasColumn('movimiento_inventarios', 'stock_nuevo')) {
                $table->integer('stock_nuevo')->nullable()->after('stock_anterior');
            }
        });

        // Consolida posibles filas duplicadas antes de crear la restricción única.
        DB::table('inventario_sucural_lotes')
            ->select('lote_id', 'sucursal_id', DB::raw('MIN(id) as conservar_id'), DB::raw('SUM(cantidad_en_sucursal) as cantidad_total'))
            ->groupBy('lote_id', 'sucursal_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('conservar_id')
            ->get()
            ->each(function ($duplicado) {
                DB::table('inventario_sucural_lotes')
                    ->where('id', $duplicado->conservar_id)
                    ->update([
                        'cantidad_en_sucursal' => $duplicado->cantidad_total,
                        'updated_at' => now(),
                    ]);

                DB::table('inventario_sucural_lotes')
                    ->where('lote_id', $duplicado->lote_id)
                    ->where('sucursal_id', $duplicado->sucursal_id)
                    ->where('id', '!=', $duplicado->conservar_id)
                    ->delete();
            });

        Schema::table('inventario_sucural_lotes', function (Blueprint $table) {
            $table->unique(['lote_id', 'sucursal_id'], 'inventario_lote_sucursal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_sucural_lotes', function (Blueprint $table) {
            $table->dropUnique('inventario_lote_sucursal_unique');
        });

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $columns = ['user_id', 'origen_tipo', 'origen_id', 'stock_anterior', 'stock_nuevo'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('movimiento_inventarios', $column)) {
                    if ($column === 'user_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        if (Schema::hasColumn('ventas', 'caja_id')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->dropConstrainedForeignId('caja_id'));
        }

        if (Schema::hasColumn('detalle_ventas', 'costo_unitario')) {
            Schema::table('detalle_ventas', fn (Blueprint $table) => $table->dropColumn('costo_unitario'));
        }

        if (Schema::hasColumn('compras', 'sucursal_id')) {
            Schema::table('compras', fn (Blueprint $table) => $table->dropConstrainedForeignId('sucursal_id'));
        }

        if (Schema::hasColumn('users', 'sucursal_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('sucursal_id'));
        }

        Schema::dropIfExists('business_sequences');
    }
};
