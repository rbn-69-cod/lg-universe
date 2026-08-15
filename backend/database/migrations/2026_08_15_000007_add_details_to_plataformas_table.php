<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plataformas', function (Blueprint $table) {
            if (! Schema::hasColumn('plataformas', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('precio');
            }

            if (! Schema::hasColumn('plataformas', 'activacion')) {
                $table->text('activacion')->nullable()->after('features');
            }

            if (! Schema::hasColumn('plataformas', 'terminos')) {
                $table->text('terminos')->nullable()->after('activacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plataformas', function (Blueprint $table) {
            foreach (['terminos', 'activacion', 'descripcion'] as $column) {
                if (Schema::hasColumn('plataformas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
