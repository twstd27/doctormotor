<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('registrado_por_id')->constrained('users')->restrictOnDelete();
            $table->string('numero_factura', 50)->nullable();
            $table->decimal('total', 10, 2);
            $table->enum('estado_pago', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
