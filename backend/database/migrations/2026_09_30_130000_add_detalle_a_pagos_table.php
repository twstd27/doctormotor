<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // 'resumen' = texto libre que escribe el cajero (detalle_servicio); 'detallado' =
            // líneas armadas automáticamente a partir de los ítems aprobados del presupuesto
            // de la OT al momento de cobrar (detalle_items, snapshot — no cambia si el
            // presupuesto cambia después).
            $table->string('detalle_tipo')->default('resumen')->after('tipo_documento');
            $table->text('detalle_servicio')->nullable()->after('detalle_tipo');
            $table->json('detalle_items')->nullable()->after('detalle_servicio');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['detalle_tipo', 'detalle_servicio', 'detalle_items']);
        });
    }
};
