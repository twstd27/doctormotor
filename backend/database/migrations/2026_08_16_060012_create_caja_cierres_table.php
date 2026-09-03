<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_cierres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cajero_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('monto_apertura', 10, 2);
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('monto_contado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->timestamp('cerrado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cierres');
    }
};
