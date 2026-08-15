<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            if (! Schema::hasColumn('cuentas', 'source_platforma')) {
                $table->string('source_platforma')->nullable()->after('activo');
            }

            if (! Schema::hasColumn('cuentas', 'source_hoja_excel')) {
                $table->string('source_hoja_excel')->nullable()->after('source_platforma');
            }

            if (! Schema::hasColumn('cuentas', 'source_row')) {
                $table->unsignedInteger('source_row')->nullable()->after('source_hoja_excel');
            }
        });

        Schema::table('perfiles', function (Blueprint $table) {
            if (! Schema::hasColumn('perfiles', 'numero')) {
                $table->string('numero')->nullable()->after('pin');
            }

            if (! Schema::hasColumn('perfiles', 'vendedor')) {
                $table->string('vendedor')->nullable()->after('numero');
            }

            if (! Schema::hasColumn('perfiles', 'costo')) {
                $table->decimal('costo', 8, 2)->nullable()->after('vendedor');
            }

            if (! Schema::hasColumn('perfiles', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('costo');
            }

            if (! Schema::hasColumn('perfiles', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }

            if (! Schema::hasColumn('perfiles', 'estado_excel')) {
                $table->string('estado_excel')->nullable()->after('fecha_fin');
            }

            if (! Schema::hasColumn('perfiles', 'source_platforma')) {
                $table->string('source_platforma')->nullable()->after('estado_excel');
            }

            if (! Schema::hasColumn('perfiles', 'source_hoja_excel')) {
                $table->string('source_hoja_excel')->nullable()->after('source_platforma');
            }

            if (! Schema::hasColumn('perfiles', 'source_row')) {
                $table->unsignedInteger('source_row')->nullable()->after('source_hoja_excel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            $columns = [
                'numero',
                'vendedor',
                'costo',
                'fecha_inicio',
                'fecha_fin',
                'estado_excel',
                'source_platforma',
                'source_hoja_excel',
                'source_row',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('perfiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('cuentas', function (Blueprint $table) {
            foreach (['source_platforma', 'source_hoja_excel', 'source_row'] as $column) {
                if (Schema::hasColumn('cuentas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
