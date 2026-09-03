<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparto_utilidad_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reparto_utilidad_id')->constrained('reparto_utilidades')->cascadeOnDelete();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->decimal('porcentaje_aplicado', 5, 2);
            $table->decimal('monto', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparto_utilidad_detalle');
    }
};
