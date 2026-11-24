<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_cierres', function (Blueprint $table) {
            $table->text('motivo_diferencia')->nullable()->after('diferencia');
            $table->decimal('fondo_siguiente', 10, 2)->nullable()->after('motivo_diferencia');
        });

        Schema::create('caja_arqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_cierre_id')->constrained('caja_cierres')->cascadeOnDelete();
            $table->decimal('denominacion', 8, 2);
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->unique(['caja_cierre_id', 'denominacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_arqueos');

        Schema::table('caja_cierres', function (Blueprint $table) {
            $table->dropColumn(['motivo_diferencia', 'fondo_siguiente']);
        });
    }
};
