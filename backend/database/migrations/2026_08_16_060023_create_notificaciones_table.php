<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('telefono_destino', 20);
            $table->enum('canal', ['whatsapp', 'email']);
            $table->string('plantilla', 80);
            $table->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $table->jsonb('payload')->nullable();
            $table->enum('estado', ['pendiente', 'enviado', 'fallido'])->default('pendiente');
            $table->timestamp('enviado_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
