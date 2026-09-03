<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparto_utilidades', function (Blueprint $table) {
            $table->id();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->decimal('ingresos_total', 12, 2);
            $table->decimal('costos_directos_total', 12, 2);
            $table->decimal('gastos_total', 12, 2);
            $table->decimal('utilidad_neta', 12, 2);
            $table->foreignId('generado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('generado_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparto_utilidades');
    }
};
