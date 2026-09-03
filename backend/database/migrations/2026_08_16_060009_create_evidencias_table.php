<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_cliente')->unique();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('subido_por_id')->constrained('users')->restrictOnDelete();
            $table->enum('tipo', ['foto', 'video']);
            $table->string('url', 255);
            $table->string('etiqueta', 100)->nullable();
            $table->timestamp('tomada_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
