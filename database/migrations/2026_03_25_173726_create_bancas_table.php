<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bancas', function (Blueprint $table) {
            $table->id();
            $table->string('banco', 100);
            $table->string('numero_cuenta', 50);
            $table->string('nombre', 120)->comment('Nombre visible o titular de la cuenta');
            $table->string('qr_code', 255)->nullable();
            $table->decimal('saldo_actual', 12, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activa', 'banco']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bancas');
    }
};
