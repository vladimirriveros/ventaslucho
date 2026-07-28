<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_banca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banca_id')->constrained('bancas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->enum('tipo', ['carga', 'retiro', 'ajuste'])->default('carga');
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_nuevo', 12, 2);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();

            $table->index('banca_id');
            $table->index('tipo');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_banca');
    }
};
