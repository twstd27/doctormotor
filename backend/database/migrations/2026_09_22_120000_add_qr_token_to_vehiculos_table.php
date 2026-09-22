<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('qr_token', 32)->nullable()->unique()->after('kilometraje_actual');
        });

        // Backfill los vehículos que ya existían antes de este cambio.
        DB::table('vehiculos')->whereNull('qr_token')->orderBy('id')->get(['id'])->each(function ($vehiculo) {
            DB::table('vehiculos')->where('id', $vehiculo->id)->update(['qr_token' => Str::random(32)]);
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
