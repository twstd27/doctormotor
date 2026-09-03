<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->decimal('monto_original', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
