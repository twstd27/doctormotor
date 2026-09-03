<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('cajero_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('caja_cierre_id')->nullable()->constrained('caja_cierres')->nullOnDelete();
            $table->enum('tipo', ['anticipo', 'parcial', 'completo', 'abono_deuda']);
            $table->enum('metodo', ['efectivo', 'qr', 'tarjeta']);
            $table->decimal('monto', 10, 2);
            $table->string('referencia_externa', 100)->nullable();
            $table->string('comprobante_url', 255)->nullable();
            $table->timestamp('fecha');
            $table->timestamps();

            $table->index('caja_cierre_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
