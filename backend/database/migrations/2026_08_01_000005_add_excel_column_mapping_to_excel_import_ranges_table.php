<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excel_import_ranges', function (Blueprint $table) {
            $columns = [
                'columna_perfil' => 'F',
                'columna_pin' => 'G',
                'columna_numero' => 'H',
                'columna_vendedor_igarlos' => 'I',
                'columna_vendedor_nikol' => 'J',
                'columna_costo' => 'K',
                'columna_fecha_inicio' => 'L',
                'columna_fecha_fin' => 'M',
                'columna_estado' => 'N',
                'columna_correo' => 'U',
                'columna_password' => 'V',
            ];

            foreach ($columns as $column => $default) {
                if (! Schema::hasColumn('excel_import_ranges', $column)) {
                    $table->string($column, 8)->default($default)->after('archivo_url');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('excel_import_ranges', function (Blueprint $table) {
            $columns = [
                'columna_perfil',
                'columna_pin',
                'columna_numero',
                'columna_vendedor_igarlos',
                'columna_vendedor_nikol',
                'columna_costo',
                'columna_fecha_inicio',
                'columna_fecha_fin',
                'columna_estado',
                'columna_correo',
                'columna_password',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('excel_import_ranges', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
