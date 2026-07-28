<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursals');
            $table->foreignId('user_id')->constrained('users'); // usuario que abrió la caja
            $table->foreignId('user_cierre_id')->nullable()->constrained('users'); // usuario que cerró
            $table->dateTime('fecha_apertura');
            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_final', 10, 2)->nullable();
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones_apertura')->nullable();
            $table->text('observaciones_cierre')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_apertura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
