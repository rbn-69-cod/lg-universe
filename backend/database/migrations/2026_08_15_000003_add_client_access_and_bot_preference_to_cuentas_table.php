<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            if (! Schema::hasColumn('cuentas', 'cliente_acceso_usuario')) {
                $table->string('cliente_acceso_usuario')->nullable()->after('source_row');
            }

            if (! Schema::hasColumn('cuentas', 'bot_preferencia')) {
                $table->string('bot_preferencia', 20)->default('principal')->after('cliente_acceso_usuario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            foreach (['cliente_acceso_usuario', 'bot_preferencia'] as $column) {
                if (Schema::hasColumn('cuentas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
