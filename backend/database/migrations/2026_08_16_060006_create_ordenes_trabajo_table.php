<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('recibido_por_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tecnico_asignado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', [
                'recepcionado', 'en_diagnostico', 'esperando_aprobacion', 'en_reparacion',
                'control_calidad', 'listo_entrega', 'entregado', 'cancelado',
            ])->default('recepcionado');
            $table->text('descripcion_problema');
            $table->integer('kilometraje_ingreso');
            $table->enum('nivel_gasolina', ['E', '1/4', '1/2', '3/4', 'F']);
            $table->timestamp('fecha_ingreso');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->timestamp('fecha_entrega_real')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('vehiculo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
