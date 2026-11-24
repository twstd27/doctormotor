<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Atajos para armar presupuestos: servicios que se repiten (lavado, diagnóstico…)
        // con su tipo, descripción y precio sugerido, para no teclearlos cada vez.
        Schema::create('servicios_frecuentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->enum('tipo', ['mano_obra', 'tercerizado'])->default('mano_obra');
            $table->string('descripcion', 150);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_frecuentes');
    }
};
