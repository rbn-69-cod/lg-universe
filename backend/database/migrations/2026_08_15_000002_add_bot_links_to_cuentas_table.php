<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            if (! Schema::hasColumn('cuentas', 'bot_hogar_url')) {
                $table->text('bot_hogar_url')->nullable()->after('source_row');
            }

            if (! Schema::hasColumn('cuentas', 'bot_temporal_url')) {
                $table->text('bot_temporal_url')->nullable()->after('bot_hogar_url');
            }

            if (! Schema::hasColumn('cuentas', 'bot_acceso4_url')) {
                $table->text('bot_acceso4_url')->nullable()->after('bot_temporal_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            foreach (['bot_hogar_url', 'bot_temporal_url', 'bot_acceso4_url'] as $column) {
                if (Schema::hasColumn('cuentas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
