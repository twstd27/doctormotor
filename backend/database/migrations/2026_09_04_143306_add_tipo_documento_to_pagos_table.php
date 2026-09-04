<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // 'factura' queda deshabilitada en la interfaz hasta tener el NIT del taller y un
            // proveedor de facturación electrónica conectado al SIN — se deja el campo desde
            // ahora para no perder el dato de qué cobros se pidieron como factura mientras tanto.
            $table->enum('tipo_documento', ['recibo', 'factura'])->default('recibo')->after('metodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
};
