<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('placa', 15);
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->smallInteger('anio');
            $table->string('color', 30);
            $table->string('motor', 50)->nullable();
            $table->integer('kilometraje_actual');
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
