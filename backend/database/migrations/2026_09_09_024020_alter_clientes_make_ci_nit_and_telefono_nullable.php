<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Un cliente que se registra solo (Google OAuth) todavía no tiene CI/NIT ni WhatsApp —
// el personal los completa después. Usa SQL nativo en vez de Blueprint::change() para no
// depender de doctrine/dbal solo por esto.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE clientes ALTER COLUMN ci_nit DROP NOT NULL');
        DB::statement('ALTER TABLE clientes ALTER COLUMN telefono_whatsapp DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE clientes SET ci_nit = '' WHERE ci_nit IS NULL");
        DB::statement("UPDATE clientes SET telefono_whatsapp = '' WHERE telefono_whatsapp IS NULL");
        DB::statement('ALTER TABLE clientes ALTER COLUMN ci_nit SET NOT NULL');
        DB::statement('ALTER TABLE clientes ALTER COLUMN telefono_whatsapp SET NOT NULL');
    }
};
