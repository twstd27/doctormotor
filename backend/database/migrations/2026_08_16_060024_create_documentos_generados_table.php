<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_generados', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['presupuesto', 'recibo', 'orden_trabajo', 'historial_clinico']);
            $table->unsignedBigInteger('referencia_id');
            $table->string('referencia_tipo', 50);
            $table->string('url', 255);
            $table->foreignId('generado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('generado_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_generados');
    }
};
