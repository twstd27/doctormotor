<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos_directos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->enum('tipo', ['repuesto', 'mano_obra', 'tercerizado']);
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('costo_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costos_directos');
    }
};
