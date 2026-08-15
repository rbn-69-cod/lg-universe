<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excel_import_ranges', function (Blueprint $table) {
            if (! Schema::hasColumn('excel_import_ranges', 'nombre_tabla')) {
                $table->string('nombre_tabla')->nullable()->after('plataforma');
            }

            if (! Schema::hasColumn('excel_import_ranges', 'producto_slug')) {
                $table->string('producto_slug')->nullable()->after('nombre_tabla');
            }

            if (! Schema::hasColumn('excel_import_ranges', 'bot_codigo_url')) {
                $table->text('bot_codigo_url')->nullable()->after('archivo_url');
            }

            if (! Schema::hasColumn('excel_import_ranges', 'bot_soporte_url')) {
                $table->text('bot_soporte_url')->nullable()->after('bot_codigo_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('excel_import_ranges', function (Blueprint $table) {
            foreach (['nombre_tabla', 'producto_slug', 'bot_codigo_url', 'bot_soporte_url'] as $column) {
                if (Schema::hasColumn('excel_import_ranges', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
