<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursals')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('stock_minimo')->nullable();
            $table->unsignedInteger('stock_maximo')->nullable();
            $table->timestamps();

            $table->unique(['producto_id', 'sucursal_id'], 'producto_sucursal_unico');
            $table->index(['sucursal_id', 'activo']);
        });

        // En instalaciones existentes, solo se activa el producto en las
        // sucursales donde ya hubo inventario. Una sucursal sin relación no
        // recibirá alertas por un producto que nunca manejó.
        $pares = DB::table('inventario_sucural_lotes')
            ->join('lotes', 'inventario_sucural_lotes.lote_id', '=', 'lotes.id')
            ->select('lotes.producto_id', 'inventario_sucural_lotes.sucursal_id')
            ->distinct()
            ->get();

        foreach ($pares as $par) {
            DB::table('producto_sucursal')->updateOrInsert(
                [
                    'producto_id' => $par->producto_id,
                    'sucursal_id' => $par->sucursal_id,
                ],
                [
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_sucursal');
    }
};
