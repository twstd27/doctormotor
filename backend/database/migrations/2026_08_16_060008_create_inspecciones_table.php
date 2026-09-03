<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->unique()->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->jsonb('accesorios')->nullable();
            $table->jsonb('rayones_previos')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('firma_cliente_url', 255)->nullable();
            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspecciones');
    }
};
