<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['entrada_compra', 'salida_ot', 'ajuste', 'devolucion']);
            $table->decimal('cantidad', 10, 2);
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('referencia_tipo', 50)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha');

            $table->index(['producto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
