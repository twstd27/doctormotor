<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_egresos', function (Blueprint $table) {
            $table->id();
            $table->enum('categoria', ['fijo', 'variable']);
            $table->string('concepto', 150);
            $table->decimal('monto', 10, 2);
            $table->foreignId('registrado_por_id')->constrained('users')->restrictOnDelete();
            $table->string('comprobante_url', 255)->nullable();
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_egresos');
    }
};
