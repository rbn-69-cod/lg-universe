<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plataformas', 'orden')) {
            Schema::table('plataformas', function (Blueprint $table) {
                $table->unsignedInteger('orden')->default(0);
            });
        }

        DB::table('plataformas')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id, $index) {
                DB::table('plataformas')
                    ->where('id', $id)
                    ->update(['orden' => $index + 1]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('plataformas', 'orden')) {
            Schema::table('plataformas', function (Blueprint $table) {
                $table->dropColumn('orden');
            });
        }
    }
};
