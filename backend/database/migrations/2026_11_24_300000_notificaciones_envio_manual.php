<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Envío manual: el sistema deja el mensaje armado y una persona lo manda desde su
        // WhatsApp. `mensaje` guarda el texto tal como se redactó (no cambia si después se
        // retocan las plantillas) y `enviado_por_id` quién lo mandó.
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->text('mensaje')->nullable()->after('payload');
            $table->foreignId('enviado_por_id')->nullable()->after('enviado_at')
                ->constrained('users')->nullOnDelete();
        });

        // Laravel guarda los enum de PostgreSQL como varchar + CHECK: se reemplaza el CHECK
        // para aceptar el estado nuevo «por_enviar».
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT IF EXISTS notificaciones_estado_check');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_estado_check CHECK (estado IN ('pendiente', 'por_enviar', 'enviado', 'fallido'))");
    }

    public function down(): void
    {
        DB::table('notificaciones')->where('estado', 'por_enviar')->update(['estado' => 'pendiente']);
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT IF EXISTS notificaciones_estado_check');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_estado_check CHECK (estado IN ('pendiente', 'enviado', 'fallido'))");

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enviado_por_id');
            $table->dropColumn('mensaje');
        });
    }
};
