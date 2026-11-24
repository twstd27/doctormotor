<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_egresos', function (Blueprint $table) {
            $table->string('rubro', 30)->default('otros')->after('categoria');
            // Los gastos que ya existían no dicen de dónde salió el dinero: se asume que no
            // salió de la caja (no cambia ningún arqueo pasado).
            $table->enum('origen', ['caja', 'banco', 'qr'])->default('banco')->after('monto');
            $table->foreignId('caja_cierre_id')->nullable()->after('origen')
                ->constrained('caja_cierres')->nullOnDelete();
            $table->string('comprobante_path', 255)->nullable()->after('comprobante_url');
        });
    }

    public function down(): void
    {
        Schema::table('gastos_egresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_cierre_id');
            $table->dropColumn(['rubro', 'origen', 'comprobante_path']);
        });
    }
};
