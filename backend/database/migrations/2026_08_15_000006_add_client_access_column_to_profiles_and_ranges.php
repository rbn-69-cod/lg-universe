<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            if (! Schema::hasColumn('perfiles', 'cliente_acceso_usuario')) {
                $table->string('cliente_acceso_usuario')->nullable()->after('source_row');
            }
        });

        Schema::table('excel_import_ranges', function (Blueprint $table) {
            if (! Schema::hasColumn('excel_import_ranges', 'columna_cliente_acceso_usuario')) {
                $table->string('columna_cliente_acceso_usuario', 8)->default('X')->after('columna_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('excel_import_ranges', function (Blueprint $table) {
            if (Schema::hasColumn('excel_import_ranges', 'columna_cliente_acceso_usuario')) {
                $table->dropColumn('columna_cliente_acceso_usuario');
            }
        });

        Schema::table('perfiles', function (Blueprint $table) {
            if (Schema::hasColumn('perfiles', 'cliente_acceso_usuario')) {
                $table->dropColumn('cliente_acceso_usuario');
            }
        });
    }
};
